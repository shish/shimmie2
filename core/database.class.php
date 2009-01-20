<?php
require_once "compat.inc.php";
$ADODB_CACHE_DIR=sys_get_temp_dir();
require_once "lib/adodb/adodb.inc.php";
require_once "lib/adodb/adodb-exceptions.inc.php";

/* Querylet {{{
 * A fragment of a query, used to build large search queries
 */
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
	var $auto_increment = null;
	var $create_table_extras = "";
}
class MySQL extends DBEngine {
	var $name = "mysql";
	var $auto_increment = "INTEGER PRIMARY KEY auto_increment";
	var $create_table_extras = "TYPE=INNODB DEFAULT CHARSET='utf8'";

	function init($db) {
		$db->Execute("SET NAMES utf8;");
	}
}
class PostgreSQL extends DBEngine {
	var $name = "pgsql";
	var $auto_increment = "SERIAL PRIMARY KEY";

	function init($db) {
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
class MemCache implements CacheEngine {
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
// }}}

/*
 * A class for controlled database access
 */
class Database {
	var $db;
	var $extensions;
	var $engine = null;
	var $cache = null;

	/*
	 * Create a new database object using connection info
	 * stored in config.php in the root shimmie folder
	 */
	public function Database() {
		if(is_readable("config.php")) {
			require_once "config.php";
			$this->engine = new MySQL();
			$this->db = @NewADOConnection($database_dsn);

			if(isset($cache)) {
				$matches = array();
				preg_match("#(memcache)://(.*)#", $cache, $matches);
				if($matches[1] == "memcache") {
					$this->cache = new MemCache($matches[2]);
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
		else {
			header("Location: install.php");
			exit;
		}
	}

// safety wrapping {{{
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

	public function upgrade_schema($filename) {
		$this->install_schema($filename);
	}

	public function install_schema($filename) {
		//print "<br>upgrading $filename";

		global $config;
		if($config->get_bool("in_upgrade")) return;
		$config->set_bool("in_upgrade", true);

		require_once "lib/adodb/adodb-xmlschema03.inc.php";
		$schema = new adoSchema($this->db);
		$sql = $schema->ParseSchema($filename);
		//echo "<pre>"; var_dump($sql); echo "</pre>";
		$result = $schema->ExecuteSchema();

		if(!$result) {
			die("Error creating tables from XML schema ($filename)");
		}

		$config->set_bool("in_upgrade", false);
	}
// }}}
}
?>
