<?php

declare(strict_types=1);

namespace Shimmie2;

use FFSPHP\{PDO, PDOStatement};

/**
 * A class for controlled database access
 */
class Database
{
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

    public function __construct(
        private string $dsn
    ) {
    }

    private function get_db(): PDO
    {
        if (is_null($this->db)) {
            $this->db = new PDO($this->dsn);
            $this->connect_engine();
            assert(!is_null($this->db));
            $this->get_engine()->init($this->db);
            $this->begin_transaction();
            assert(!is_null($this->db));
        }
        return $this->db;
    }

    private function connect_engine(): void
    {
        if (\Safe\preg_match("/^([^:]*)/", $this->dsn, $matches)) {
            $db_proto = $matches[1];
        } else {
            throw new ServerError("Can't figure out database engine");
        }

        $this->engine = match($db_proto) {
            DatabaseDriverID::MYSQL->value => new MySQL(),
            DatabaseDriverID::PGSQL->value => new PostgreSQL(),
            DatabaseDriverID::SQLITE->value => new SQLite(),
            default => throw new ServerError('Unknown PDO driver: '.$db_proto),
        };
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
            return $this->get_db()->rollBack();
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
        try {
            Ctx::$tracer->begin("Savepoint $name");
            // doing string interpolation because bound parameters don't work here
            $this->execute("SAVEPOINT $name");  // @phpstan-ignore-line
            $ret = $callback();
            $this->execute("RELEASE SAVEPOINT $name");  // @phpstan-ignore-line
            Ctx::$tracer->end();
            return $ret;
        } catch (\Exception $e) {
            $this->execute("ROLLBACK TO SAVEPOINT $name");  // @phpstan-ignore-line
            Ctx::$tracer->end();
            throw $e;
        }
    }

    private function get_engine(): DBEngine
    {
        if (is_null($this->engine)) {
            $this->connect_engine();
            assert(!is_null($this->engine));
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
     * @param sql-params-array $args
     */
    private function count_time(string $method, float $start, string $query, ?array $args): void
    {
        $dur = ftime() - $start;
        // trim whitespace
        $query = \Safe\preg_replace('/[\n\t ]+/m', ' ', $query);
        $query = trim($query);
        if (Ctx::$tracer_enabled) {
            Ctx::$tracer->complete($start * 1000000, $dur * 1000000, "DB Query", ["query" => $query, "args" => $args, "method" => $method]);
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
     * @param sql-params-array $args
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
     * @param literal-string $query
     * @param sql-params-array $args
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
     * @param literal-string $query
     * @param sql-params-array $args
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
     * @param literal-string $query
     * @param sql-params-array $args
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
     * @param literal-string $query
     * @param sql-params-array $args
     * @return array<string, mixed>|null
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
     * @param literal-string $query
     * @param sql-params-array $args
     * @return list<mixed>
     */
    public function get_col(string $query, array $args = []): array
    {
        $_start = ftime();
        $res = $this->_execute($query, $args)->fetchAll(PDO::FETCH_COLUMN);
        $this->count_time("get_col", $_start, $query, $args);
        // @phpstan-ignore-next-line -- this is a list, but fetchAll is typed as array
        return $res;
    }

    /**
     * Execute an SQL query and return the first column of each row as a single iterable object.
     *
     * @param literal-string $query
     * @param sql-params-array $args
     * @return \Generator<mixed>
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
     * @param literal-string $query
     * @param sql-params-array $args
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
     * @param literal-string $query
     * @param sql-params-array $args
     * @return \Generator<string, mixed>
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
     * @param literal-string $query
     * @param sql-params-array $args
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
     * @param literal-string $query
     * @param sql-params-array $args
     */
    public function exists(string $query, array $args = []): bool
    {
        $_start = ftime();
        $row = $this->_execute($query, $args)->fetch();
        $this->count_time("exists", $_start, $query, $args);
        if ($row === null) {
            return false;
        }
        return true;
    }

    /**
     * Get the ID of the last inserted row.
     */
    public function get_last_insert_id(string $seq): int
    {
        if ($this->get_engine()->id === DatabaseDriverID::PGSQL) {
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
        // @phpstan-ignore-next-line
        $this->execute($this->get_engine()->create_table_sql($name, $data));
    }

    /**
     * Returns the number of tables present in the current database.
     */
    public function count_tables(): int
    {
        $sql = match ($this->get_engine()->id) {
            DatabaseDriverID::MYSQL => "SHOW TABLES",
            DatabaseDriverID::PGSQL => "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'",
            DatabaseDriverID::SQLITE => "SELECT name FROM sqlite_master WHERE type = 'table'",
        };
        return count($this->get_col($sql));
    }

    public function raw_db(): PDO
    {
        return $this->get_db();
    }

    public function standardise_boolean(string $table, string $column, bool $include_postgres = false): void
    {
        $d = $this->get_driver_id();
        if ($d === DatabaseDriverID::MYSQL) {
            # In mysql, ENUM('Y', 'N') is secretly INTEGER where Y=1 and N=2.
            # BOOLEAN is secretly TINYINT where true=1 and false=0.
            # So we can cast directly from ENUM to BOOLEAN which gives us a
            # column of values 'true' and 'invalid but who cares lol', which
            # we can then UPDATE to be 'true' and 'false'.
            $this->execute("ALTER TABLE $table MODIFY COLUMN $column BOOLEAN;");  // @phpstan-ignore-line
            $this->execute("UPDATE $table SET $column=0 WHERE $column=2;");  // @phpstan-ignore-line
        }
        if ($d === DatabaseDriverID::SQLITE) {
            # SQLite doesn't care about column types at all, everything is
            # text, so we can in-place replace a char with a bool
            $this->execute("UPDATE $table SET $column = ($column IN ('Y', 1))");  // @phpstan-ignore-line
        }
        if ($d === DatabaseDriverID::PGSQL && $include_postgres) {
            $this->execute("ALTER TABLE $table ADD COLUMN {$column}_b BOOLEAN DEFAULT FALSE NOT NULL");  // @phpstan-ignore-line
            $this->execute("UPDATE $table SET {$column}_b = ($column = 'Y')");  // @phpstan-ignore-line
            $this->execute("ALTER TABLE $table DROP COLUMN $column");  // @phpstan-ignore-line
            $this->execute("ALTER TABLE $table RENAME COLUMN {$column}_b TO $column");  // @phpstan-ignore-line
        }
    }

    /**
     * Generates a deterministic pseudorandom order based on a seed and a column ID
     */
    public function seeded_random(int $seed, string $id_column): string
    {
        $d = $this->get_driver_id();
        if ($d === DatabaseDriverID::MYSQL) {
            // MySQL supports RAND(n), where n is a random seed. Use that.
            return "RAND($seed)";
        }

        // As fallback, use MD5 as a DRBG.
        return "MD5($seed || '+' || $id_column)";
    }
}
