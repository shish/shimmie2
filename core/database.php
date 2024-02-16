<?php

declare(strict_types=1);

namespace Shimmie2;

use FFSPHP\PDO;
use FFSPHP\PDOStatement;

require_once __DIR__ . '/exceptions.php';

enum DatabaseDriverID: string
{
    case MYSQL = "mysql";
    case PGSQL = "pgsql";
    case SQLITE = "sqlite";
}

class DatabaseException extends SCoreException
{
    public string $query;
    /** @var array<string, mixed> */
    public array $args;

    /**
     * @param array<string, mixed> $args
     */
    public function __construct(string $msg, string $query, array $args)
    {
        parent::__construct($msg);
        $this->error = $msg;
        $this->query = $query;
        $this->args = $args;
    }
}

/**
 * A class for controlled database access
 *
 * @phpstan-type QueryArgs array<string, string|int|bool|null>
 */
class Database
{
    private string $dsn;

    /**
     * The PDO database connection object, for anyone who wants direct access.
     */
    private ?PDO $db = null;
    public float $dbtime = 0.0;

    /**
     * Meta info about the database engine.
     */
    private ?DBEngine $engine = null;

    /**
     * How many queries this DB object has run
     */
    public int $query_count = 0;
    /** @var string[] */
    public array $queries = [];

    public function __construct(string $dsn)
    {
        $this->dsn = $dsn;
    }

    private function get_db(): PDO
    {
        if(is_null($this->db)) {
            $this->db = new PDO($this->dsn);
            $this->connect_engine();
            $this->get_engine()->init($this->db);
            $this->begin_transaction();
        }
        return $this->db;
    }

    private function connect_engine(): void
    {
        if (preg_match("/^([^:]*)/", $this->dsn, $matches)) {
            $db_proto = $matches[1];
        } else {
            throw new ServerError("Can't figure out database engine");
        }

        if ($db_proto === DatabaseDriverID::MYSQL->value) {
            $this->engine = new MySQL();
        } elseif ($db_proto === DatabaseDriverID::PGSQL->value) {
            $this->engine = new PostgreSQL();
        } elseif ($db_proto === DatabaseDriverID::SQLITE->value) {
            $this->engine = new SQLite();
        } else {
            die_nicely(
                'Unknown PDO driver: '.$db_proto,
                "Please check that this is a valid driver, installing the PHP modules if needed"
            );
        }
    }

    public function begin_transaction(): void
    {
        if ($this->is_transaction_open() === false) {
            $this->get_db()->beginTransaction();
        }
    }

    public function is_transaction_open(): bool
    {
        return !is_null($this->db) && $this->db->inTransaction();
    }

    public function commit(): bool
    {
        if ($this->is_transaction_open()) {
            return $this->get_db()->commit();
        } else {
            throw new ServerError("Unable to call commit() as there is no transaction currently open.");
        }
    }

    public function rollback(): bool
    {
        if ($this->is_transaction_open()) {
            return $this->get_db()->rollback();
        } else {
            throw new ServerError("Unable to call rollback() as there is no transaction currently open.");
        }
    }

    /**
     * @template T
     * @param callable():T $callback
     * @return T
     */
    public function with_savepoint(callable $callback, string $name = "sp"): mixed
    {
        global $_tracer;
        try {
            $_tracer->begin("Savepoint $name");
            $this->execute("SAVEPOINT $name");
            $ret = $callback();
            $this->execute("RELEASE SAVEPOINT $name");
            $_tracer->end();
            return $ret;
        } catch (\Exception $e) {
            $this->execute("ROLLBACK TO SAVEPOINT $name");
            $_tracer->end();
            throw $e;
        }
    }

    private function get_engine(): DBEngine
    {
        if (is_null($this->engine)) {
            $this->connect_engine();
        }
        return $this->engine;
    }

    public function scoreql_to_sql(string $input): string
    {
        return $this->get_engine()->scoreql_to_sql($input);
    }

    public function get_driver_id(): DatabaseDriverID
    {
        return $this->get_engine()->id;
    }

    public function get_version(): string
    {
        return $this->get_engine()->get_version($this->get_db());
    }

