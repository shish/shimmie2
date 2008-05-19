<?php
require_once "compat.inc.php";
$ADODB_CACHE_DIR=sys_get_temp_dir();
require_once "lib/adodb/adodb.inc.php";

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
// {{{ dbengines
class DBEngine {
	var $name = null;
	var $auto_increment = null;
	var $create_table_extras = "";
}
class MySQL extends DBEngine {
	var $name = "mysql";
	var $auto_increment = "INTEGER PRIMARY KEY auto_increment";
	var $create_table_extras = "TYPE=INNODB DEFAULT CHARSET='utf8'";
}
class PostgreSQL extends DBEngine {
	var $name = "pgsql";
	var $auto_increment = "SERIAL PRIMARY KEY";
}
//}}}

/*
 * A class for controlled database access, available through "global $database"
 */
class Database {
	var $db;
	var $extensions;
	var $get_images = "SELECT images.*,UNIX_TIMESTAMP(posted) AS posted_timestamp FROM images ";
	var $cache_hits = 0, $cache_misses = 0;
	var $engine = null;

	/*
	 * Create a new database object using connection info
	 * stored in config.php in the root shimmie folder
	 */
	public function Database() {
		if(is_readable("config.php")) {
			require_once "config.php";
			$this->engine = new MySQL();
			$this->db = @NewADOConnection($database_dsn);
			$this->use_memcache = isset($memcache);
			if($this->db) {
				$this->db->SetFetchMode(ADODB_FETCH_ASSOC);
				$this->db->Execute("SET NAMES utf8"); // FIXME: mysql specific :|
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
			if($this->use_memcache) {
				$this->memcache = new Memcache;
				$this->memcache->pconnect('localhost', 11211) or ($this->use_memcache = false);
			}
		}
		else {
			header("Location: install.php");
			exit;
		}
	}

// memcache {{{
	public function cache_get($key) {
		if($this->use_memcache) {
			$val = $this->memcache->get($key);
			if($val) {
				$this->cache_hits++;
				return $val;
			}
			else {
				$this->cache_misses++;
			}
		}
		return false;
	}

	public function cache_set($key, $val, $time=0) {
		if($this->use_memcache) {
			$this->memcache->set($key, $val, false, $time);
		}
	}

	public function cache_delete($key) {
		if($this->use_memcache) {
			$this->memcache->delete($key);
		}
	}
// }}}
// misc {{{
	public function count_images($tags=array()) {
		if(count($tags) == 0) {
			return $this->db->GetOne("SELECT COUNT(*) FROM images");
		}
		else {
			$querylet = $this->build_search_querylet($tags);
			$result = $this->execute($querylet->sql, $querylet->variables);
			return $result->RecordCount();
		}
	}

	public function count_pages($tags=array()) {
		global $config;
		$images_per_page = $config->get_int('index_width') * $config->get_int('index_height');
		return ceil($this->count_images($tags) / $images_per_page);
	}

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
// tags {{{
	public function resolve_alias($tag) {
		$newtag = $this->db->GetOne("SELECT newtag FROM aliases WHERE oldtag=?", array($tag));
		if(!empty($newtag)) {
			return $newtag;
		} else {
			return $tag;
		}
	}

	public function sanitise($tag) {
		$tag = preg_replace("/[\s?*]/", "", $tag);
		$tag = preg_replace("/\.+/", ".", $tag);
		$tag = preg_replace("/^(\.+[\/\\\\])+/", "", $tag);
		return $tag;
	}

	private function build_search_querylet($terms) {
		$tag_querylets = array();
		$img_querylets = array();
		$positive_tag_count = 0;
		$negative_tag_count = 0;


		// turn each term into a specific type of querylet
		foreach($terms as $term) {
			$negative = false;
			if((strlen($term) > 0) && ($term[0] == '-')) {
				$negative = true;
				$term = substr($term, 1);
			}
			
			$term = $this->resolve_alias($term);

			$stpe = new SearchTermParseEvent($term);
			send_event($stpe);
			if($stpe->is_querylet_set()) {
				$img_querylets[] = new ImgQuerylet($stpe->get_querylet(), !$negative);
			}
			else {
				$term = str_replace("*", "%", $term);
				$term = str_replace("?", "_", $term);
				if(!preg_match("/^[%_]+$/", $term)) {
					$tag_querylets[] = new TagQuerylet($term, !$negative);
				}
			}
		}

		// merge all the tag querylets into one generic one
		$sql = "0";
		$terms = array();
		foreach($tag_querylets as $tq) {
			$sign = $tq->positive ? "+" : "-";
			$sql .= " $sign (tag LIKE ?)";
			$terms[] = $tq->tag;
			
			if($sign == "+") $positive_tag_count++;
			else $negative_tag_count++;
		}
		$tag_search = new Querylet($sql, $terms);

		// merge all the image metadata searches into one generic querylet
		$n = 0;
		$sql = "";
		$terms = array();
		foreach($img_querylets as $iq) {
			if($n++ > 0) $sql .= " AND";
			if(!$iq->positive) $sql .= " NOT";
			$sql .= " (" . $iq->qlet->sql . ")";
			$terms = array_merge($terms, $iq->qlet->variables);
		}
		$img_search = new Querylet($sql, $terms);


		// no tags, do a simple search (+image metadata if we have any)
		if($positive_tag_count + $negative_tag_count == 0) {
			$query = new Querylet($this->get_images);

			if(strlen($img_search->sql) > 0) {
				$query->append_sql(" WHERE ");
				$query->append($img_search);
			}
		}

		// one positive tag (a common case), do an optimised search
		else if($positive_tag_count == 1 && $negative_tag_count == 0) {
			$query = new Querylet(
				// MySQL is braindead, and does a full table scan on images, running the subquery once for each row -_-
				// "{$this->get_images} WHERE images.id IN (SELECT image_id FROM tags WHERE tag LIKE ?) ",
				"
					SELECT images.*, UNIX_TIMESTAMP(posted) AS posted_timestamp
					FROM tags, image_tags, images
					WHERE
						tag LIKE ?
						AND tags.id = image_tags.tag_id
						AND image_tags.image_id = images.id
				",
				$tag_search->variables);

			if(strlen($img_search->sql) > 0) {
				$query->append_sql(" AND ");
				$query->append($img_search);
			}
		}

		// more than one positive tag, or more than zero negative tags
		else {
			$s_tag_array = array_map("sql_escape", $tag_search->variables);
			$s_tag_list = join(', ', $s_tag_array);
			
			$tag_id_array = array();
			$tags_ok = true;
			foreach($tag_search->variables as $tag) {
				$tag_ids = $this->db->GetCol("SELECT id FROM tags WHERE tag LIKE ?", array($tag));
				$tag_id_array = array_merge($tag_id_array, $tag_ids);
				$tags_ok = count($tag_ids) > 0;
				if(!$tags_ok) break;
			}
			if($tags_ok) {
				$tag_id_list = join(', ', $tag_id_array);

				$subquery = new Querylet("
					SELECT images.*, SUM({$tag_search->sql}) AS score
					FROM images
					LEFT JOIN image_tags ON image_tags.image_id = images.id
					JOIN tags ON image_tags.tag_id = tags.id
					WHERE tags.id IN ({$tag_id_list})
					GROUP BY images.id
					HAVING score = ?",
					array_merge(
						$tag_search->variables,
						array($positive_tag_count)
					)
				);
				$query = new Querylet("
					SELECT *, UNIX_TIMESTAMP(posted) AS posted_timestamp
					FROM ({$subquery->sql}) AS images ", $subquery->variables);

				if(strlen($img_search->sql) > 0) {
					$query->append_sql(" WHERE ");
					$query->append($img_search);
				}
			}
			else {
				# there are no results, "where 1=0" should shortcut things
				$query = new Querylet("
					SELECT images.*
					FROM images
					WHERE 1=0
				");
			}
		}

		return $query;
	}

	public function delete_tags_from_image($image_id) {
		$this->execute("UPDATE tags SET count = count - 1 WHERE id IN (SELECT tag_id FROM image_tags WHERE image_id = ?)", array($image_id));
		$this->execute("DELETE FROM image_tags WHERE image_id=?", array($image_id));
	}

	public function set_tags($image_id, $tags) {
		$tags = tag_explode($tags);

		$tags = array_map(array($this, 'resolve_alias'), $tags);
		$tags = array_map(array($this, 'sanitise'), $tags);
		$tags = array_unique($tags); // remove any duplicate tags

		// delete old
		$this->delete_tags_from_image($image_id);
		
		// insert each new tag
		foreach($tags as $tag) {
			$this->execute("INSERT IGNORE INTO tags(tag) VALUES (?)", array($tag));
			$this->execute("INSERT INTO image_tags(image_id, tag_id) VALUES(?, (SELECT id FROM tags WHERE tag = ?))", array($image_id, $tag));
			$this->execute("UPDATE tags SET count = count + 1 WHERE tag = ?", array($tag));
		}
	}
	
	public function set_source($image_id, $source) {
		if(empty($source)) $source = null;
		$this->execute("UPDATE images SET source=? WHERE id=?", array($source, $image_id));
	}
// }}}
// images {{{
	public function get_images($start, $limit, $tags=array()) {
		$images = array();

		assert($start >= 0);
		assert($limit >  0);
		if($start < 0) $start = 0;
		if($limit < 1) $limit = 1;
		
		if(count($tags) == 0) {
			$result = $this->execute("{$this->get_images} ORDER BY id DESC LIMIT ? OFFSET ?", array($limit, $start));
		}
		else {
			$querylet = $this->build_search_querylet($tags);
			$querylet->append(new Querylet("ORDER BY images.id DESC LIMIT ? OFFSET ?", array($limit, $start)));
			$result = $this->execute($querylet->sql, $querylet->variables);
		}
		
		while(!$result->EOF) {
			$images[] = new Image($result->fields);
			$result->MoveNext();
		}
		return $images;
	}

	public function get_next_image($id, $tags=array(), $next=true) {
		if($next) {
			$gtlt = "<";
			$dir = "DESC";
		}
		else {
			$gtlt = ">";
			$dir = "ASC";
		}

		if(count($tags) == 0) {
			$row = $this->db->GetRow("{$this->get_images} WHERE images.id $gtlt ? ORDER BY images.id $dir LIMIT 1", array((int)$id));
		}
		else {
			$tags[] = "id$gtlt$id";
			$querylet = $this->build_search_querylet($tags);
			$querylet->append_sql(" ORDER BY images.id $dir LIMIT 1");
			$row = $this->db->GetRow($querylet->sql, $querylet->variables);
		}
		
		return ($row ? new Image($row) : null);
	}

	public function get_prev_image($id, $tags=array()) {
		return $this->get_next_image($id, $tags, false);
	}

	public function get_image($id) {
		$image = null;
		$row = $this->db->GetRow("{$this->get_images} WHERE images.id=?", array($id));
		return ($row ? new Image($row) : null);
	}

	public function get_random_image($tags=array()) {
		$max = $this->count_images($tags);
		$rand = mt_rand(0, $max);
		$set = $this->get_images($rand, 1, $tags);
		if(count($set) > 0) return $set[0];
		else return null;
	}

	public function get_image_by_hash($hash) {
		$image = null;
		$row = $this->db->GetRow("{$this->get_images} WHERE hash=?", array($hash));
		return ($row ? new Image($row) : null);
	}

	public function remove_image($id) {
		$this->execute("DELETE FROM images WHERE id=?", array($id));
	}
// }}}
// users {{{
	var $SELECT_USER = "SELECT *,(unix_timestamp(now()) - unix_timestamp(joindate))/(60*60*24) AS days_old FROM users ";
	
	public function get_user_session($name, $session) {
		$row = $this->db->GetRow("{$this->SELECT_USER} WHERE name LIKE ? AND md5(concat(pass, ?)) = ?",
				array($name, get_session_ip(), $session));
		return $row ? new User($row) : null;
	}

	public function get_user_by_id($id) {
		$row = $this->db->GetRow("{$this->SELECT_USER} WHERE id=?", array($id));
		return $row ? new User($row) : null;
	}
	
	public function get_user_by_name($name) {
		$row = $this->db->GetRow("{$this->SELECT_USER} WHERE name=?", array($name));
		return $row ? new User($row) : null;
	}

	public function get_user_by_name_and_hash($name, $hash) {
		$row = $this->db->GetRow("{$this->SELECT_USER} WHERE name LIKE ? AND pass = ?", array($name, $hash));
		return $row ? new User($row) : null;
	}
// }}}
}
?>
