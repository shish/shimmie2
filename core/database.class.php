<?php
require_once "compat.inc.php";
$ADODB_CACHE_DIR=sys_get_temp_dir();
require_once "lib/adodb/adodb.inc.php";
require_once "lib/adodb/adodb-exceptions.inc.php";

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
		return "CREATE TABLE $name ($data)";
	}
}
class MySQL extends DBEngine {
	var $name = "mysql";

	public function init($db) {
		$db->Execute("SET NAMES utf8;");
	}

	public function scoreql_to_sql($data) {
		$data = str_replace("SCORE_AIPK", "INTEGER PRIMARY KEY auto_increment", $data);
		$data = str_replace("SCORE_INET", "CHAR(15)", $data);
		$data = str_replace("SCORE_BOOL_Y", "'Y'", $data);
		$data = str_replace("SCORE_BOOL_N", "'N'", $data);
		$data = str_replace("SCORE_BOOL", "ENUM('Y', 'N')", $data);
		$data = str_replace("SCORE_DATETIME", "DATETIME", $data);
		$data = str_replace("SCORE_NOW", "\"1970-01-01\"", $data);
		$data = str_replace("SCORE_STRNORM", "", $data);
		return $data;
	}

	public function create_table_sql($name, $data) {
		$data = $this->scoreql_to_sql($data);
		$ctes = "TYPE=InnoDB DEFAULT CHARSET='utf8'";
		return "CREATE TABLE $name ($data) $ctes";
	}
}
class PostgreSQL extends DBEngine {
	var $name = "pgsql";

	public function scoreql_to_sql($data) {
		$data = str_replace("SCORE_AIPK", "SERIAL PRIMARY KEY", $data);
		$data = str_replace("SCORE_INET", "INET", $data);
		$data = str_replace("SCORE_BOOL_Y", "'t'", $data);
		$data = str_replace("SCORE_BOOL_N", "'f'", $data);
		$data = str_replace("SCORE_BOOL", "BOOL", $data);
		$data = str_replace("SCORE_DATETIME", "TIMESTAMP", $data);
		$data = str_replace("SCORE_NOW", "current_time", $data);
		$data = str_replace("SCORE_STRNORM", "lower", $data);
		return $data;
	}

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

class SQLite extends DBEngine {
	var $name = "sqlite";

	public function init($db) {
		ini_set('sqlite.assoc_case', 0);
		$db->execute("PRAGMA foreign_keys = ON;");
		@sqlite_create_function($db->_connectionID, 'UNIX_TIMESTAMP', '_unix_timestamp', 1);
		@sqlite_create_function($db->_connectionID, 'now', '_now', 0);
		@sqlite_create_function($db->_connectionID, 'floor', '_floor', 1);
		@sqlite_create_function($db->_connectionID, 'log', '_log');
		@sqlite_create_function($db->_connectionID, 'isnull', '_isnull', 1);
		@sqlite_create_function($db->_connectionID, 'md5', '_md5', 1);
		@sqlite_create_function($db->_connectionID, 'concat', '_concat', 2);
		@sqlite_create_function($db->_connectionID, 'lower', '_lower', 1);
	}

	public function create_table_sql($name, $data) {
		$data = str_replace("SCORE_AIPK", "INTEGER PRIMARY KEY", $data);
		$data = str_replace("SCORE_INET", "VARCHAR(15)", $data);
		$data = str_replace("SCORE_BOOL_Y", "'Y'", $data);
		$data = str_replace("SCORE_BOOL_N", "'N'", $data);
		$data = str_replace("SCORE_BOOL", "CHAR(1)", $data);
		$data = str_replace("SCORE_NOW", "\"1970-01-01\"", $data);
		$data = str_replace("SCORE_STRNORM", "", $data);
		$cols = array();
		$extras = "";
		foreach(explode(",", $data) as $bit) {
			$matches = array();
			if(preg_match("/INDEX\s*\((.*)\)/", $bit, $matches)) {
				$col = $matches[1];
				$extras .= "CREATE INDEX {$name}_{$col} on $name($col);";
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
	var $hits=0, $misses=0;

	public function __construct($args) {
		$this->memcache = new Memcache;
		$this->memcache->pconnect('localhost', 11211) or ($this->use_memcache = false);
	}

	public function get($key) {
		assert(!is_null($key));
		$val = $this->memcache->get($key);
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
	 * The ADODB database connection object, for anyone who wants direct access
	 */
	var $db;

	/**
	 * Meta info about the database engine
	 */
	var $engine = null;

	/**
	 * The currently active cache engine
	 */
	var $cache = null;

	/**
	 * Create a new database object using connection info
	 * stored in config.php in the root shimmie folder
	 */
	public function Database() {
		global $database_dsn, $cache_dsn;

		if(substr($database_dsn, 0, 5) == "mysql") {
			$this->engine = new MySQL();
		}
		else if(substr($database_dsn, 0, 5) == "pgsql") {
			$this->engine = new PostgreSQL();
		}
		else if(substr($database_dsn, 0, 6) == "sqlite") {
			$this->engine = new SQLite();
		}

		$this->db = @NewADOConnection($database_dsn);

		if(isset($cache_dsn) && !empty($cache_dsn)) {
			$matches = array();
			preg_match("#(memcache|apc)://(.*)#", $cache_dsn, $matches);
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

		if($this->db) {
			$this->db->SetFetchMode(ADODB_FETCH_ASSOC);
			$this->engine->init($this->db);
		}
		else {
			$version = VERSION;
			print "
			<html>
				<head>
					<title>Internal error - Shimmie-$version</title>
				</head>
				<body>
					Internal error: Could not connect to database
				</body>
			</html>
			";
			exit;
		}
	}

	/**
	 * Execute an SQL query and return an ADODB resultset
	 */
	public function execute($query, $args=array()) {
		$result = $this->db->Execute($query, $args);
		if($result === False) {
			print "SQL Error: " . $this->db->ErrorMsg();
			print "<br>Query: $query";
			print "<br>Args: "; print_r($args);
			exit;
		}
		return $result;
	}

	/**
	 * Execute an SQL query and return a 2D array
	 */
	public function get_all($query, $args=array()) {
		$result = $this->db->GetAll($query, $args);
		if($result === False) {
			print "SQL Error: " . $this->db->ErrorMsg();
			print "<br>Query: $query";
			print "<br>Args: "; print_r($args);
			exit;
		}
		return $result;
	}

	/**
	 * Execute an SQL query and return a single row
	 */
	public function get_row($query, $args=array()) {
		$result = $this->db->GetRow($query, $args);
		if($result === False) {
			print "SQL Error: " . $this->db->ErrorMsg();
			print "<br>Query: $query";
			print "<br>Args: "; print_r($args);
			exit;
		}
		if(count($result) == 0) {
			return null;
		}
		else {
			return $result;
		}
	}

	/**
	 * Create a table from pseudo-SQL
	 */
	public function create_table($name, $data) {
		$this->execute($this->engine->create_table_sql($name, $data));
	}
}
?>