    /**
     * @param QueryArgs $args
     */
    private function count_time(string $method, float $start, string $query, ?array $args): void
    {
        global $_tracer, $tracer_enabled;
        $dur = ftime() - $start;
        // trim whitespace
        $query = preg_replace('/[\n\t ]+/m', ' ', $query);
        $query = trim($query);
        if ($tracer_enabled) {
            $_tracer->complete($start * 1000000, $dur * 1000000, "DB Query", ["query" => $query, "args" => $args, "method" => $method]);
        }
        $this->queries[] = $query;
        $this->query_count++;
        $this->dbtime += $dur;
    }

    public function set_timeout(?int $time): void
    {
        $this->get_engine()->set_timeout($this->get_db(), $time);
    }

    public function notify(string $channel, ?string $data = null): void
    {
        $this->get_engine()->notify($this->get_db(), $channel, $data);
    }

    /**
     * @param QueryArgs $args
     */
    public function _execute(string $query, array $args = []): PDOStatement
    {
        try {
            $uri = $_SERVER['REQUEST_URI'] ?? "unknown uri";
            return $this->get_db()->execute(
                "-- $uri\n" .
                $query,
                $args
            );
        } catch (\PDOException $pdoe) {
            throw new DatabaseException($pdoe->getMessage(), $query, $args);
        }
    }

    /**
     * Execute an SQL query with no return
     *
     * @param QueryArgs $args
     */
    public function execute(string $query, array $args = []): PDOStatement
    {
        $_start = ftime();
        $st = $this->_execute($query, $args);
        $this->count_time("execute", $_start, $query, $args);
        return $st;
    }

    /**
     * Execute an SQL query and return a 2D array.
     *
     * @param QueryArgs $args
     * @return array<array<string, mixed>>
     */
    public function get_all(string $query, array $args = []): array
    {
        $_start = ftime();
        $data = $this->_execute($query, $args)->fetchAll();
        $this->count_time("get_all", $_start, $query, $args);
        return $data;
    }

    /**
     * Execute an SQL query and return a iterable object for use with generators.
     *
     * @param QueryArgs $args
     */
    public function get_all_iterable(string $query, array $args = []): PDOStatement
    {
        $_start = ftime();
        $data = $this->_execute($query, $args);
        $this->count_time("get_all_iterable", $_start, $query, $args);
        return $data;
    }

    /**
     * Execute an SQL query and return a single row.
     *
     * @param QueryArgs $args
     * @return array<string, mixed>
     */
    public function get_row(string $query, array $args = []): ?array
    {
        $_start = ftime();
        $row = $this->_execute($query, $args)->fetch();
        $this->count_time("get_row", $_start, $query, $args);
        return $row ? $row : null;
    }

    /**
     * Execute an SQL query and return the first column of each row.
     *
     * @param QueryArgs $args
     * @return array<mixed>
     */
    public function get_col(string $query, array $args = []): array
    {
        $_start = ftime();
        $res = $this->_execute($query, $args)->fetchAll(PDO::FETCH_COLUMN);
        $this->count_time("get_col", $_start, $query, $args);
        return $res;
    }

    /**
     * Execute an SQL query and return the first column of each row as a single iterable object.
     *
     * @param QueryArgs $args
     */
    public function get_col_iterable(string $query, array $args = []): \Generator
    {
        $_start = ftime();
        $stmt = $this->_execute($query, $args);
        $this->count_time("get_col_iterable", $_start, $query, $args);
        foreach ($stmt as $row) {
            yield $row[0];
        }
    }

    /**
     * Execute an SQL query and return the the first column => the second column.
     *
     * @param QueryArgs $args
     * @return array<string, mixed>
     */
    public function get_pairs(string $query, array $args = []): array
    {
        $_start = ftime();
        $res = $this->_execute($query, $args)->fetchAll(PDO::FETCH_KEY_PAIR);
        $this->count_time("get_pairs", $_start, $query, $args);
        return $res;
    }


    /**
     * Execute an SQL query and return the the first column => the second column as an iterable object.
     *
     * @param QueryArgs $args
     */
    public function get_pairs_iterable(string $query, array $args = []): \Generator
    {
        $_start = ftime();
        $stmt = $this->_execute($query, $args);
        $this->count_time("get_pairs_iterable", $_start, $query, $args);
        foreach ($stmt as $row) {
            yield $row[0] => $row[1];
        }
    }

