<?php
/**
 * All the imageboard-specific bits of code should be in this file, everything
 * else in /core should be standard SCore bits.
 */

/**
 * \page search Shimmie2: Searching
 * 
 * The current search system is built of several search item -> image ID list
 * translators, eg:
 * 
 * \li the item "fred" will search the image_tags table to find image IDs with the fred tag
 * \li the item "size=640x480" will search the images table to find image IDs of 640x480 images
 * 
 * So the search "fred size=640x480" will calculate two lists and take the
 * intersection. (There are some optimisations in there making it more
 * complicated behind the scenes, but as long as you can turn a single word
 * into a list of image IDs, making a search plugin should be simple)
 */

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Classes                                                                   *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * An object representing an entry in the images table. As of 2.2, this no
 * longer necessarily represents an image per se, but could be a video,
 * sound file, or any other supported upload type.
 */
class Image {
	var $id = null;
	var $height, $width;
	var $hash, $filesize;
	var $filename, $ext;
	var $owner_ip;
	var $posted;
	var $source;

	/**
	 * One will very rarely construct an image directly, more common
	 * would be to use Image::by_id, Image::by_hash, etc
	 */
	public function Image($row=null) {
		if(!is_null($row)) {
			foreach($row as $name => $value) {
				// some databases use table.name rather than name
				$name = str_replace("images.", "", $name);
				$this->$name = $value; // hax
			}
			$this->posted_timestamp = strtotime($this->posted); // pray

			assert(is_numeric($this->id));
			assert(is_numeric($this->height));
			assert(is_numeric($this->width));
		}
	}

	/**
	 * Find an image by ID
	 *
	 * @retval Image
	 */
	public static function by_id($id) {
		assert(is_numeric($id));
		global $database;
		$image = null;
		$row = $database->get_row("SELECT * FROM images WHERE images.id=?", array($id));
		return ($row ? new Image($row) : null);
	}

	/**
	 * Find an image by hash
	 *
	 * @retval Image
	 */
	public static function by_hash($hash) {
		assert(is_string($hash));
		global $database;
		$image = null;
		$row = $database->db->GetRow("SELECT images.* FROM images WHERE hash=?", array($hash));
		return ($row ? new Image($row) : null);
	}

	/**
	 * Pick a random image out of a set
	 *
	 * @retval Image
	 */
	public static function by_random($tags=array()) {
		assert(is_array($tags));
		$max = Image::count_images($tags);
		$rand = mt_rand(0, $max-1);
		$set = Image::find_images($rand, 1, $tags);
		if(count($set) > 0) return $set[0];
		else return null;
	}

	/**
	 * Search for an array of images
	 */
	public static function find_images($start, $limit, $tags=array()) {
		assert(is_numeric($start));
		assert(is_numeric($limit));
		assert(is_array($tags));
		global $database;

		$images = array();

		if($start < 0) $start = 0;
		if($limit < 1) $limit = 1;

		$querylet = Image::build_search_querylet($tags);
		$querylet->append(new Querylet("ORDER BY images.id DESC LIMIT ? OFFSET ?", array($limit, $start)));
		$result = $database->execute($querylet->sql, $querylet->variables);

		while(!$result->EOF) {
			$images[] = new Image($result->fields);
			$result->MoveNext();
		}
		return $images;
	}

	/*
	 * Image-related utility functions
	 */
	
	/**
	 * Count the number of image results for a given search
	 */
	public static function count_images($tags=array()) {
		assert(is_array($tags));
		global $database;
		if(count($tags) == 0) {
			#return $database->db->GetOne("SELECT COUNT(*) FROM images");
			$total = $database->cache->get("image-count");
			if(!$total) {
				$total = $database->db->GetOne("SELECT COUNT(*) FROM images");
				$database->cache->set("image-count", $total, 600);
			}
			return $total;
		}
		else if(count($tags) == 1 && !preg_match("/[:=><]/", $tags[0])) {
			return $database->db->GetOne(
				$database->engine->scoreql_to_sql("SELECT count FROM tags WHERE SCORE_STRNORM(tag) = SCORE_STRNORM(?)"),
				$tags);
		}
		else {
			$querylet = Image::build_search_querylet($tags);
			$result = $database->execute($querylet->sql, $querylet->variables);
			return $result->RecordCount();
		}
	}

