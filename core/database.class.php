<?php
$ADODB_CACHE_DIR="./data";
require_once "lib/adodb/adodb.inc.php";

class Querylet { // {{{
	var $sql;
	var $variables;
	
	public function querylet($sql, $variables=array()) {
		$this->sql = $sql;
		$this->variables = $variables;
	}

	public function append($querylet) {
		$this->sql .= $querylet->sql;
		$this->variables = array_merge($this->variables, $querylet->variables);
	}

	public function append_sql($sql) {
		$this->sql .= $sql;
	}

	public function add_variable($var) {
		$this->variables[] = $var;
	}
} // }}}

class Database {
	var $db;
	var $extensions;
	var $get_images = "SELECT images.*,UNIX_TIMESTAMP(posted) AS posted_timestamp FROM images ";

	public function Database() {
		if(is_readable("config.php")) {
			require_once "config.php";
			$this->db = @NewADOConnection($database_dsn);
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
		}
		else {
			header("Location: install.php");
			exit;
		}
	}

// misc {{{
	public function count_pages($tags=array()) {
		global $config;
		$images_per_page = $config->get_int('index_width') * $config->get_int('index_height');
		if(count($tags) == 0) {
			return ceil($this->db->GetOne("SELECT COUNT(*) FROM images") / $images_per_page);
		}
		else {
			$querylet = $this->build_search_querylet($tags);
			$result = $this->execute($querylet->sql, $querylet->variables);
			return ceil($result->RecordCount() / $images_per_page);
		}
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

	public function cache_execute($time, $query, $args=array()) {
		global $config;
		if($config->get_bool('db_cache')) {
			return $this->error_check($this->db->CacheExecute($time, $query, $args));
		}
		else {
			return $this->execute($query, $args);
		}
	}

	private function error_check($result) {
		if($result === False) {
			print "SQL Error: " . $this->db->ErrorMsg() . "<br>";
			print "Query: $query";
			exit;
		}
		return $result;
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
		return preg_replace("/[\s?*]/", "", $tag);
	}

	private function build_search_querylet($terms) {
		$tag_search = new Querylet("0");
		$positive_tag_count = 0;
		$negative_tag_count = 0;
		$img_search = new Querylet("");

		foreach($terms as $term) {
			$negative = false;
			if((strlen($term) > 0) && ($term[0] == '-')) {
				$negative = true;
				$term = substr($term, 1);
			}
			
			$term = $this->resolve_alias($term);

			$matches = array();
			if(preg_match("/size(<|>|<=|>=|=)(\d+)x(\d+)/", $term, $matches)) {
				$cmp = $matches[1];
				$args = array(int_escape($matches[2]), int_escape($matches[3]));
				$img_search->append(new Querylet("AND (width $cmp ? AND height $cmp ?)", $args));
			}
			else if(preg_match("/ratio(<|>|<=|>=|=)(\d+):(\d+)/", $term, $matches)) {
				$cmp = $matches[1];
				$args = array(int_escape($matches[2]), int_escape($matches[3]));
				$img_search->append(new Querylet("AND (width / height $cmp ? / ?)", $args));
			}
			else if(preg_match("/(filesize|id)(<|>|<=|>=|=)(\d+[kmg]?b?)/i", $term, $matches)) {
				$col = $matches[1];
				$cmp = $matches[2];
				$val = parse_shorthand_int($matches[3]);
				$img_search->append(new Querylet("AND (images.$col $cmp $val)"));
			}
			else if(preg_match("/(poster|user)=(.*)/i", $term, $matches)) {
				global $database;
				$user = $database->get_user_by_name($matches[2]);
				if(!is_null($user)) {
					$user_id = $user->id;
				}
				else {
					$user_id = -1;
				}
				$img_search->append(new Querylet("AND (images.owner_id = $user_id)"));
			}
			else if(preg_match("/(hash=|md5:)([0-9a-fA-F]*)/i",$term,$matches)) {
				$hash = strtolower($matches[2]);
				if(!is_null($hash)) {
					$img_search->append(new Querylet("AND (images.hash = '$hash')"));
				}
			}
			else {
				$term = str_replace("*", "%", $term);
				$term = str_replace("?", "_", $term);
				$sign = $negative ? "-" : "+";
				if($sign == "+") $positive_tag_count++;
				else $negative_tag_count++;
				$tag_search->append(new Querylet(" $sign (tag LIKE ?)", array($term)));
			}
		}

		if($positive_tag_count + $negative_tag_count == 0) {
			$query = new Querylet($this->get_images);

			if(strlen($img_search->sql) > 0) {
				$query->append_sql("WHERE 1=1 ");
				$query->append($img_search);
			}
		}
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
				$query->append($img_search);
			}
		}
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
			}
			else {
				# there are no results, "where 1=0" should shortcut things
				$query = new Querylet("
					SELECT images.*
					FROM images
					LEFT JOIN image_tags ON image_tags.image_id = images.id
					JOIN tags ON image_tags.tag_id = tags.id
					WHERE 1=0
				");
			}

			if(strlen($img_search->sql) > 0) {
				$query->append_sql("WHERE 1=1 ");
				$query->append($img_search);
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
				array($name, $_SERVER['REMOTE_ADDR'], $session));
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