    /**
     * Execute an SQL query and return a single value, or null.
     *
     * @param QueryArgs $args
     */
    public function get_one(string $query, array $args = []): mixed
    {
        $_start = ftime();
        $row = $this->_execute($query, $args)->fetch();
        $this->count_time("get_one", $_start, $query, $args);
        return $row ? $row[0] : null;
    }

    /**
     * Execute an SQL query and returns a bool indicating if any data was returned
     *
     * @param QueryArgs $args
     */
    public function exists(string $query, array $args = []): bool
    {
        $_start = ftime();
        $row = $this->_execute($query, $args)->fetch();
        $this->count_time("exists", $_start, $query, $args);
        if ($row == null) {
            return false;
        }
        return true;
    }

    /**
     * Get the ID of the last inserted row.
     */
    public function get_last_insert_id(string $seq): int
    {
        if ($this->get_engine()->id == DatabaseDriverID::PGSQL) {
            $id = $this->get_db()->lastInsertId($seq);
        } else {
            $id = $this->get_db()->lastInsertId();
        }
        assert(is_numeric($id));
        return (int)$id;
    }

    /**
     * Create a table from pseudo-SQL.
     */
    public function create_table(string $name, string $data): void
    {
        if (is_null($this->engine)) {
            $this->connect_engine();
        }
        $data = trim($data, ", \t\n\r\0\x0B");  // mysql doesn't like trailing commas
        $this->execute($this->get_engine()->create_table_sql($name, $data));
    }

    /**
     * Returns the number of tables present in the current database.
     */
    public function count_tables(): int
    {
        if ($this->get_engine()->id === DatabaseDriverID::MYSQL) {
            return count(
                $this->get_all("SHOW TABLES")
            );
        } elseif ($this->get_engine()->id === DatabaseDriverID::PGSQL) {
            return count(
                $this->get_all("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'")
            );
        } elseif ($this->get_engine()->id === DatabaseDriverID::SQLITE) {
            return count(
                $this->get_all("SELECT name FROM sqlite_master WHERE type = 'table'")
            );
        } else {
            $did = (string)$this->get_engine()->id;
            throw new ServerError("Can't count tables for database type {$did}");
        }
    }

    public function raw_db(): PDO
    {
        return $this->get_db();
    }

    public function standardise_boolean(string $table, string $column, bool $include_postgres = false): void
    {
        $d = $this->get_driver_id();
        if ($d == DatabaseDriverID::MYSQL) {
            # In mysql, ENUM('Y', 'N') is secretly INTEGER where Y=1 and N=2.
            # BOOLEAN is secretly TINYINT where true=1 and false=0.
            # So we can cast directly from ENUM to BOOLEAN which gives us a
            # column of values 'true' and 'invalid but who cares lol', which
            # we can then UPDATE to be 'true' and 'false'.
            $this->execute("ALTER TABLE $table MODIFY COLUMN $column BOOLEAN;");
            $this->execute("UPDATE $table SET $column=0 WHERE $column=2;");
        }
        if ($d == DatabaseDriverID::SQLITE) {
            # SQLite doesn't care about column types at all, everything is
            # text, so we can in-place replace a char with a bool
            $this->execute("UPDATE $table SET $column = ($column IN ('Y', 1))");
        }
        if ($d == DatabaseDriverID::PGSQL && $include_postgres) {
            $this->execute("ALTER TABLE $table ADD COLUMN {$column}_b BOOLEAN DEFAULT FALSE NOT NULL");
            $this->execute("UPDATE $table SET {$column}_b = ($column = 'Y')");
            $this->execute("ALTER TABLE $table DROP COLUMN $column");
            $this->execute("ALTER TABLE $table RENAME COLUMN {$column}_b TO $column");
        }
    }

    /**
     * Generates a deterministic pseudorandom order based on a seed and a column ID
     */
    public function seeded_random(int $seed, string $id_column): string
    {
        $d = $this->get_driver_id();
        if ($d == DatabaseDriverID::MYSQL) {
            // MySQL supports RAND(n), where n is a random seed. Use that.
            return "RAND($seed)";
        }

        // As fallback, use MD5 as a DRBG.
        return "MD5(CONCAT($seed, CONCAT('+', $id_column)))";
    }
}
