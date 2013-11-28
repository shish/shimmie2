<?php
/** @privatesection */
// Querylet {{{
class Querylet {
	var $sql;
	var $variables;

	public function Querylet($sql, $variables=array()) {
		$this->sql = $sql;
		$this->variables = $variables;
	}

	public function append($querylet) {
		assert(!is_null($querylet));
		$this->sql .= $querylet->sql;
		$this->variables = array_merge($this->variables, $querylet->variables);
	}

	public function append_sql($sql) {
		$this->sql .= $sql;
	}

	public function add_variable($var) {
		$this->variables[] = $var;
	}
}
class TagQuerylet {
	var $tag;
	var $positive;

	public function TagQuerylet($tag, $positive) {
		$this->tag = $tag;
		$this->positive = $positive;
	}
}
class ImgQuerylet {
	var $qlet;
	var $positive;

	public function ImgQuerylet($qlet, $positive) {
		$this->qlet = $qlet;
		$this->positive = $positive;
	}
}
// }}}
// {{{ db engines
class DBEngine {
	var $name = null;

	public function init($db) {}

	public function scoreql_to_sql($scoreql) {
		return $scoreql;
	}

	public function create_table_sql($name, $data) {
		return 'CREATE TABLE '.$name.' ('.$data.')';
	}
}
class MySQL extends DBEngine {
	var $name = "mysql";

	public function init($db) {
		$db->exec("SET NAMES utf8;");
	}

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

	public function create_table_sql($name, $data) {
		$data = $this->scoreql_to_sql($data);
		$ctes = "ENGINE=InnoDB DEFAULT CHARSET='utf8'";
		return 'CREATE TABLE '.$name.' ('.$data.') '.$ctes;
	}
}
class PostgreSQL extends DBEngine {
	var $name = "pgsql";

	public function init($db) {
		$db->exec("SET application_name TO 'shimmie [{$_SERVER['REMOTE_ADDR']}]';");
	}

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