	/**
	 * Count the number of pages for a given search
	 */
	public static function count_pages($tags=array()) {
		assert(is_array($tags));
		global $config, $database;
		$images_per_page = $config->get_int('index_width') * $config->get_int('index_height');
		return ceil(Image::count_images($tags) / $images_per_page);
	}


	/*
	 * Accessors & mutators
	 */

	/**
	 * Find the next image in the sequence.
	 *
	 * Rather than simply $this_id + 1, one must take into account
	 * deleted images and search queries
	 *
	 * @retval Image
	 */
	public function get_next($tags=array(), $next=true) {
		assert(is_array($tags));
		assert(is_bool($next));
		global $database;

		if($next) {
			$gtlt = "<";
			$dir = "DESC";
		}
		else {
			$gtlt = ">";
			$dir = "ASC";
		}

		if(count($tags) == 0) {
			$row = $database->db->GetRow("SELECT images.* FROM images WHERE images.id $gtlt {$this->id} ORDER BY images.id $dir LIMIT 1");
		}
		else {
			$tags[] = "id$gtlt{$this->id}";
			$querylet = Image::build_search_querylet($tags);
			$querylet->append_sql(" ORDER BY images.id $dir LIMIT 1");
			$row = $database->db->GetRow($querylet->sql, $querylet->variables);
		}

		return ($row ? new Image($row) : null);
	}

	/**
	 * The reverse of get_next
	 *
	 * @retval Image
	 */
	public function get_prev($tags=array()) {
		return $this->get_next($tags, false);
	}

	/**
	 * Find the User who owns this Image
	 *
	 * @retval User
	 */
	public function get_owner() {
		return User::by_id($this->owner_id);
	}

	/**
	 * Get this image's tags as an array
	 */
	public function get_tag_array() {
		global $database;
		$cached = $database->cache->get("image-{$this->id}-tags");
		if($cached) return $cached;

		if(!isset($this->tag_array)) {
			$this->tag_array = Array();
			$row = $database->Execute("SELECT tag FROM image_tags JOIN tags ON image_tags.tag_id = tags.id WHERE image_id=? ORDER BY tag", array($this->id));
			while(!$row->EOF) {
				$this->tag_array[] = $row->fields['tag'];
				$row->MoveNext();
			}
		}

		$database->cache->set("image-{$this->id}-tags", $this->tag_array);
		return $this->tag_array;
	}

	/**
	 * Get this image's tags as a string
	 */
	public function get_tag_list() {
		return Tag::implode($this->get_tag_array());
	}

	/**
	 * Get the URL for the full size image
	 *
	 * @retval string
	 */
	public function get_image_link() {
		global $config;
		if(strlen($config->get_string('image_ilink')) > 0) {
			return $this->parse_link_template($config->get_string('image_ilink'));
		}
		else if($config->get_bool('nice_urls', false)) {
			return $this->parse_link_template(make_link('_images/$hash/$id - $tags.$ext'));
		}
		else {
			return $this->parse_link_template(make_link('image/$id.$ext'));
		}
	}

	/**
	 * Get a short link to the full size image
	 *
	 * @deprecated
	 * @retval string
	 */
	public function get_short_link() {
		global $config;
		return $this->parse_link_template($config->get_string('image_slink'));
	}

	/**
	 * Get the URL for the thumbnail
	 *
	 * @retval string
	 */
	public function get_thumb_link() {
		global $config;
		if(strlen($config->get_string('image_tlink')) > 0) {
			return $this->parse_link_template($config->get_string('image_tlink'));
		}
		else if($config->get_bool('nice_urls', false)) {
			return $this->parse_link_template(make_link('_thumbs/$hash/thumb.jpg'));
		}
		else {
			return $this->parse_link_template(make_link('thumb/$id.jpg'));
		}
	}

