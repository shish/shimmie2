<?php declare(strict_types=1);
use FFSPHP\PDO;

abstract class DatabaseDriver
{
    public const MYSQL = "mysql";
    public const PGSQL = "pgsql";
    public const SQLITE = "sqlite";
}

/**
 * A class for controlled database access
 */
class Database
{
    /** @var string */
    private $dsn;

    /**
     * The PDO database connection object, for anyone who wants direct access.
     * @var null|PDO
     */
    private $db = null;

    /**
     * @var float
     */
    public $dbtime = 0.0;

    /**
     * Meta info about the database engine.
     * @var DBEngine|null
     */
    private $engine = null;

    /**
     * A boolean flag to track if we already have an active transaction.
     * (ie: True if beginTransaction() already called)
     *
     * @var bool
     */
    public $transaction = false;

    /**
     * How many queries this DB object has run
     */
    public $query_count = 0;

    public function __construct(string $dsn)
    {
        $this->dsn = $dsn;
    }

    private function connect_db(): void
    {
        $this->db = new PDO($this->dsn, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $this->connect_engine();
        $this->engine->init($this->db);

        $this->begin_transaction();
    }

    private function connect_engine(): void
    {
        if (preg_match("/^([^:]*)/", $this->dsn, $matches)) {
            $db_proto=$matches[1];
        } else {
            throw new SCoreException("Can't figure out database engine");
        }

        if ($db_proto === DatabaseDriver::MYSQL) {
            $this->engine = new MySQL();
        } elseif ($db_proto === DatabaseDriver::PGSQL) {
            $this->engine = new PostgreSQL();
        } elseif ($db_proto === DatabaseDriver::SQLITE) {
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
        if ($this->transaction === false) {
            $this->db->beginTransaction();
            $this->transaction = true;
        }
    }

    public function is_transaction_open(): bool
    {
        return !is_null($this->db) && $this->transaction === true;
    }

    public function commit(): bool
    {
        if ($this->is_transaction_open()) {
            $this->transaction = false;
            return $this->db->commit();
        } else {
            throw new SCoreException("Unable to call commit() as there is no transaction currently open.");
        }
    }

    public function rollback(): bool
    {
        if ($this->is_transaction_open()) {
            $this->transaction = false;
            return $this->db->rollback();
        } else {
            throw new SCoreException("Unable to call rollback() as there is no transaction currently open.");
        }
    }

    public function scoreql_to_sql(string $input): string
    {
        if (is_null($this->engine)) {
            $this->connect_engine();
        }
        return $this->engine->scoreql_to_sql($input);
    }

    public function scoresql_value_prepare($input)
    {
        if (is_null($this->engine)) {
            $this->connect_engine();
        }
        if ($input===true) {
            return $this->engine->BOOL_Y;
        } elseif ($input===false) {
            return $this->engine->BOOL_N;
        }
        return $input;
    }

    public function get_driver_name(): string
    {
        if (is_null($this->engine)) {
            $this->connect_engine();
        }
        return $this->engine->name;
    }

    public function get_version(): string
    {
        return $this->engine->get_version($this->db);
    }

    private function count_time(string $method, float $start, string $query, ?array $args): void
    {
        global $_tracer, $tracer_enabled;
        $dur = microtime(true) - $start;
        if ($tracer_enabled) {
            $query = trim(preg_replace('/^[\t ]+/m', '', $query));  // trim leading whitespace
            $_tracer->complete($start * 1000000, $dur * 1000000, "DB Query", ["query"=>$query, "args"=>$args, "method"=>$method]);
        }
        $this->query_count++;
        $this->dbtime += $dur;
    }

    public function set_timeout(int $time): void
    {
        $this->engine->set_timeout($this->db, $time);
    }

    public function notify(string $channel, ?string $data=null): void
    {
        $this->engine->notify($this->db, $channel, $data);
    }

    public function execute(string $query, array $args = []): PDOStatement
    {
        try {
            if (is_null($this->db)) {
                $this->connect_db();
            }
            return $this->db->execute(
                "-- " . str_replace("%2F", "/", urlencode($_GET['q'] ?? '')). "\n" .
                $query,
                $args
            );
        } catch (PDOException $pdoe) {
            throw new SCoreException($pdoe->getMessage(), $query);
        }
    }

    /**
     * Execute an SQL query and return a 2D array.
     */
    public function get_all(string $query, array $args = []): array
    {
        $_start = microtime(true);
        $data = $this->execute($query, $args)->fetchAll();
        $this->count_time("get_all", $_start, $query, $args);
        return $data;
    }

    /**
     * Execute an SQL query and return a iterable object for use with generators.
     */
    public function get_all_iterable(string $query, array $args = []): PDOStatement
    {
        $_start = microtime(true);
        $data = $this->execute($query, $args);
        $this->count_time("get_all_iterable", $_start, $query, $args);
        return $data;
    }

    /**
     * Execute an SQL query and return a single row.
     */
    public function get_row(string $query, array $args = []): ?array
    {
        $_start = microtime(true);
        $row = $this->execute($query, $args)->fetch();
        $this->count_time("get_row", $_start, $query, $args);
        return $row ? $row : null;
    }

    /**
     * Execute an SQL query and return the first column of each row.
     */
    public function get_col(string $query, array $args = []): array
    {
        $_start = microtime(true);
        $res = $this->execute($query, $args)->fetchAll(PDO::FETCH_COLUMN);
        $this->count_time("get_col", $_start, $query, $args);
        return $res;
    }

    /**
     * Execute an SQL query and return the first column of each row as a single iterable object.
     */
    public function get_col_iterable(string $query, array $args = []): Generator
    {
        $_start = microtime(true);
        $stmt = $this->execute($query, $args);
        $this->count_time("get_col_iterable", $_start, $query, $args);
        foreach ($stmt as $row) {
            yield $row[0];
        }
    }

    /**
     * Execute an SQL query and return the the first column => the second column.
     */
    public function get_pairs(string $query, array $args = []): array
    {
        $_start = microtime(true);
        $res = $this->execute($query, $args)->fetchAll(PDO::FETCH_KEY_PAIR);
        $this->count_time("get_pairs", $_start, $query, $args);
        return $res;
    }


    /**
     * Execute an SQL query and return the the first column => the second column as an iterable object.
     */
    public function get_pairs_iterable(string $query, array $args = []): Generator
    {
        $_start = microtime(true);
        $stmt = $this->execute($query, $args);
        $this->count_time("get_pairs_iterable", $_start, $query, $args);
        foreach ($stmt as $row) {
            yield $row[0] => $row[1];
        }
    }

    /**
     * Execute an SQL query and return a single value, or null.
     */
    public function get_one(string $query, array $args = [])
    {
        $_start = microtime(true);
        $row = $this->execute($query, $args)->fetch();
        $this->count_time("get_one", $_start, $query, $args);
        return $row ? $row[0] : null;
    }

    /**
     * Execute an SQL query and returns a bool indicating if any data was returned
     */
    public function exists(string $query, array $args = []): bool
    {
        $_start = microtime(true);
        $row = $this->execute($query, $args)->fetch();
        $this->count_time("exists", $_start, $query, $args);
        if ($row==null) {
            return false;
        }
        return true;
    }

    /**
     * Get the ID of the last inserted row.
     */
    public function get_last_insert_id(string $seq): int
    {
        if ($this->engine->name == DatabaseDriver::PGSQL) {
            $id = $this->db->lastInsertId($seq);
        } else {
            $id = $this->db->lastInsertId();
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
        $this->execute($this->engine->create_table_sql($name, $data));
    }

    /**
     * Returns the number of tables present in the current database.
     *
     * @throws SCoreException
     */
    public function count_tables(): int
    {
        if (is_null($this->db) || is_null($this->engine)) {
            $this->connect_db();
        }

        if ($this->engine->name === DatabaseDriver::MYSQL) {
            return count(
                $this->get_all("SHOW TABLES")
            );
        } elseif ($this->engine->name === DatabaseDriver::PGSQL) {
            return count(
                $this->get_all("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'")
            );
        } elseif ($this->engine->name === DatabaseDriver::SQLITE) {
            return count(
                $this->get_all("SELECT name FROM sqlite_master WHERE type = 'table'")
            );
        } else {
            throw new SCoreException("Can't count tables for database type {$this->engine->name}");
        }
    }

    public function raw_db(): PDO
    {
        return $this->db;
    }
}