	public function create_table_sql($name, $data) {
		$data = $this->scoreql_to_sql($data);
		return 'CREATE TABLE '.$name.' ('.$data.')';
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

class SQLite extends DBEngine {
	var $name = "sqlite";

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
	}

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

	public function create_table_sql($name, $data) {
		$data = $this->scoreql_to_sql($data);
		$cols = array();
		$extras = "";
		foreach(explode(",", $data) as $bit) {
			$matches = array();
			if(preg_match("/INDEX\s*\((.*)\)/", $bit, $matches)) {
				$col = $matches[1];
				$extras .= "CREATE INDEX {$name}_{$col} on {$name}({$col});";
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
	var $memcache=null, $hits=0, $misses=0;

	public function __construct($args) {
		$hp = explode(":", $args);
		if(class_exists("Memcache")) {
			$this->memcache = new Memcache;
			@$this->memcache->pconnect($hp[0], $hp[1]);
		}
	}

	public function get($key) {
		assert(!is_null($key));
		if((DEBUG_CACHE === true) || (is_null(DEBUG_CACHE) && @$_GET['DEBUG_CACHE'])) {
			file_put_contents("data/cache.log", "Cache lookup: $key\n", FILE_APPEND);
		}
		$val = $this->memcache->get($key);
		if($val !== false) {
			$this->hits++;
			return $val;
		}
		else {
			$this->misses++;
			return false;
		}
	}

	public function set($key, $val, $time=0) {
		assert(!is_null($key));
		$this->memcache->set($key, $val, false, $time);
	}

	public function delete($key) {
		assert(!is_null($key));
		$this->memcache->delete($key);
	}

	public function get_hits() {return $this->hits;}
	public function get_misses() {return $this->misses;}
}
class APCCache implements CacheEngine {
	var $hits=0, $misses=0;

	public function __construct($args) {}

	public function get($key) {
		assert(!is_null($key));
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
		assert(!is_null($key));
		apc_store($key, $val, $time);
	}

	public function delete($key) {
		assert(!is_null($key));
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
	 * The PDO database connection object, for anyone who wants direct access
	 */
	private $db = null;

	/**
	 * Meta info about the database engine
	 */
	private $engine = null;

	/**
	 * The currently active cache engine
	 */
	public $cache = null;

	/**
	 * For now, only connect to the cache, as we will pretty much certainly
	 * need it. There are some pages where all the data is in cache, so the
	 * DB connection is on-demand.
	 */
	public function Database() {
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

		$db_params = array(
			PDO::ATTR_PERSISTENT => DATABASE_KA,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		);
		if(defined("HIPHOP")) $this->db = new PDO(DATABASE_DSN, $db_user, $db_pass);
		else $this->db = new PDO(DATABASE_DSN, $db_user, $db_pass, $db_params);

		$this->connect_engine();
		$this->engine->init($this->db);

		$this->db->beginTransaction();
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

	public function commit() {
		if(!is_null($this->db)) return $this->db->commit();
	}

	public function rollback() {
		if(!is_null($this->db)) return $this->db->rollback();
	}

	public function escape($input) {
		if(is_null($this->db)) $this->connect_db();
		return $this->db->Quote($input);
	}

	public function scoreql_to_sql($input) {
		if(is_null($this->engine)) $this->connect_engine();
		return $this->engine->scoreql_to_sql($input);
	}

	public function get_driver_name() {
		if(is_null($this->engine)) $this->connect_engine();
		return $this->engine->name;
	}

	/**
	 * Execute an SQL query and return an PDO resultset
	 */
	public function execute($query, $args=array()) {
		try {
			if(is_null($this->db)) $this->connect_db();
			_count_execs($this->db, $query, $args);
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
	 * Execute an SQL query and return a 2D array
	 */
	public function get_all($query, $args=array()) {
		return $this->execute($query, $args)->fetchAll();
	}

	/**
	 * Execute an SQL query and return a single row
	 */
	public function get_row($query, $args=array()) {
		$row = $this->execute($query, $args)->fetch();
		return $row ? $row : null;
	}

	/**
	 * Execute an SQL query and return the first column of each row
	 */
	public function get_col($query, $args=array()) {
		$stmt = $this->execute($query, $args);
		$res = array();
		foreach($stmt as $row) {
			$res[] = $row[0];
		}
		return $res;
	}

	/**
	 * Execute an SQL query and return the the first row => the second rown
	 */
	public function get_pairs($query, $args=array()) {
		$stmt = $this->execute($query, $args);
		$res = array();
		foreach($stmt as $row) {
			$res[$row[0]] = $row[1];
		}
		return $res;
	}

	/**
	 * Execute an SQL query and return a single value
	 */
	public function get_one($query, $args=array()) {
		$row = $this->execute($query, $args)->fetch();
		return $row[0];
	}

	/**
	 * get the ID of the last inserted row
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
	 * Create a table from pseudo-SQL
	 */
	public function create_table($name, $data) {
		if(is_null($this->engine)) $this->connect_engine();
		$this->execute($this->engine->create_table_sql($name, $data));
	}
	
	/**
	 * Returns the number of tables present in the current database.
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
						$this->get_all(".tables")
					);
		} else {
			// Hard to find a universal way to do this...
			return NULL;
		}
	}
}

class MockDatabase extends Database {
	var $query_id = 0;
	var $responses = array();
	var $cache = null;

	public function __construct($responses = array()) {
		$this->cache = new NoCache();
		$this->responses = $responses;
	}
	public function execute($query, $params=array()) {
		log_debug("mock-database",
			"QUERY: " . $query .
			"\nARGS: " . var_export($params, true) .
			"\nRETURN: " . var_export($this->responses[$this->query_id], true)
		);
		return $this->responses[$this->query_id++];
	}

	public function get_all($query, $args=array()) {return $this->execute($query, $args);}
	public function get_row($query, $args=array()) {return $this->execute($query, $args);}
	public function get_col($query, $args=array()) {return $this->execute($query, $args);}
	public function get_pairs($query, $args=array()) {return $this->execute($query, $args);}
	public function get_one($query, $args=array()) {return $this->execute($query, $args);}
	public function get_last_insert_id($seq) {return $this->query_id;}

	public function scoreql_to_sql($sql) {return $sql;}
	public function create_table($name, $def) {}
	public function connect_engine() {}
}
?>
