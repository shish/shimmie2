<?php
/** @privatesection */
// Querylet {{{
class Querylet {
	/** @var string */
	public $sql;
	/** @var array */
	public $variables;

	public function __construct(string $sql, array $variables=array()) {
		$this->sql = $sql;
		$this->variables = $variables;
	}

	public function append(Querylet $querylet) {
		$this->sql .= $querylet->sql;
		$this->variables = array_merge($this->variables, $querylet->variables);
	}

	public function append_sql(string $sql) {
		$this->sql .= $sql;
	}

	public function add_variable($var) {
		$this->variables[] = $var;
	}
}

class TagQuerylet {
	/** @var string  */
	public $tag;
	/** @var bool  */
	public $positive;

	public function __construct(string $tag, bool $positive) {
		$this->tag = $tag;
		$this->positive = $positive;
	}
}

class ImgQuerylet {
	/** @var \Querylet */
	public $qlet;
	/** @var bool */
	public $positive;

	public function __construct(Querylet $qlet, bool $positive) {
		$this->qlet = $qlet;
		$this->positive = $positive;
	}
}
// }}}
// {{{ db engines
class DBEngine {
	/** @var null|string */
	public $name = null;

	public function init(PDO $db) {}

	public function scoreql_to_sql(string $scoreql): string {
		return $scoreql;
	}

	public function create_table_sql(string $name, string $data): string {
		return 'CREATE TABLE '.$name.' ('.$data.')';
	}
}
class MySQL extends DBEngine {
	/** @var string */
	public $name = "mysql";

	public function init(PDO $db) {
		$db->exec("SET NAMES utf8;");
	}

	public function scoreql_to_sql(string $data): string {
		$data = str_replace("SCORE_AIPK", "INTEGER PRIMARY KEY auto_increment", $data);
		$data = str_replace("SCORE_INET", "VARCHAR(45)", $data);
		$data = str_replace("SCORE_BOOL_Y", "'Y'", $data);
		$data = str_replace("SCORE_BOOL_N", "'N'", $data);
		$data = str_replace("SCORE_BOOL", "ENUM('Y', 'N')", $data);
		$data = str_replace("SCORE_DATETIME", "DATETIME", $data);
		$data = str_replace("SCORE_NOW", "\"1970-01-01\"", $data);
		$data = str_replace("SCORE_STRNORM", "", $data);
		$data = str_replace("SCORE_ILIKE", "LIKE", $data);
		return $data;
	}

	public function create_table_sql(string $name, string $data): string {
		$data = $this->scoreql_to_sql($data);
		$ctes = "ENGINE=InnoDB DEFAULT CHARSET='utf8'";
		return 'CREATE TABLE '.$name.' ('.$data.') '.$ctes;
	}
}
class PostgreSQL extends DBEngine {
	/** @var string */
	public $name = "pgsql";

	public function init(PDO $db) {
		if(array_key_exists('REMOTE_ADDR', $_SERVER)) {
			$db->exec("SET application_name TO 'shimmie [{$_SERVER['REMOTE_ADDR']}]';");
		}
		else {
			$db->exec("SET application_name TO 'shimmie [local]';");
		}
		$db->exec("SET statement_timeout TO 10000;");
	}

	public function scoreql_to_sql(string $data): string {
		$data = str_replace("SCORE_AIPK", "SERIAL PRIMARY KEY", $data);
		$data = str_replace("SCORE_INET", "INET", $data);
		$data = str_replace("SCORE_BOOL_Y", "'t'", $data);
		$data = str_replace("SCORE_BOOL_N", "'f'", $data);
		$data = str_replace("SCORE_BOOL", "BOOL", $data);
		$data = str_replace("SCORE_DATETIME", "TIMESTAMP", $data);
		$data = str_replace("SCORE_NOW", "current_timestamp", $data);
		$data = str_replace("SCORE_STRNORM", "lower", $data);
		$data = str_replace("SCORE_ILIKE", "ILIKE", $data);
		return $data;
	}