	/**
	 * Get the tooltip for this image, formatted according to the
	 * configured template
	 *
	 * @retval string
	 */
	public function get_tooltip() {
		global $config;
		return $this->parse_link_template($config->get_string('image_tip'), "html_escape");
	}

	/**
	 * Figure out where the full size image is on disk
	 *
	 * @retval string
	 */
	public function get_image_filename() {
		return warehouse_path("images", $this->hash);
	}

	/**
	 * Figure out where the thumbnail is on disk
	 *
	 * @retval string
	 */
	public function get_thumb_filename() {
		return warehouse_path("thumbs", $this->hash);
	}

	/**
	 * Get the original filename
	 *
	 * @retval string
	 */
	public function get_filename() {
		return $this->filename;
	}

	/**
	 * Get the image's mime type
	 *
	 * FIXME: now we handle more than just images
	 *
	 * @retval string
	 */
	public function get_mime_type() {
		return "image/".($this->ext);
	}

	/**
	 * Get the image's filename extension
	 *
	 * @retval string
	 */
	public function get_ext() {
		return $this->ext;
	}

	/**
	 * Get the image's source URL
	 *
	 * @retval string
	 */
	public function get_source() {
		return $this->source;
	}

	/**
	 * Set the image's source URL
	 */
	public function set_source($source) {
		global $database;
		if(empty($source)) $source = null;
		$database->execute("UPDATE images SET source=? WHERE id=?", array($source, $this->id));
	}

	/**
	 * Delete all tags from this image.
	 *
	 * Normally in preparation to set them to a new set.
	 */
	public function delete_tags_from_image() {
		global $database;
		$database->execute(
				"UPDATE tags SET count = count - 1 WHERE id IN ".
				"(SELECT tag_id FROM image_tags WHERE image_id = ?)", array($this->id));
		$database->execute("DELETE FROM image_tags WHERE image_id=?", array($this->id));
	}

	/**
	 * Set the tags for this image
	 */
	public function set_tags($tags) {
		global $database;
		$tags = Tag::resolve_list($tags);

		assert(is_array($tags));
		assert(count($tags) > 0);

		// delete old
		$this->delete_tags_from_image();

		// insert each new tags
		foreach($tags as $tag) {
			$id = $database->db->GetOne(
					$database->engine->scoreql_to_sql(
						"SELECT id FROM tags WHERE SCORE_STRNORM(tag) = SCORE_STRNORM(?)"
					),
					array($tag));
			if(empty($id)) {
				// a new tag
				$database->execute(
						"INSERT INTO tags(tag) VALUES (?)",
						array($tag));
				$database->execute(
						"INSERT INTO image_tags(image_id, tag_id)
						VALUES(?, (SELECT id FROM tags WHERE tag = ?))",
						array($this->id, $tag));
			}
			else {
				// user of an existing tag
				$database->execute(
						"INSERT INTO image_tags(image_id, tag_id) VALUES(?, ?)",
						array($this->id, $id));
			}
			$database->execute(
					$database->engine->scoreql_to_sql(
						"UPDATE tags SET count = count + 1 WHERE SCORE_STRNORM(tag) = SCORE_STRNORM(?)"
					),
					array($tag));
		}

		log_info("core-image", "Tags for Image #{$this->id} set to: ".implode(" ", $tags));
		$database->cache->delete("image-{$this->id}-tags");
	}

	/**
	 * Delete this image from the database and disk
	 */
	public function delete() {
		global $database;
		$this->delete_tags_from_image();
		$database->execute("DELETE FROM images WHERE id=?", array($this->id));
		log_info("core-image", "Deleted Image #{$this->id} ({$this->hash})");

		unlink($this->get_image_filename());
		unlink($this->get_thumb_filename());
	}

