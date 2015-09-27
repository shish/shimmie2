<?php
/** @privatesection */
// Querylet {{{
class Querylet {
	/** @var string */
	public $sql;
	/** @var array */
	public $variables;

	/**
	 * @param string $sql
	 * @param array $variables
	 */
	public function __construct($sql, $variables=array()) {
		$this->sql = $sql;
		$this->variables = $variables;
	}

	/**
	 * @param \Querylet $querylet
	 */
	public function append($querylet) {
		assert('!is_null($querylet)');
		$this->sql .= $querylet->sql;
		$this->variables = array_merge($this->variables, $querylet->variables);
	}

	/**
	 * @param string $sql
	 */
	public function append_sql($sql) {
		$this->sql .= $sql;
	}

	/**
	 * @param mixed $var
	 */
	public function add_variable($var) {
		$this->variables[] = $var;
	}
}

class TagQuerylet {
	/** @var string  */
	public $tag;
	/** @var bool  */
	public $positive;

	/**
	 * @param string $tag
	 * @param bool $positive
	 */
	public function __construct($tag, $positive) {
		$this->tag = $tag;
		$this->positive = $positive;
	}
}

class ImgQuerylet {
	/** @var \Querylet */
	public $qlet;
	/** @var bool */
	public $positive;

	/**
	 * @param \Querylet $qlet
	 * @param bool $positive
	 */
	public function __construct($qlet, $positive) {
		$this->qlet = $qlet;
		$this->positive = $positive;
	}
}
// }}}
// {{{ db engines
class DBEngine {
	/** @var null|string */
	public $name = null;

	/**
	 * @param \PDO $db
	 */
	public function init($db) {}

	/**
	 * @param string $scoreql
	 * @return string
	 */
	public function scoreql_to_sql($scoreql) {
		return $scoreql;
	}

	/**
	 * @param string $name
	 * @param string $data
	 * @return string
	 */
	public function create_table_sql($name, $data) {
		return 'CREATE TABLE '.$name.' ('.$data.')';
	}
}
class MySQL extends DBEngine {
	/** @var string */
	public $name = "mysql";

	/**
	 * @param \PDO $db
	 */
	public function init($db) {
		$db->exec("SET NAMES utf8;");
	}

	/**
	 * @param string $data
	 * @return string
	 */
	public function scoreql_to_sql($data) {
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

	/**
	 * @param string $name
	 * @param string $data
	 * @return string
	 */
	public function create_table_sql($name, $data) {
		$data = $this->scoreql_to_sql($data);
		$ctes = "ENGINE=InnoDB DEFAULT CHARSET='utf8'";
		return 'CREATE TABLE '.$name.' ('.$data.') '.$ctes;
	}
}
class PostgreSQL extends DBEngine {
	/** @var string */
	public $name = "pgsql";

	/**
	 * @param \PDO $db
	 */
	public function init($db) {
		if(array_key_exists('REMOTE_ADDR', $_SERVER)) {
			$db->exec("SET application_name TO 'shimmie [{$_SERVER['REMOTE_ADDR']}]';");
		}
		else {
			$db->exec("SET application_name TO 'shimmie [local]';");
		}
		$db->exec("SET statement_timeout TO 10000;");
	}