	public function create_table_sql(string $name, string $data): string {
		$data = $this->scoreql_to_sql($data);
		return "CREATE TABLE $name ($data)";
	}
}

// shimmie functions for export to sqlite
function _unix_timestamp($date) { return strtotime($date); }
function _now() { return date("Y-m-d h:i:s"); }
function _floor($a) { return floor($a); }
function _log($a, $b=null) {
	if(is_null($b)) return log($a);
	else return log($a, $b);
}
function _isnull($a) { return is_null($a); }
function _md5($a) { return md5($a); }
function _concat($a, $b) { return $a . $b; }
function _lower($a) { return strtolower($a); }
function _rand() { return rand(); }
function _ln($n) { return log($n); }

class SQLite extends DBEngine {
	/** @var string  */
	public $name = "sqlite";

	public function init(PDO $db) {
		ini_set('sqlite.assoc_case', 0);
		$db->exec("PRAGMA foreign_keys = ON;");
		$db->sqliteCreateFunction('UNIX_TIMESTAMP', '_unix_timestamp', 1);
		$db->sqliteCreateFunction('now', '_now', 0);
		$db->sqliteCreateFunction('floor', '_floor', 1);
		$db->sqliteCreateFunction('log', '_log');
		$db->sqliteCreateFunction('isnull', '_isnull', 1);
		$db->sqliteCreateFunction('md5', '_md5', 1);
		$db->sqliteCreateFunction('concat', '_concat', 2);
		$db->sqliteCreateFunction('lower', '_lower', 1);
		$db->sqliteCreateFunction('rand', '_rand', 0);
		$db->sqliteCreateFunction('ln', '_ln', 1);
	}

	public function scoreql_to_sql(string $data): string {
		$data = str_replace("SCORE_AIPK", "INTEGER PRIMARY KEY", $data);
		$data = str_replace("SCORE_INET", "VARCHAR(45)", $data);
		$data = str_replace("SCORE_BOOL_Y", "'Y'", $data);
		$data = str_replace("SCORE_BOOL_N", "'N'", $data);
		$data = str_replace("SCORE_BOOL", "CHAR(1)", $data);
		$data = str_replace("SCORE_NOW", "\"1970-01-01\"", $data);
		$data = str_replace("SCORE_STRNORM", "lower", $data);
		$data = str_replace("SCORE_ILIKE", "LIKE", $data);
		return $data;
	}

	public function create_table_sql(string $name, string $data): string {
		$data = $this->scoreql_to_sql($data);
		$cols = array();
		$extras = "";
		foreach(explode(",", $data) as $bit) {
			$matches = array();
			if(preg_match("/(UNIQUE)? ?INDEX\s*\((.*)\)/", $bit, $matches)) {
				$uni = $matches[1];
				$col = $matches[2];
				$extras .= "CREATE $uni INDEX {$name}_{$col} ON {$name}({$col});";
			}
			else {
				$cols[] = $bit;
			}
		}
		$cols_redone = implode(", ", $cols);
		return "CREATE TABLE $name ($cols_redone); $extras";
	}
}
// }}}
// {{{ cache engines
interface CacheEngine {

	public function get(string $key);
	public function set(string $key, $val, int $time=0);
	public function delete(string $key);
	public function get_hits(): int;
	public function get_misses(): int;
}
class NoCache implements CacheEngine {
	public function get(string $key) {return false;}
	public function set(string $key, $val, int $time=0) {}
	public function delete(string $key) {}

	public function get_hits(): int {return 0;}
	public function get_misses(): int {return 0;}
}
class MemcacheCache implements CacheEngine {
	/** @var \Memcache|null */
	public $memcache=null;
	/** @var int */
	private $hits=0;
	/** @var int */
	private $misses=0;

	public function __construct(string $args) {
		$hp = explode(":", $args);
		$this->memcache = new Memcache;
		@$this->memcache->pconnect($hp[0], $hp[1]);
	}