	/**
	 * Someone please explain this
	 *
	 * @retval string
	 */
	public function parse_link_template($tmpl, $_escape="url_escape") {
		global $config;

		// don't bother hitting the database if it won't be used...
		$tags = "";
		if(strpos($tmpl, '$tags') !== false) { // * stabs dynamically typed languages with a rusty spoon *
			$tags = $this->get_tag_list();
			$tags = str_replace("/", "", $tags);
			$tags = preg_replace("/^\.+/", "", $tags);
		}

		$base_href = $config->get_string('base_href');
		$fname = $this->get_filename();
		$base_fname = strpos($fname, '.') ? substr($fname, 0, strrpos($fname, '.')) : $fname;

		$tmpl = str_replace('$id',   $this->id,   $tmpl);
		$tmpl = str_replace('$hash_ab', substr($this->hash, 0, 2), $tmpl);
		$tmpl = str_replace('$hash_cd', substr($this->hash, 2, 2), $tmpl);
		$tmpl = str_replace('$hash', $this->hash, $tmpl);
		$tmpl = str_replace('$tags', $_escape($tags),  $tmpl);
		$tmpl = str_replace('$base', $base_href,  $tmpl);
		$tmpl = str_replace('$ext',  $this->ext,  $tmpl);
		$tmpl = str_replace('$size', "{$this->width}x{$this->height}", $tmpl);
		$tmpl = str_replace('$filesize', to_shorthand_int($this->filesize), $tmpl);
		$tmpl = str_replace('$filename', $_escape($base_fname), $tmpl);
		$tmpl = str_replace('$title', $_escape($config->get_string("title")), $tmpl);

		$plte = new ParseLinkTemplateEvent($tmpl, $this);
		send_event($plte);
		$tmpl = $plte->link;

		return $tmpl;
	}

	private static function build_search_querylet($terms) {
		assert(is_array($terms));
		global $database;
		if($database->engine->name == "mysql")
			return Image::build_ugly_search_querylet($terms);
		else
			return Image::build_accurate_search_querylet($terms);
	}

