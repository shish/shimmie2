<?php
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

    private function connect_db(): void
    {
        # FIXME: detect ADODB URI, automatically translate PDO DSN

        /*
         * Why does the abstraction layer act differently depending on the
         * back-end? Because PHP is deliberately retarded.
         *
         * http://stackoverflow.com/questions/237367
         */
        $matches = [];
        $db_user=null;
        $db_pass=null;
        if (preg_match("/user=([^;]*)/", DATABASE_DSN, $matches)) {
            $db_user=$matches[1];
        }
        if (preg_match("/password=([^;]*)/", DATABASE_DSN, $matches)) {
            $db_pass=$matches[1];
        }

        $db_params = [
            PDO::ATTR_PERSISTENT => DATABASE_KA,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];
        $this->db = new PDO(DATABASE_DSN, $db_user, $db_pass, $db_params);

        $this->connect_engine();
        $this->engine->init($this->db);

        $this->beginTransaction();
    }

    private function connect_engine(): void
    {
        if (preg_match("/^([^:]*)/", DATABASE_DSN, $matches)) {
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
            die('Unknown PDO driver: '.$db_proto);
        }
    }

    public function beginTransaction(): void
    {
        if ($this->transaction === false) {
            $this->db->beginTransaction();
            $this->transaction = true;
        }
    }

    public function commit(): bool
    {
        if (!is_null($this->db)) {
            if ($this->transaction === true) {
                $this->transaction = false;
                return $this->db->commit();
            } else {
                throw new SCoreException("<p><b>Database Transaction Error:</b> Unable to call commit() as there is no transaction currently open.");
            }
        } else {
            throw new SCoreException("<p><b>Database Transaction Error:</b> Unable to call commit() as there is no connection currently open.");
        }
    }

    public function rollback(): bool
    {
        if (!is_null($this->db)) {
            if ($this->transaction === true) {
                $this->transaction = false;
                return $this->db->rollback();
            } else {
                throw new SCoreException("<p><b>Database Transaction Error:</b> Unable to call rollback() as there is no transaction currently open.");
            }
        } else {
            throw new SCoreException("<p><b>Database Transaction Error:</b> Unable to call rollback() as there is no connection currently open.");
        }
    }

    public function escape(string $input): string
    {
        if (is_null($this->db)) {
            $this->connect_db();
        }
        return $this->db->Quote($input);
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

    public function execute(string $query, array $args=[], bool $scoreql = false): PDOStatement
    {
        try {
            if($scoreql===true) {
                $query = $this->scoreql_to_sql($query);
            }

            if (is_null($this->db)) {
                $this->connect_db();
            }
            $stmt = $this->db->prepare(
                "-- " . str_replace("%2F", "/", urlencode(@$_GET['q'])). "\n" .
                $query
            );
            assert(!is_bool($stmt));
            // $stmt = $this->db->prepare($query);
            if (!array_key_exists(0, $args)) {
                foreach ($args as $name=>$value) {
                    if (is_int($value)) {
                        $stmt->bindValue(':'.$name, $value, PDO::PARAM_INT);
                    } else {
                        $stmt->bindValue(':'.$name, $value, PDO::PARAM_STR);
                    }
                }
                $stmt->execute();
            } else {
                $stmt->execute($args);
            }
            return $stmt;
        } catch (PDOException $pdoe) {
            throw new SCoreException($pdoe->getMessage()."<p><b>Query:</b> ".$query);
        }
    }

    /**
     * Execute an SQL query and return a 2D array.
     */
    public function get_all(string $query, array $args=[], bool $scoreql = false): array
    {
        if($scoreql===true) {
            $query = $this->scoreql_to_sql($query);
        }

        $_start = microtime(true);
        $data = $this->execute($query, $args)->fetchAll();
        $this->count_time("get_all", $_start, $query, $args);
        return $data;
    }

    /**
     * Execute an SQL query and return a iterable object for use with generators.
     */
    public function get_all_iterable(string $query, array $args=[], bool $scoreql = false): PDOStatement
    {
        if($scoreql===true) {
            $query = $this->scoreql_to_sql($query);
        }
        $_start = microtime(true);
        $data = $this->execute($query, $args);
        $this->count_time("get_all_iterable", $_start, $query, $args);
        return $data;
    }

    /**
     * Execute an SQL query and return a single row.
     */
    public function get_row(string $query, array $args=[], bool $scoreql = false): ?array
    {
        if($scoreql===true) {
            $query = $this->scoreql_to_sql($query);
        }
        $_start = microtime(true);
        $row = $this->execute($query, $args)->fetch();
        $this->count_time("get_row", $_start, $query, $args);
        return $row ? $row : null;
    }


    /**
     * Execute an SQL query and return a boolean based on whether it returns a result
     */
    public function exists(string $query, array $args=[], bool $scoreql = false): bool
    {
        if($scoreql===true) {
            $query = $this->scoreql_to_sql($query);
        }
        $_start = microtime(true);
        $result = $this->execute($query, $args);
        $this->count_time("exists", $_start, $query, $args);
        return $result->rowCount()>0;
    }

    /**
     * Execute an SQL query and return the first column of each row.
     */
    public function get_col(string $query, array $args=[], bool $scoreql = false): array
    {
        if($scoreql===true) {
            $query = $this->scoreql_to_sql($query);
        }
        $_start = microtime(true);
        $res = $this->execute($query, $args)->fetchAll(PDO::FETCH_COLUMN);
        $this->count_time("get_col", $_start, $query, $args);
        return $res;
    }

    /**
     * Execute an SQL query and return the first column of each row as a single iterable object.
     */
    public function get_col_iterable(string $query, array $args=[], bool $scoreql = false): Generator
    {
        if($scoreql===true) {
            $query = $this->scoreql_to_sql($query);
        }
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
    public function get_pairs(string $query, array $args=[], bool $scoreql = false): array
    {
        if($scoreql===true) {
            $query = $this->scoreql_to_sql($query);
        }
        $_start = microtime(true);
        $res = $this->execute($query, $args)->fetchAll(PDO::FETCH_KEY_PAIR);
        $this->count_time("get_pairs", $_start, $query, $args);
        return $res;
    }

    /**
     * Execute an SQL query and return a single value.
     */
    public function get_one(string $query, array $args=[], bool $scoreql = false)
    {
        if($scoreql===true) {
            $query = $this->scoreql_to_sql($query);
        }
        $_start = microtime(true);
        $row = $this->execute($query, $args)->fetch();
        $this->count_time("get_one", $_start, $query, $args);
        return $row[0];
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
}

class MockDatabase extends Database
{
    /** @var int */
    private $query_id = 0;
    /** @var array */
    private $responses = [];

    public function __construct(array $responses = [])
    {
        $this->responses = $responses;
    }

    public function execute(string $query, array $params=[], bool $scoreql = false): PDOStatement
    {
        log_debug(
            "mock-database",
            "QUERY: " . $query .
            "\nARGS: " . var_export($params, true) .
            "\nRETURN: " . var_export($this->responses[$this->query_id], true)
        );
        return $this->responses[$this->query_id++];
    }

    public function _execute(string $query, array $params=[])
    {
        log_debug(
            "mock-database",
            "QUERY: " . $query .
            "\nARGS: " . var_export($params, true) .
            "\nRETURN: " . var_export($this->responses[$this->query_id], true)
        );
        return $this->responses[$this->query_id++];
    }

    public function get_all(string $query, array $args=[], bool $scoreql = false): array
    {
        return $this->_execute($query, $args);
    }
    public function get_row(string $query, array $args=[], bool $scoreql = false): ?array
    {
        return $this->_execute($query, $args);
    }
    public function get_col(string $query, array $args=[], bool $scoreql = false): array
    {
        return $this->_execute($query, $args);
    }
    public function get_pairs(string $query, array $args=[], bool $scoreql = false): array
    {
        return $this->_execute($query, $args);
    }
    public function get_one(string $query, array $args=[], bool $scoreql = false)
    {
        return $this->_execute($query, $args);
    }

    public function get_last_insert_id(string $seq): int
    {
        return $this->query_id;
    }

    public function scoreql_to_sql(string $sql): string
    {
        return $sql;
    }

    public function create_table(string $name, string $def): void
    {
    }

    public function connect_engine(): void
    {
    }
}