	public function get(string $key) {
		$val = $this->memcache->get($key);
		if((DEBUG_CACHE === true) || (is_null(DEBUG_CACHE) && @$_GET['DEBUG_CACHE'])) {
			$hit = $val === false ? "miss" : "hit";
			file_put_contents("data/cache.log", "Cache $hit: $key\n", FILE_APPEND);
		}
		if($val !== false) {
			$this->hits++;
			return $val;
		}
		else {
			$this->misses++;
			return false;
		}
	}

	public function set(string $key, $val, int $time=0) {
		$this->memcache->set($key, $val, false, $time);
		if((DEBUG_CACHE === true) || (is_null(DEBUG_CACHE) && @$_GET['DEBUG_CACHE'])) {
			file_put_contents("data/cache.log", "Cache set: $key ($time)\n", FILE_APPEND);
		}
	}

	public function delete(string $key) {
		$this->memcache->delete($key);
		if((DEBUG_CACHE === true) || (is_null(DEBUG_CACHE) && @$_GET['DEBUG_CACHE'])) {
			file_put_contents("data/cache.log", "Cache delete: $key\n", FILE_APPEND);
		}
	}

	public function get_hits(): int {return $this->hits;}
	public function get_misses(): int {return $this->misses;}
}
class MemcachedCache implements CacheEngine {
	/** @var \Memcached|null */
	public $memcache=null;
	/** @var int */
	private $hits=0;
	/** @var int */
	private $misses=0;

	public function __construct(string $args) {
		$hp = explode(":", $args);
		$this->memcache = new Memcached;
		#$this->memcache->setOption(Memcached::OPT_COMPRESSION, False);
		#$this->memcache->setOption(Memcached::OPT_SERIALIZER, Memcached::SERIALIZER_PHP);
		#$this->memcache->setOption(Memcached::OPT_PREFIX_KEY, phpversion());
		$this->memcache->addServer($hp[0], $hp[1]);
	}

	public function get(string $key) {
		$key = urlencode($key);

		$val = $this->memcache->get($key);
		$res = $this->memcache->getResultCode();

		if((DEBUG_CACHE === true) || (is_null(DEBUG_CACHE) && @$_GET['DEBUG_CACHE'])) {
			$hit = $res == Memcached::RES_SUCCESS ? "hit" : "miss";
			file_put_contents("data/cache.log", "Cache $hit: $key\n", FILE_APPEND);
		}
		if($res == Memcached::RES_SUCCESS) {
			$this->hits++;
			return $val;
		}
		else if($res == Memcached::RES_NOTFOUND) {
			$this->misses++;
			return false;
		}
		else {
			error_log("Memcached error during get($key): $res");
			return false;
		}
	}

	public function set(string $key, $val, int $time=0) {
		$key = urlencode($key);

		$this->memcache->set($key, $val, $time);
		$res = $this->memcache->getResultCode();
		if((DEBUG_CACHE === true) || (is_null(DEBUG_CACHE) && @$_GET['DEBUG_CACHE'])) {
			file_put_contents("data/cache.log", "Cache set: $key ($time)\n", FILE_APPEND);
		}
		if($res != Memcached::RES_SUCCESS) {
			error_log("Memcached error during set($key): $res");
		}
	}

	public function delete(string $key) {
		$key = urlencode($key);

		$this->memcache->delete($key);
		$res = $this->memcache->getResultCode();
		if((DEBUG_CACHE === true) || (is_null(DEBUG_CACHE) && @$_GET['DEBUG_CACHE'])) {
			file_put_contents("data/cache.log", "Cache delete: $key\n", FILE_APPEND);
		}
		if($res != Memcached::RES_SUCCESS && $res != Memcached::RES_NOTFOUND) {
			error_log("Memcached error during delete($key): $res");
		}
	}