	/**
	 * "foo bar -baz user=foo" becomes
	 *
	 * SELECT * FROM images WHERE
	 *           images.id IN (SELECT image_id FROM image_tags WHERE tag='foo')
	 *   AND     images.id IN (SELECT image_id FROM image_tags WHERE tag='bar')
	 *   AND NOT images.id IN (SELECT image_id FROM image_tags WHERE tag='baz')
	 *   AND     images.id IN (SELECT id FROM images WHERE owner_name='foo')
	 *
	 * This is:
	 *   A) Incredibly simple:
	 *      Each search term maps to a list of image IDs
	 *   B) Runs really fast on a good database:
	 *      These lists are calucalted once, and the set intersection taken
	 *   C) Runs really slow on bad databases:
	 *      All the subqueries are executed every time for every row in the
	 *      images table. Yes, MySQL does suck this much.
	 */
	private static function build_accurate_search_querylet($terms) {
		global $config, $database;

		$tag_querylets = array();
		$img_querylets = array();
		$positive_tag_count = 0;

		$stpe = new SearchTermParseEvent(null, $terms);
		send_event($stpe);
		if($stpe->is_querylet_set()) {
			foreach($stpe->get_querylets() as $querylet) {
				$img_querylets[] = new ImgQuerylet($querylet, true);
			}
		}

		// parse the words that are searched for into
		// various types of querylet
		foreach($terms as $term) {
			$positive = true;
			if((strlen($term) > 0) && ($term[0] == '-')) {
				$positive = false;
				$term = substr($term, 1);
			}

			$term = Tag::resolve_alias($term);

			$stpe = new SearchTermParseEvent($term, $terms);
			send_event($stpe);
			if($stpe->is_querylet_set()) {
				foreach($stpe->get_querylets() as $querylet) {
					$img_querylets[] = new ImgQuerylet($querylet, $positive);
				}
			}
			else {
				$term = str_replace("*", "%", $term);
				$term = str_replace("?", "_", $term);
				if(!preg_match("/^[%_]+$/", $term)) {
					$expansions = Tag::resolve_wildcard($term);
					if($positive) $positive_tag_count++;
					foreach($expansions as $term) {
						$tag_querylets[] = new TagQuerylet($term, $positive);
					}
				}
			}
		}


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
		if(count($tag_querylets) == 0) {
			$query = new Querylet("SELECT images.* FROM images ");

			if(strlen($img_search->sql) > 0) {
				$query->append_sql(" WHERE ");
				$query->append($img_search);
			}
		}

		// one positive tag (a common case), do an optimised search
		else if(count($tag_querylets) == 1 && $tag_querylets[0]->positive) {
			$query = new Querylet($database->engine->scoreql_to_sql("
				SELECT images.* FROM images
				JOIN image_tags ON images.id = image_tags.image_id
				WHERE tag_id = (SELECT tags.id FROM tags WHERE SCORE_STRNORM(tag) = SCORE_STRNORM(?))
				"), array($tag_querylets[0]->tag));

			if(strlen($img_search->sql) > 0) {
				$query->append_sql(" AND ");
				$query->append($img_search);
			}
		}

		// more than one positive tag, or more than zero negative tags
		else {
			$positive_tag_id_array = array();
			$negative_tag_id_array = array();
			$tags_ok = true;

			foreach($tag_querylets as $tq) {
				$tag_ids = $database->db->GetCol(
						$database->engine->scoreql_to_sql(
							"SELECT id FROM tags WHERE SCORE_STRNORM(tag) = SCORE_STRNORM(?)"
						),
						array($tq->tag));
				if($tq->positive) {
					$positive_tag_id_array = array_merge($positive_tag_id_array, $tag_ids);
					$tags_ok = count($tag_ids) > 0;
					if(!$tags_ok) break;
				}
				else {
					$negative_tag_id_array = array_merge($negative_tag_id_array, $tag_ids);
				}
			}

			if($tags_ok) {
				$have_pos = count($positive_tag_id_array) > 0;
				$have_neg = count($negative_tag_id_array) > 0;

				$sql = "SELECT images.* FROM images WHERE ";
				if($have_pos) {
					$positive_tag_id_list = join(', ', $positive_tag_id_array);
					$sql .= "
						images.id IN (
							SELECT image_id
							FROM image_tags
							WHERE tag_id IN ($positive_tag_id_list)
							GROUP BY image_id
							HAVING COUNT(image_id)>=$positive_tag_count
						)
					";
				}
				if($have_pos && $have_neg) {
					$sql .= " AND ";
				}
				if($have_neg) {
					$negative_tag_id_list = join(', ', $negative_tag_id_array);
					$sql .= "
						images.id NOT IN (
							SELECT image_id
							FROM image_tags
							WHERE tag_id IN ($negative_tag_id_list)
						)
					";
				}
				$query = new Querylet($sql);

				if(strlen($img_search->sql) > 0) {
					$query->append_sql(" AND ");
					$query->append($img_search);
				}
			}
			else {
				# one of the positive tags had zero results, therefor there
				# can be no results; "where 1=0" should shortcut things
				$query = new Querylet("
					SELECT images.*
					FROM images
					WHERE 1=0
				");
			}
		}

		return $query;
	}

	/**
	 * this function exists because mysql is a turd, see the docs for
	 * build_accurate_search_querylet() for a full explanation
	 */
	private static function build_ugly_search_querylet($terms) {
		global $config, $database;

		$tag_querylets = array();
		$img_querylets = array();
		$positive_tag_count = 0;
		$negative_tag_count = 0;

		$stpe = new SearchTermParseEvent(null, $terms);
		send_event($stpe);
		if($stpe->is_querylet_set()) {
			foreach($stpe->get_querylets() as $querylet) {
				$img_querylets[] = new ImgQuerylet($querylet, true);
			}
		}

		// turn each term into a specific type of querylet
		foreach($terms as $term) {
			$negative = false;
			if((strlen($term) > 0) && ($term[0] == '-')) {
				$negative = true;
				$term = substr($term, 1);
			}
			
			$term = Tag::resolve_alias($term);

			$stpe = new SearchTermParseEvent($term, $terms);
			send_event($stpe);
			if($stpe->is_querylet_set()) {
				foreach($stpe->get_querylets() as $querylet) {
					$img_querylets[] = new ImgQuerylet($querylet, !$negative);
				}
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
			$query = new Querylet("SELECT images.*,UNIX_TIMESTAMP(posted) AS posted_timestamp FROM images ");

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
				$tag_ids = $database->db->GetCol("SELECT id FROM tags WHERE tag LIKE ?", array($tag));
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
}

/**
 * A class for organising the tag related functions.
 *
 * All the methods are static, one should never actually use a tag object.
 */
class Tag {
	/**
	 * Remove any excess fluff from a user-input tag
	 */
	public static function sanitise($tag) {
		assert(is_string($tag));
		$tag = preg_replace("/[\s?*]/", "", $tag);
		$tag = preg_replace("/\.+/", ".", $tag);
		$tag = preg_replace("/^(\.+[\/\\\\])+/", "", $tag);
		return $tag;
	}

	/**
	 * Turn any string or array into a valid tag array
	 */
	public static function explode($tags) {
		assert(is_string($tags) || is_array($tags));
	
		if(is_string($tags)) {
			$tags = explode(' ', $tags);
		}
		else if(is_array($tags)) {
			// do nothing
		}

		$tags = array_map("trim", $tags);

		$tag_array = array();
		foreach($tags as $tag) {
			if(is_string($tag) && strlen($tag) > 0) {
				$tag_array[] = $tag;
			}
		}

		if(count($tag_array) == 0) {
			$tag_array = array("tagme");
		}

		return $tag_array;
	}

	public static function implode($tags) {
		assert(is_string($tags) || is_array($tags));

		if(is_string($tags)) {
			// do nothing
		}
		else if(is_array($tags)) {
			$tags = implode(' ', $tags);
		}

		return $tags;
	}

	public static function resolve_alias($tag) {
		assert(is_string($tag));

		global $database;
		$newtag = $database->db->GetOne("SELECT newtag FROM aliases WHERE oldtag=?", array($tag));
		if(!empty($newtag)) {
			return $newtag;
		} else {
			return $tag;
		}
	}

	public static function resolve_wildcard($tag) {
		if(strpos($tag, "%") === false && strpos($tag, "_") === false) {
			return array($tag);
		}
		else {
			global $database;
			$newtags = $database->db->GetCol("SELECT tag FROM tags WHERE tag LIKE ?", array($tag));
			if(count($newtags) > 0) {
				$resolved = $newtags;
			} else {
				$resolved = array($tag);
			}
			return $resolved;
		}
	}

	public static function resolve_list($tags) {
		$tags = Tag::explode($tags);
		$new = array();
		foreach($tags as $tag) {
			$new_set = explode(' ', Tag::resolve_alias($tag));
			foreach($new_set as $new_one) {
				$new[] = $new_one;
			}
		}
		$new = array_map(array('Tag', 'sanitise'), $new);
		$new = array_iunique($new); // remove any duplicate tags
		return $new;
	}
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Misc functions                                                            *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * Move a file from PHP's temporary area into shimmie's image storage
 * heirachy, or throw an exception trying
 */
function move_upload_to_archive($event) {
	$target = warehouse_path("images", $event->hash);
	if(!@copy($event->tmpname, $target)) {
		throw new UploadException("Failed to copy file from uploads ({$event->tmpname}) to archive ($target)");
		return false;
	}
	return true;
}

/**
 * Given a full size pair of dimentions, return a pair scaled down to fit
 * into the configured thumbnail square, with ratio intact
 */
function get_thumbnail_size($orig_width, $orig_height) {
	global $config;

	if($orig_width == 0) $orig_width = 192;
	if($orig_height == 0) $orig_height = 192;

	$max_width  = $config->get_int('thumb_width');
	$max_height = $config->get_int('thumb_height');

	$xscale = ($max_height / $orig_height);
	$yscale = ($max_width / $orig_width);
	$scale = ($xscale < $yscale) ? $xscale : $yscale;

	if($scale > 1 && $config->get_bool('thumb_upscale')) {
		return array((int)$orig_width, (int)$orig_height);
	}
	else {
		return array((int)($orig_width*$scale), (int)($orig_height*$scale));
	}
}

?>
