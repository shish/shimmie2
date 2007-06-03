<?php

class Image {
	var $id = null;
	var $height, $width;
	var $hash, $filesize;
	var $filename, $ext;
	var $owner_ip;
	var $posted;

	public function Image($a=false, $b=false, $c=false) {
		if($b == false && $c == false) {
			$this->create_from_row($a);
		}
		else {
			$this->create_from_data($a, $b, $c);
		}
	}

	private function create_from_row($row) {
		$this->id       = $row['id'];
		$this->owner_id = $row['owner_id'];
		$this->owner_ip = $row['owner_ip'];
		$this->filename = $row['filename'];
		$this->filesize = $row['filesize'];
		$this->hash     = $row['hash'];
		$this->ext      = $row['ext'];
		$this->width    = $row['width'];
		$this->height   = $row['height'];
		$this->posted   = $row['posted'];
		$this->posted_timestamp   = $row['posted_timestamp'];
	}

	private function mime_to_ext($mime) {
		switch($mime) {
			default:
			case 'image/jpeg': return "jpg"; break;
			case 'image/png': return "png"; break;
			case 'image/gif': return "gif"; break;
		}
	}

	private function create_from_data($tmp, $filename, $tags) {
		global $config;

		$this->ok = false;
		$info = "";

		if(!file_exists($tmp)) return;
		if(filesize($tmp) > $config->get_int('upload_size')) return;
		if(!($info = getimagesize($tmp))) return;

		$this->width = $info[0];
		$this->height = $info[1];
		$this->mime_type = $info['mime'];
		$this->filename = str_replace("/", "_", $filename); // is this even possible?
		$this->filesize = filesize($tmp);
		$this->ext = $this->mime_to_ext($info['mime']);
		$this->hash = md5_file($tmp);
		$this->temp_filename = $tmp;
		
		$this->tag_array = tag_explode($tags);

		$this->ok = true;
	}

	public function is_ok() {
		return $this->ok;
	}

	public function get_owner() {
		global $database;
		return $database->get_user($this->owner_id);
	}

	public function get_tag_array() {
		if(!isset($this->tag_array)) {
			global $database;
			$this->tag_array = Array();
			$row = $database->Execute("SELECT * FROM tags WHERE image_id=? ORDER BY tag", array($this->id));
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
		global $config;
		return $this->parse_link_template($config->get_string('image_ilink'));
	}

	public function get_short_link() {
		global $config;
		return $this->parse_link_template($config->get_string('image_slink'));
	}

	public function get_thumb_link() {
		global $config;
		return $this->parse_link_template($config->get_string('image_tlink'));
	}

	public function get_tooltip() {
		global $config;
		return $this->parse_link_template($config->get_string('image_tip'));
	}

	public function get_image_filename() {
		global $config;
		$hash = $this->hash;
		$ab = substr($hash, 0, 2);
		$ext = $this->ext;
		return "images/$ab/$hash";
	}

	public function get_thumb_filename() {
		global $config;
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

	public function parse_link_template($tmpl) {
		global $config;

		// don't bother hitting the database if it won't be used...
		$safe_tags = "";
		if(strpos($tmpl, '$tags') !== false) { // * stabs dynamically typed languages with a rusty spoon *
			$safe_tags = preg_replace(
					"/[^a-zA-Z0-9_\- ]/",
					"", $this->get_tag_list());
		}

		$base_href = $config->get_string('base_href');
		$fname = $this->get_filename();
		$base_fname = strpos($fname, '.') ? substr($fname, 0, strrpos($fname, '.')) : $fname;

		$tmpl = str_replace('$id',   $this->id,   $tmpl);
		$tmpl = str_replace('$hash', $this->hash, $tmpl);
		$tmpl = str_replace('$tags', $safe_tags,  $tmpl);
		$tmpl = str_replace('$base', $base_href,  $tmpl);
		$tmpl = str_replace('$ext',  $this->ext,  $tmpl);
		$tmpl = str_replace('$size', "{$this->width}x{$this->height}", $tmpl);
		$tmpl = str_replace('$filesize', to_shorthand_int($this->filesize), $tmpl);
		$tmpl = str_replace('$filename', $base_fname, $tmpl);

		return $tmpl;
	}
}
?>