	public function get_hits(): int {return $this->hits;}
	public function get_misses(): int {return $this->misses;}
}

class APCCache implements CacheEngine {
	public $hits=0, $misses=0;

	public function __construct(string $args) {
		// $args is not used, but is passed in when APC cache is created.
	}

	public function get(string $key) {
		$val = apc_fetch($key);
		if($val) {
			$this->hits++;
			return $val;
		}
		else {
			$this->misses++;
			return false;
		}
	}

	public function set(string $key, $val, int $time=0) {
		apc_store($key, $val, $time);
	}

	public function delete(string $key) {
		apc_delete($key);
	}

	public function get_hits(): int {return $this->hits;}
	public function get_misses(): int {return $this->misses;}
}
// }}}
/** @publicsection */

/**
 * A class for controlled database access
 */
class Database {
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
	 * The currently active cache engine.
	 * @var CacheEngine|null
	 */
	public $cache = null;

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

	/**
	 * For now, only connect to the cache, as we will pretty much certainly
	 * need it. There are some pages where all the data is in cache, so the
	 * DB connection is on-demand.
	 */
	public function __construct() {
		$this->connect_cache();
	}

	private function connect_cache() {
		$matches = array();
		if(defined("CACHE_DSN") && CACHE_DSN && preg_match("#(memcache|memcached|apc)://(.*)#", CACHE_DSN, $matches)) {
			if($matches[1] == "memcache") {
				$this->cache = new MemcacheCache($matches[2]);
			}
			else if($matches[1] == "memcached") {
				$this->cache = new MemcachedCache($matches[2]);
			}
			else if($matches[1] == "apc") {
				$this->cache = new APCCache($matches[2]);
			}
		}
		else {
			$this->cache = new NoCache();
		}
	}

	private function connect_db() {
		# FIXME: detect ADODB URI, automatically translate PDO DSN

		/*
		 * Why does the abstraction layer act differently depending on the
		 * back-end? Because PHP is deliberately retarded.
		 *
		 * http://stackoverflow.com/questions/237367
		 */
		$matches = array(); $db_user=null; $db_pass=null;
		if(preg_match("/user=([^;]*)/", DATABASE_DSN, $matches)) $db_user=$matches[1];
		if(preg_match("/password=([^;]*)/", DATABASE_DSN, $matches)) $db_pass=$matches[1];

		// https://bugs.php.net/bug.php?id=70221
		$ka = DATABASE_KA;
		if(version_compare(PHP_VERSION, "6.9.9") == 1 && $this->get_driver_name() == "sqlite") {
			$ka = false;
		}

		$db_params = array(
			PDO::ATTR_PERSISTENT => $ka,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		);
		$this->db = new PDO(DATABASE_DSN, $db_user, $db_pass, $db_params);

		$this->connect_engine();
		$this->engine->init($this->db);

		$this->beginTransaction();
	}

	private function connect_engine() {
		if(preg_match("/^([^:]*)/", DATABASE_DSN, $matches)) $db_proto=$matches[1];
		else throw new SCoreException("Can't figure out database engine");

		if($db_proto === "mysql") {
			$this->engine = new MySQL();
		}
		else if($db_proto === "pgsql") {
			$this->engine = new PostgreSQL();
		}
		else if($db_proto === "sqlite") {
			$this->engine = new SQLite();
		}
		else {
			die('Unknown PDO driver: '.$db_proto);
		}
	}

	public function beginTransaction() {
		if ($this->transaction === false) {
			$this->db->beginTransaction();
			$this->transaction = true;
		}
	}

	public function commit(): bool {
		if(!is_null($this->db)) {
			if ($this->transaction === true) {
				$this->transaction = false;
				return $this->db->commit();
			}
			else {
				throw new SCoreException("<p><b>Database Transaction Error:</b> Unable to call commit() as there is no transaction currently open.");
			}
		}
		else {
			throw new SCoreException("<p><b>Database Transaction Error:</b> Unable to call commit() as there is no connection currently open.");
		}
	}