	/**
	 * @param string $data
	 * @return string
	 */
	public function scoreql_to_sql($data) {
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

	/**
	 * @param string $name
	 * @param string $data
	 * @return string
	 */
	public function create_table_sql($name, $data) {
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

	/**
	 * @param \PDO $db
	 */
	public function init($db) {
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

	/**
	 * @param string $data
	 * @return string
	 */
	public function scoreql_to_sql($data) {
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

	/**
	 * @param string $name
	 * @param string $data
	 * @return string
	 */
	public function create_table_sql($name, $data) {
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
	public function get($key);
	public function set($key, $val, $time=0);
	public function delete($key);

	public function get_hits();
	public function get_misses();
}
class NoCache implements CacheEngine {
	public function get($key) {return false;}
	public function set($key, $val, $time=0) {}
	public function delete($key) {}

	public function get_hits() {return 0;}
	public function get_misses() {return 0;}
}
class MemcacheCache implements CacheEngine {
	/** @var \Memcache|null */
	public $memcache=null;
	/** @var int */
	private $hits=0;
	/** @var int */
	private $misses=0;

	/**
	 * @param string $args
	 */
	public function __construct($args) {
		$hp = explode(":", $args);
		if(class_exists("Memcache")) {
			$this->memcache = new Memcache;
			@$this->memcache->pconnect($hp[0], $hp[1]);
		}
		else {
			print "no memcache"; exit;
		}
	}

	/**
	 * @param string $key
	 * @return array|bool|string
	 */
	public function get($key) {
		assert('!is_null($key)');
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

	/**
	 * @param string $key
	 * @param mixed $val
	 * @param int $time
	 */
	public function set($key, $val, $time=0) {
		assert('!is_null($key)');
		$this->memcache->set($key, $val, false, $time);
		if((DEBUG_CACHE === true) || (is_null(DEBUG_CACHE) && @$_GET['DEBUG_CACHE'])) {
			file_put_contents("data/cache.log", "Cache set: $key ($time)\n", FILE_APPEND);
		}
	}

	/**
	 * @param string $key
	 */
	public function delete($key) {
		assert('!is_null($key)');
		$this->memcache->delete($key);
		if((DEBUG_CACHE === true) || (is_null(DEBUG_CACHE) && @$_GET['DEBUG_CACHE'])) {
			file_put_contents("data/cache.log", "Cache delete: $key\n", FILE_APPEND);
		}
	}

	/**
	 * @return int
	 */
	public function get_hits() {return $this->hits;}

	/**
	 * @return int
	 */
	public function get_misses() {return $this->misses;}
}

class APCCache implements CacheEngine {
	var $hits=0, $misses=0;

	public function __construct($args) {
		// $args is not used, but is passed in when APC cache is created.
	}

	public function get($key) {
		assert('!is_null($key)');
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

	public function set($key, $val, $time=0) {
		assert('!is_null($key)');
		apc_store($key, $val, $time);
	}

	public function delete($key) {
		assert('!is_null($key)');
		apc_delete($key);
	}

	public function get_hits() {return $this->hits;}
	public function get_misses() {return $this->misses;}
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
		if(defined("CACHE_DSN") && CACHE_DSN && preg_match("#(memcache|apc)://(.*)#", CACHE_DSN, $matches)) {
			if($matches[1] == "memcache") {
				$this->cache = new MemcacheCache($matches[2]);
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

	/**
	 * @return bool
	 * @throws SCoreException
	 */
	public function commit() {
		if(!is_null($this->db)) {
			if ($this->transaction === true) {
				$this->transaction = false;
				return $this->db->commit();
			}
			else {
				throw new SCoreException("<p><b>Database Transaction Error:</b> Unable to call commit() as there is no transaction currently open.");
			}
		}
	}

	/**
	 * @return bool
	 * @throws SCoreException
	 */
	public function rollback() {
		if(!is_null($this->db)) {
			if ($this->transaction === true) {
				$this->transaction = false;
				return $this->db->rollback();
			}
			else {
				throw new SCoreException("<p><b>Database Transaction Error:</b> Unable to call rollback() as there is no transaction currently open.");
			}
		}
	}

	/**
	 * @param string $input
	 * @return string
	 */
	public function escape($input) {
		if(is_null($this->db)) $this->connect_db();
		return $this->db->Quote($input);
	}

	/**
	 * @param string $input
	 * @return string
	 */
	public function scoreql_to_sql($input) {
		if(is_null($this->engine)) $this->connect_engine();
		return $this->engine->scoreql_to_sql($input);
	}

	/**
	 * @return null|string
	 */
	public function get_driver_name() {
		if(is_null($this->engine)) $this->connect_engine();
		return $this->engine->name;
	}

	private function count_execs($db, $sql, $inputarray) {
		if ((defined('DEBUG_SQL') && DEBUG_SQL === true) || (!defined('DEBUG_SQL') && @$_GET['DEBUG_SQL'])) {
			$fp = @fopen("data/sql.log", "a");
			if($fp) {
				$sql = trim(preg_replace('/\s+/msi', ' ', $sql));
				if(isset($inputarray) && is_array($inputarray) && !empty($inputarray)) {
					fwrite($fp, $sql." -- ".join(", ", $inputarray)."\n");
				}
				else {
					fwrite($fp, $sql."\n");
				}
				fclose($fp);
			}
		}
		if(!is_array($inputarray)) $this->query_count++;
		# handle 2-dimensional input arrays
		else if(is_array(reset($inputarray))) $this->query_count += sizeof($inputarray);
		else $this->query_count++;
	}

	/**
	 * Execute an SQL query and return an PDO result-set.
	 *
	 * @param string $query
	 * @param array $args
	 * @return PDOStatement
	 * @throws SCoreException
	 */
	public function execute($query, $args=array()) {
		try {
			if(is_null($this->db)) $this->connect_db();
			$this->count_execs($this->db, $query, $args);
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
	public function get_all($query, $args=array()) {
		$_start = microtime(true);
		$data = $this->execute($query, $args)->fetchAll();
		$this->dbtime += microtime(true) - $_start;
		return $data;
	}

	/**
	 * Execute an SQL query and return a single row.
	 *
	 * @param string $query
	 * @param array $args
	 * @return array|null
	 */
	public function get_row($query, $args=array()) {
		$_start = microtime(true);
		$row = $this->execute($query, $args)->fetch();
		$this->dbtime += microtime(true) - $_start;
		return $row ? $row : null;
	}

	/**
	 * Execute an SQL query and return the first column of each row.
	 *
	 * @param string $query
	 * @param array $args
	 * @return array
	 */
	public function get_col($query, $args=array()) {
		$_start = microtime(true);
		$stmt = $this->execute($query, $args);
		$res = array();
		foreach($stmt as $row) {
			$res[] = $row[0];
		}
		$this->dbtime += microtime(true) - $_start;
		return $res;
	}

	/**
	 * Execute an SQL query and return the the first row => the second rown.
	 *
	 * @param string $query
	 * @param array $args
	 * @return array
	 */
	public function get_pairs($query, $args=array()) {
		$_start = microtime(true);
		$stmt = $this->execute($query, $args);
		$res = array();
		foreach($stmt as $row) {
			$res[$row[0]] = $row[1];
		}
		$this->dbtime += microtime(true) - $_start;
		return $res;
	}

	/**
	 * Execute an SQL query and return a single value.
	 *
	 * @param string $query
	 * @param array $args
	 * @return mixed
	 */
	public function get_one($query, $args=array()) {
		$_start = microtime(true);
		$row = $this->execute($query, $args)->fetch();
		$this->dbtime += microtime(true) - $_start;
		return $row[0];
	}

	/**
	 * Get the ID of the last inserted row.
	 *
	 * @param string|null $seq
	 * @return int
	 */
	public function get_last_insert_id($seq) {
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
	public function create_table($name, $data) {
		if(is_null($this->engine)) { $this->connect_engine(); }
		$data = trim($data, ", \t\n\r\0\x0B");  // mysql doesn't like trailing commas
		$this->execute($this->engine->create_table_sql($name, $data));
	}

	/**
	 * Returns the number of tables present in the current database.
	 *
	 * @return int|null
	 */
	public function count_tables() {

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
			// Hard to find a universal way to do this...
			return NULL;
		}
	}
}

class MockDatabase extends Database {
	/** @var int */
	var $query_id = 0;
	/** @var array */
	var $responses = array();
	/** @var \NoCache|null  */
	var $cache = null;

	/**
	 * @param array $responses
	 */
	public function __construct($responses = array()) {
		$this->cache = new NoCache();
		$this->responses = $responses;
	}

	/**
	 * @param string $query
	 * @param array $params
	 * @return PDOStatement
	 */
	public function execute($query, $params=array()) {
		log_debug("mock-database",
			"QUERY: " . $query .
			"\nARGS: " . var_export($params, true) .
			"\nRETURN: " . var_export($this->responses[$this->query_id], true)
		);
		return $this->responses[$this->query_id++];
	}

	/**
	 * @param string $query
	 * @param array $args
	 * @return array|PDOStatement
	 */
	public function get_all($query, $args=array()) {return $this->execute($query, $args);}

	/**
	 * @param string $query
	 * @param array $args
	 * @return mixed|null|PDOStatement
	 */
	public function get_row($query, $args=array()) {return $this->execute($query, $args);}

	/**
	 * @param string $query
	 * @param array $args
	 * @return array|PDOStatement
	 */
	public function get_col($query, $args=array()) {return $this->execute($query, $args);}

	/**
	 * @param string $query
	 * @param array $args
	 * @return array|PDOStatement
	 */
	public function get_pairs($query, $args=array()) {return $this->execute($query, $args);}

	/**
	 * @param string $query
	 * @param array $args
	 * @return mixed|PDOStatement
	 */
	public function get_one($query, $args=array()) {return $this->execute($query, $args);}

	/**
	 * @param null|string $seq
	 * @return int|string
	 */
	public function get_last_insert_id($seq) {return $this->query_id;}

	/**
	 * @param string $sql
	 * @return string
	 */
	public function scoreql_to_sql($sql) {return $sql;}
	public function create_table($name, $def) {}
	public function connect_engine() {}
}

