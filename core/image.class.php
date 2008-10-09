<?php
/*
 * An object representing an entry in the images table. As of 2.2, this no
 * longer necessarily represents an image per se, but could be a video,
 * sound file, or any other supported upload type.
 */
class Image {
	var $config;
	var $database;

	var $id = null;
	var $height, $width;
	var $hash, $filesize;
	var $filename, $ext;
	var $owner_ip;
	var $posted;
	var $source;

	public function Image($row=null) {
		global $config;
		global $database;

		$this->config = $config;
		$this->database = $database;

		if(!is_null($row)) {
			foreach($row as $name => $value) {
				// FIXME: some databases use table.name rather than name
				$this->$name = $value; // hax
			}
			$this->posted_timestamp = strtotime($this->posted); // pray
		}
	}

	public static function by_id(Config $config, Database $database, $id) {
		assert(is_numeric($id));
		$image = null;
		$row = $database->get_row("SELECT * FROM images WHERE images.id=?", array($id));
		return ($row ? new Image($row) : null);
	}
	
	public static function by_hash(Config $config, Database $database, $hash) {
		assert(is_string($hash));
		$image = null;
		$row = $this->db->GetRow("SELECT images.* FROM images WHERE hash=?", array($hash));
		return ($row ? new Image($row) : null);
	}

	public static function by_random(Config $config, Database $database, $tags=array()) {
		$max = $database->count_images($tags);
		$rand = mt_rand(0, $max);
		$set = Image::find_images($config, $database, $rand, 1, $tags);
		if(count($set) > 0) return $set[0];
		else return null;
	}
	
	public static function find_images(Config $config, Database $database, $start, $limit, $tags=array()) {
		$images = array();

		assert(is_numeric($start) && $start >= 0);
		assert(is_numeric($limit) && $limit >  0);
		if($start < 0) $start = 0;
		if($limit < 1) $limit = 1;
		
		if(count($tags) == 0) {
			$result = $database->execute("SELECT images.* FROM images ORDER BY id DESC LIMIT ? OFFSET ?", array($limit, $start));
		}
		else {
			$querylet = $database->build_search_querylet($tags);
			$querylet->append(new Querylet("ORDER BY images.id DESC LIMIT ? OFFSET ?", array($limit, $start)));
			$result = $database->execute($querylet->sql, $querylet->variables);
		}
		
		while(!$result->EOF) {
			$images[] = new Image($result->fields);
			$result->MoveNext();
		}
		return $images;
	}


	public function get_next($tags=array(), $next=true) {
		assert(is_array($tags));
		assert(is_bool($next));
		
		if($next) {
			$gtlt = "<";
			$dir = "DESC";
		}
		else {
			$gtlt = ">";
			$dir = "ASC";
		}

		if(count($tags) == 0) {
			$row = $this->db->GetRow("SELECT images.* FROM images WHERE images.id $gtlt {$this->id} ORDER BY images.id $dir LIMIT 1");
		}
		else {
			$tags[] = "id$gtlt{$this->id}";
			$querylet = $this->database->build_search_querylet($tags);
			$querylet->append_sql(" ORDER BY images.id $dir LIMIT 1");
			$row = $this->db->GetRow($querylet->sql, $querylet->variables);
		}
		
		return ($row ? new Image($row) : null);
	}

	public function get_prev($tags=array()) {
		return $this->get_next($tags, false);
	}
	
	public function delete() {
		$this->database->execute("DELETE FROM images WHERE id=?", array($this->id));
		
		unlink($this->get_image_filename());
		unlink($this->get_thumb_filename());
	}

	public function get_owner() {
		return User::by_id($this->config, $this->database, $this->owner_id);
	}

	public function get_tag_array() {
		if(!isset($this->tag_array)) {
			$this->tag_array = Array();
			$row = $this->database->Execute("SELECT tag FROM image_tags JOIN tags ON image_tags.tag_id = tags.id WHERE image_id=? ORDER BY tag", array($this->id));
			while(!$row->EOF) {
				$this->tag_array[] = $row->fields['tag'];
				$row->MoveNext();
			}
		}
		return $this->tag_array;
	}

	public function get_tag_list() {
		return implode(' ', $this->get_tag_array());
	}

	public function get_image_link() {
		return $this->parse_link_template($this->config->get_string('image_ilink'));
	}

	public function get_short_link() {
		return $this->parse_link_template($this->config->get_string('image_slink'));
	}

	public function get_thumb_link() {
		return $this->parse_link_template($this->config->get_string('image_tlink'));
	}

	public function get_tooltip() {
		global $config;
		return $this->parse_link_template($config->get_string('image_tip'), "html_escape");
	}

	public function get_image_filename() {
		$hash = $this->hash;
		$ab = substr($hash, 0, 2);
		$ext = $this->ext;
		return "images/$ab/$hash";
	}

	public function get_thumb_filename() {
		$hash = $this->hash;
		$ab = substr($hash, 0, 2);
		return "thumbs/$ab/$hash";
	}

	public function get_filename() {
		return $this->filename;
	}
	
	public function get_mime_type() {
		return "image/".($this->ext);
	}

	public function get_ext() {
		return $this->ext;
	}

	public function get_source() {
		return $this->source;
	}

	public function parse_link_template($tmpl, $_escape="url_escape") {
		// don't bother hitting the database if it won't be used...
		$safe_tags = "";
		if(strpos($tmpl, '$tags') !== false) { // * stabs dynamically typed languages with a rusty spoon *
			$safe_tags = preg_replace(
					"/[^a-zA-Z0-9_\- ]/",
					"", $this->get_tag_list());
		}

		$base_href = $this->config->get_string('base_href');
		$fname = $this->get_filename();
		$base_fname = strpos($fname, '.') ? substr($fname, 0, strrpos($fname, '.')) : $fname;

		$tmpl = str_replace('$id',   $this->id,   $tmpl);
		$tmpl = str_replace('$hash', $this->hash, $tmpl);
		$tmpl = str_replace('$tags', $_escape($safe_tags),  $tmpl);
		$tmpl = str_replace('$base', $base_href,  $tmpl);
		$tmpl = str_replace('$ext',  $this->ext,  $tmpl);
		$tmpl = str_replace('$size', "{$this->width}x{$this->height}", $tmpl);
		$tmpl = str_replace('$filesize', to_shorthand_int($this->filesize), $tmpl);
		$tmpl = str_replace('$filename', $_escape($base_fname), $tmpl);
		$tmpl = str_replace('$title', $_escape($this->config->get_string("title")), $tmpl);

		$plte = new ParseLinkTemplateEvent($tmpl, $this);
		send_event($plte);
		$tmpl = $plte->link;

		return $tmpl;
	}
}
?>