	public function rollback(): bool {
		if(!is_null($this->db)) {
			if ($this->transaction === true) {
				$this->transaction = false;
				return $this->db->rollback();
			}
			else {
				throw new SCoreException("<p><b>Database Transaction Error:</b> Unable to call rollback() as there is no transaction currently open.");
			}
		}
		else {
			throw new SCoreException("<p><b>Database Transaction Error:</b> Unable to call rollback() as there is no connection currently open.");
		}
	}

	public function escape(string $input): string {
		if(is_null($this->db)) $this->connect_db();
		return $this->db->Quote($input);
	}

	public function scoreql_to_sql(string $input): string {
		if(is_null($this->engine)) $this->connect_engine();
		return $this->engine->scoreql_to_sql($input);
	}

	public function get_driver_name(): string {
		if(is_null($this->engine)) $this->connect_engine();
		return $this->engine->name;
	}

	private function count_execs(string $sql, array $inputarray) {
		if((DEBUG_SQL === true) || (is_null(DEBUG_SQL) && @$_GET['DEBUG_SQL'])) {
			$sql = trim(preg_replace('/\s+/msi', ' ', $sql));
			if(isset($inputarray) && is_array($inputarray) && !empty($inputarray)) {
				$text = $sql." -- ".join(", ", $inputarray)."\n";
			}
			else {
				$text = $sql."\n";
			}
			file_put_contents("data/sql.log", $text, FILE_APPEND);
		}
		if(!is_array($inputarray)) $this->query_count++;
		# handle 2-dimensional input arrays
		else if(is_array(reset($inputarray))) $this->query_count += sizeof($inputarray);
		else $this->query_count++;
	}

	private function count_time(string $method, float $start) {
		if((DEBUG_SQL === true) || (is_null(DEBUG_SQL) && @$_GET['DEBUG_SQL'])) {
			$text = $method.":".(microtime(true) - $start)."\n";
			file_put_contents("data/sql.log", $text, FILE_APPEND);
		}
		$this->dbtime += microtime(true) - $start;
	}

	public function execute(string $query, array $args=array()): PDOStatement {
		try {
			if(is_null($this->db)) $this->connect_db();
			$this->count_execs($query, $args);
			$stmt = $this->db->prepare($query);
			if (!array_key_exists(0, $args)) {
				foreach($args as $name=>$value) {
					if(is_numeric($value)) {
						$stmt->bindValue(':'.$name, $value, PDO::PARAM_INT);
					}
					else {
						$stmt->bindValue(':'.$name, $value, PDO::PARAM_STR);
					}
				}
				$stmt->execute();
			}
			else {
				$stmt->execute($args);
			}
			return $stmt;
		}
		catch(PDOException $pdoe) {
			throw new SCoreException($pdoe->getMessage()."<p><b>Query:</b> ".$query);
		}
	}

	/**
	 * Execute an SQL query and return a 2D array.
	 *
	 * @param string $query
	 * @param array $args
	 * @return array
	 */
	public function get_all(string $query, array $args=array()): array {
		$_start = microtime(true);
		$data = $this->execute($query, $args)->fetchAll();
		$this->count_time("get_all", $_start);
		return $data;
	}

	/**
	 * Execute an SQL query and return a single row.
	 *
	 * @param string $query
	 * @param array $args
	 * @return array|null
	 */
	public function get_row(string $query, array $args=array()) {
		$_start = microtime(true);
		$row = $this->execute($query, $args)->fetch();
		$this->count_time("get_row", $_start);
		return $row ? $row : null;
	}

	/**
	 * Execute an SQL query and return the first column of each row.
	 *
	 * @param string $query
	 * @param array $args
	 * @return array
	 */
	public function get_col(string $query, array $args=array()): array {
		$_start = microtime(true);
		$stmt = $this->execute($query, $args);
		$res = array();
		foreach($stmt as $row) {
			$res[] = $row[0];
		}
		$this->count_time("get_col", $_start);
		return $res;
	}

	/**
	 * Execute an SQL query and return the the first row => the second row.
	 *
	 * @param string $query
	 * @param array $args
	 * @return array
	 */
	public function get_pairs(string $query, array $args=array()): array {
		$_start = microtime(true);
		$stmt = $this->execute($query, $args);
		$res = array();
		foreach($stmt as $row) {
			$res[$row[0]] = $row[1];
		}
		$this->count_time("get_pairs", $_start);
		return $res;
	}

	/**
	 * Execute an SQL query and return a single value.
	 *
	 * @param string $query
	 * @param array $args
	 * @return mixed|null
	 */
	public function get_one(string $query, array $args=array()) {
		$_start = microtime(true);
		$row = $this->execute($query, $args)->fetch();
		$this->count_time("get_one", $_start);
		return $row[0];
	}

	/**
	 * Get the ID of the last inserted row.
	 *
	 * @param string|null $seq
	 * @return int
	 */
	public function get_last_insert_id(string $seq): int {
		if($this->engine->name == "pgsql") {
			return $this->db->lastInsertId($seq);
		}
		else {
			return $this->db->lastInsertId();
		}
	}

	/**
	 * Create a table from pseudo-SQL.
	 *
	 * @param string $name
	 * @param string $data
	 */
	public function create_table(string $name, string $data) {
		if(is_null($this->engine)) { $this->connect_engine(); }
		$data = trim($data, ", \t\n\r\0\x0B");  // mysql doesn't like trailing commas
		$this->execute($this->engine->create_table_sql($name, $data));
	}

	/**
	 * Returns the number of tables present in the current database.
	 *
	 * @return int
	 * @throws SCoreException
	 */
	public function count_tables(): int {
		if(is_null($this->db) || is_null($this->engine)) $this->connect_db();

		if($this->engine->name === "mysql") {
			return count(
				$this->get_all("SHOW TABLES")
			);
		} else if ($this->engine->name === "pgsql") {
			return count(
				$this->get_all("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'")
			);
		} else if ($this->engine->name === "sqlite") {
			return count(
				$this->get_all("SELECT name FROM sqlite_master WHERE type = 'table'")
			);
		} else {
			throw new SCoreException("Can't count tables for database type {$this->engine->name}");
		}
	}
}

class MockDatabase extends Database {
	/** @var int */
	private $query_id = 0;
	/** @var array */
	private $responses = array();
	/** @var \NoCache|null  */
	public $cache = null;

	public function __construct(array $responses = array()) {
		$this->cache = new NoCache();
		$this->responses = $responses;
	}

	public function execute(string $query, array $params=array()): PDOStatement {
		log_debug("mock-database",
			"QUERY: " . $query .
			"\nARGS: " . var_export($params, true) .
			"\nRETURN: " . var_export($this->responses[$this->query_id], true)
		);
		return $this->responses[$this->query_id++];
	}
	public function _execute(string $query, array $params=array()) {
		log_debug("mock-database",
			"QUERY: " . $query .
			"\nARGS: " . var_export($params, true) .
			"\nRETURN: " . var_export($this->responses[$this->query_id], true)
		);
		return $this->responses[$this->query_id++];
	}

	public function get_all(string $query, array $args=array()): array {return $this->_execute($query, $args);}
	public function get_row(string $query, array $args=array()) {return $this->_execute($query, $args);}
	public function get_col(string $query, array $args=array()): array {return $this->_execute($query, $args);}
	public function get_pairs(string $query, array $args=array()): array {return $this->_execute($query, $args);}
	public function get_one(string $query, array $args=array()) {return $this->_execute($query, $args);}

	public function get_last_insert_id(string $seq): int {return $this->query_id;}

	public function scoreql_to_sql(string $sql): string {return $sql;}
	public function create_table(string $name, string $def) {}
	public function connect_engine() {}
}

