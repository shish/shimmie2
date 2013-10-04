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

$tag_n = 0; // temp hack
$_flexihash = null;
$_fh_last_opts = null;

require_once "lib/flexihash.php";

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
	var $locked;

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
			$this->locked = bool_escape($this->locked);

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
	public static function by_id(/*int*/ $id) {
		assert(is_numeric($id));
		global $database;
		$image = null;
		$row = $database->get_row("SELECT * FROM images WHERE images.id=:id", array("id"=>$id));
		return ($row ? new Image($row) : null);
	}

	/**
	 * Find an image by hash
	 *
	 * @retval Image
	 */
	public static function by_hash(/*string*/ $hash) {
		assert(is_string($hash));
		global $database;
		$image = null;
		$row = $database->get_row("SELECT images.* FROM images WHERE hash=:hash", array("hash"=>$hash));
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
		if ($max < 1) return null;		// From Issue #22 - opened by HungryFeline on May 30, 2011.
		$rand = mt_rand(0, $max-1);
		$set = Image::find_images($rand, 1, $tags);
		if(count($set) > 0) return $set[0];
		else return null;
	}

	/**
	 * Search for an array of images
	 *
	 * @retval Array
	 */
	public static function find_images(/*int*/ $start, /*int*/ $limit, $tags=array()) {
		assert(is_numeric($start));
		assert(is_numeric($limit));
		assert(is_array($tags));
		global $database, $user;

		$images = array();

		if($start < 0) $start = 0;
		if($limit < 1) $limit = 1;

		if(SPEED_HAX) {
			if(!$user->can("big_search") and count($tags) > 3) {
				die("Anonymous users may only search for up to 3 tags at a time"); // FIXME: throw an exception?
			}
		}

		$querylet = Image::build_search_querylet($tags);
		$querylet->append(new Querylet("ORDER BY images.id DESC LIMIT :limit OFFSET :offset", array("limit"=>$limit, "offset"=>$start)));
		#var_dump($querylet->sql); var_dump($querylet->variables);
		$result = $database->execute($querylet->sql, $querylet->variables);

		while($row = $result->fetch()) {
			$images[] = new Image($row);
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
        $tag_count = count($tags);
        
		if($tag_count == 0) {
			$total = $database->cache->get("image-count");
			if(!$total) {
				$total = $database->get_one("SELECT COUNT(*) FROM images");
				$database->cache->set("image-count", $total, 600);
			}
			return $total;
		}
		else if($tag_count == 1 && !preg_match("/[:=><\*\?]/", $tags[0])) {
			$term = Tag::resolve_alias($tags[0]);
			return $database->get_one(
				$database->scoreql_to_sql("SELECT count FROM tags WHERE SCORE_STRNORM(tag) = SCORE_STRNORM(:tag)"),
				array("tag"=>$term));
		}
		else {
			$querylet = Image::build_search_querylet($tags);
			$result = $database->execute($querylet->sql, $querylet->variables);
			return $result->rowCount();
		}
	}

	/**
	 * Count the number of pages for a given search
	 */
	public static function count_pages($tags=array()) {
		assert(is_array($tags));
		global $config, $database;
		return ceil(Image::count_images($tags) / $config->get_int('index_images'));
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
			$row = $database->get_row('SELECT images.* FROM images WHERE images.id '.$gtlt.' '.$this->id.' ORDER BY images.id '.$dir.' LIMIT 1');
		}
		else {
			$tags[] = 'id'. $gtlt . $this->id;
			$querylet = Image::build_search_querylet($tags);
			$querylet->append_sql(' ORDER BY images.id '.$dir.' LIMIT 1');
			$row = $database->get_row($querylet->sql, $querylet->variables);
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
	 * Set the image's owner
	 */
	public function set_owner(User $owner) {
		global $database;
		if($owner->id != $this->owner_id) {
			$database->execute("UPDATE images SET owner_id=:owner_id WHERE id=:id", array("owner_id"=>$owner->id, "id"=>$this->id));
			log_info("core_image", "Owner for Image #{$this->id} set to {$owner->name}", false, array("image_id" => $this->id));
		}
	}

	/**
	 * Get this image's tags as an array
	 */
	public function get_tag_array() {
		global $database;
		if(!isset($this->tag_array)) {
			$this->tag_array = $database->get_col("SELECT tag FROM image_tags JOIN tags ON image_tags.tag_id = tags.id WHERE image_id=:id ORDER BY tag", array("id"=>$this->id));
		}
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

		$image_ilink = $config->get_string('image_ilink');  // store a copy for speed.

		if( !empty($image_ilink) ) {	/* empty is faster than strlen */
			if(!startsWith($image_ilink, "http://") && !startsWith($image_ilink, "/")) {
				$image_ilink = make_link($image_ilink);
			}
			return $this->parse_link_template($image_ilink);
		}
		else if($config->get_bool('nice_urls', false)) {
			return $this->parse_link_template(make_link('_images/$hash/$id%20-%20$tags.$ext'));
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
		
		$image_tlink = $config->get_string('image_tlink'); // store a copy for speed.
		
		if( !empty($image_tlink) ) {	/* empty is faster than strlen */
			if(!startsWith($image_tlink, "http://") && !startsWith($image_tlink, "/")) {
				$image_tlink = make_link($image_tlink);
			}
			return $this->parse_link_template($image_tlink);
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
		$tt = $this->parse_link_template($config->get_string('image_tip'), "no_escape");

		// Removes the size tag if the file is an mp3 
		if($this->ext === 'mp3'){
			$iitip = $tt;
			$mp3tip = array("0x0");
			$h_tip = str_replace($mp3tip, " ", $iitip);

			// Makes it work with a variation of the default tooltips (I.E $tags // $filesize // $size)
			$justincase = array("   //", "//   ", "  //", "//  ", "  ");
			if(strstr($h_tip, "  ")) {
				$h_tip = html_escape(str_replace($justincase, "", $h_tip));
			}else{
				$h_tip = html_escape($h_tip);
			}
			return $h_tip;
		}
		else {
			return $tt;
		}
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
	 * @retval string
	 */
	public function get_mime_type() {
		return getMimeType($this->get_image_filename(), $this->get_ext());
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
	public function set_source(/*string*/ $new_source) {
		global $database;
		$old_source = $this->source;
		if(empty($new_source)) $new_source = null;
		if($new_source != $old_source) {
			$database->execute("UPDATE images SET source=:source WHERE id=:id", array("source"=>$new_source, "id"=>$this->id));
			log_info("core_image", "Source for Image #{$this->id} set to: $new_source (was $old_source)", false, array("image_id" => $this->id));
		}
	}

	/**
	 * Check if the image is locked.
	 * @retval bool
	 */
	public function is_locked() {
		return $this->locked;
	}

	public function set_locked($tf) {
		global $database;
		$ln = $tf ? "Y" : "N";
		$sln = $database->scoreql_to_sql('SCORE_BOOL_'.$ln);
		$sln = str_replace("'", "", $sln);
		$sln = str_replace('"', "", $sln);
		if(bool_escape($sln) !== $this->locked) {
			$database->execute("UPDATE images SET locked=:yn WHERE id=:id", array("yn"=>$sln, "id"=>$this->id));
			log_info("core_image", "Setting Image #{$this->id} lock to: $ln", false, array("image_id" => $this->id));
		}
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
				"(SELECT tag_id FROM image_tags WHERE image_id = :id)", array("id"=>$this->id));
		$database->execute("DELETE FROM image_tags WHERE image_id=:id", array("id"=>$this->id));
	}

	/**
	 * Set the tags for this image
	 */
	public function set_tags($tags) {
		global $database;

		assert(is_array($tags));

		$tags = array_map(array('Tag', 'sanitise'), $tags);
		$tags = Tag::resolve_aliases($tags);

		assert(is_array($tags));
		assert(count($tags) > 0);
		$new_tags = implode(" ", $tags);

		if($new_tags != $this->get_tag_list()) {
			// delete old
			$this->delete_tags_from_image();
			// insert each new tags
			foreach($tags as $tag) {
				$id = $database->get_one(
						$database->scoreql_to_sql(
							"SELECT id FROM tags WHERE SCORE_STRNORM(tag) = SCORE_STRNORM(:tag)"
						),
						array("tag"=>$tag));
				if(empty($id)) {
					// a new tag
					$database->execute(
							"INSERT INTO tags(tag) VALUES (:tag)",
							array("tag"=>$tag));
					$database->execute(
							"INSERT INTO image_tags(image_id, tag_id)
							VALUES(:id, (SELECT id FROM tags WHERE tag = :tag))",
							array("id"=>$this->id, "tag"=>$tag));
				}
				else {
					// user of an existing tag
					$database->execute(
							"INSERT INTO image_tags(image_id, tag_id) VALUES(:iid, :tid)",
							array("iid"=>$this->id, "tid"=>$id));
				}
				$database->execute(
						$database->scoreql_to_sql(
							"UPDATE tags SET count = count + 1 WHERE SCORE_STRNORM(tag) = SCORE_STRNORM(:tag)"
						),
						array("tag"=>$tag));
			}

			log_info("core_image", "Tags for Image #{$this->id} set to: ".implode(" ", $tags), false, array("image_id" => $this->id));
			$database->cache->delete("image-{$this->id}-tags");
		}
	}

	/**
	 * Delete this image from the database and disk
	 */
	public function delete() {
		global $database;
		$this->delete_tags_from_image();
		$database->execute("DELETE FROM images WHERE id=:id", array("id"=>$this->id));
		log_info("core_image", 'Deleted Image #'.$this->id.' ('.$this->hash.')', false, array("image_id" => $this->id));

		unlink($this->get_image_filename());
		unlink($this->get_thumb_filename());
	}

	/**
	 * This function removes an image (and thumbnail) from the DISK ONLY.
	 * It DOES NOT remove anything from the database.
	 */
	public function remove_image_only() {
		log_info("core_image", 'Removed Image File ('.$this->hash.')', false, array("image_id" => $this->id));
		@unlink($this->get_image_filename());
		@unlink($this->get_thumb_filename());
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
		$tmpl = str_replace('$date', $_escape(autodate($this->posted)), $tmpl);

		// nothing seems to use this, sending the event out to 50 exts is a lot of overhead
		if(!SPEED_HAX) {
			$plte = new ParseLinkTemplateEvent($tmpl, $this);
			send_event($plte);
			$tmpl = $plte->link;
		}

		global $_flexihash, $_fh_last_opts;
		$matches = array();
		if(preg_match("/(.*){(.*)}(.*)/", $tmpl, $matches)) {
			$pre = $matches[1];
			$opts = $matches[2];
			$post = $matches[3];

			if($opts != $_fh_last_opts) {
				$_fh_last_opts = $opts;
				$_flexihash = new Flexihash();
				foreach(explode(",", $opts) as $opt) {
					$parts = explode("=", $opt);
                    $parts_count = count($parts);
					$opt_val = "";
					$opt_weight = 0;
					if($parts_count == 2) {
						$opt_val = $parts[0];
						$opt_weight = $parts[1];
					}
					elseif($parts_count == 1) {
						$opt_val = $parts[0];
						$opt_weight = 1;
					}
					$_flexihash->addTarget($opt_val, $opt_weight);
				}
			}

			$choice = $_flexihash->lookup($pre.$post);
			$tmpl = $pre.$choice.$post;
		}

		return $tmpl;
	}

	private static function build_search_querylet($terms) {
		assert(is_array($terms));
		global $database;
		if($database->get_driver_name() === "mysql")
			return Image::build_ugly_search_querylet($terms);
		else
			return Image::build_accurate_search_querylet($terms);
	}

	/**
	 * WARNING: this description is no longer accurate, though it does get across
	 * the general idea - the actual method has a few extra optimisiations
	 *
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
			if(is_string($term) && !empty($term) && ($term[0] == '-')) {
				$positive = false;
				$term = substr($term, 1);
			}
			if(strlen($term) === 0) {
				continue;
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
				$expansions = Tag::resolve_wildcard($term);
				if($expansions && $positive) $positive_tag_count++;
				foreach($expansions as $term) {
					$tag_querylets[] = new TagQuerylet($term, $positive);
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

			if(!empty($img_search->sql)) {
				$query->append_sql(" WHERE ");
				$query->append($img_search);
			}
		}

		// one positive tag (a common case), do an optimised search
		else if(count($tag_querylets) == 1 && $tag_querylets[0]->positive) {
			$query = new Querylet($database->scoreql_to_sql("
				SELECT images.* FROM images
				JOIN image_tags ON images.id=image_tags.image_id
				JOIN tags ON image_tags.tag_id=tags.id
				WHERE SCORE_STRNORM(tag) = SCORE_STRNORM(:tag)
				"), array("tag"=>$tag_querylets[0]->tag));

			if(!empty($img_search->sql)) {
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
				$tag_ids = $database->get_col(
						$database->scoreql_to_sql(
							"SELECT id FROM tags WHERE SCORE_STRNORM(tag) = SCORE_STRNORM(:tag)"
						),
						array("tag"=>$tq->tag));
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

				$sql = "SELECT images.* FROM images WHERE images.id IN (";
				if($have_pos) {
					$positive_tag_id_list = join(', ', $positive_tag_id_array);
					$sql .= "
						SELECT image_id
						FROM image_tags
						WHERE tag_id IN ($positive_tag_id_list)
						GROUP BY image_id
						HAVING COUNT(image_id)>=$positive_tag_count
					";
				}
				if($have_pos && $have_neg) {
					$sql .= " EXCEPT ";
				}
				if($have_neg) {
					$negative_tag_id_list = join(', ', $negative_tag_id_array);
					$sql .= "
						SELECT image_id
						FROM image_tags
						WHERE tag_id IN ($negative_tag_id_list)
					";
				}
				$sql .= ")";
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

		reset($terms); // rewind to first element in array.
		
		// turn each term into a specific type of querylet
		foreach($terms as $term) {
			$negative = false;
			if( !empty($term) && ($term[0] == '-')) {
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
			global $tag_n;
			$sign = $tq->positive ? "+" : "-";
			//$sql .= " $sign (tag LIKE :tag$tag_n)";
			$sql .= ' '.$sign.' (tag LIKE :tag'.$tag_n.')';
			//$terms["tag$tag_n"] = $tq->tag;
			$terms['tag'.$tag_n] = $tq->tag;
			$tag_n++;
			
			if($sign === "+") $positive_tag_count++;
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

			if(!empty($img_search->sql)) {
				$query->append_sql(" WHERE ");
				$query->append($img_search);
			}
		}

		// one positive tag (a common case), do an optimised search
		else if($positive_tag_count === 1 && $negative_tag_count === 0) {
			$query = new Querylet(
				// MySQL is braindead, and does a full table scan on images, running the subquery once for each row -_-
				// "{$this->get_images} WHERE images.id IN (SELECT image_id FROM tags WHERE tag LIKE ?) ",
				"
					SELECT images.*, UNIX_TIMESTAMP(posted) AS posted_timestamp
					FROM tags, image_tags, images
					WHERE
						tag LIKE :tag0
						AND tags.id = image_tags.tag_id
						AND image_tags.image_id = images.id
				",
				$tag_search->variables);

			if(!empty($img_search->sql)) {
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
				$tag_ids = $database->get_col("SELECT id FROM tags WHERE tag LIKE :tag", array("tag"=>$tag));
				$tag_id_array = array_merge($tag_id_array, $tag_ids);
				$tags_ok = count($tag_ids) > 0;
				if(!$tags_ok) break;
			}
			if($tags_ok) {
				$tag_id_list = join(', ', $tag_id_array);

				$subquery = new Querylet('
					SELECT images.*, SUM('.$tag_search->sql.') AS score
					FROM images
					LEFT JOIN image_tags ON image_tags.image_id = images.id
					JOIN tags ON image_tags.tag_id = tags.id
					WHERE tags.id IN ('.$tag_id_list.')
					GROUP BY images.id
					HAVING score = :score',
					array_merge(
						$tag_search->variables,
						array("score"=>$positive_tag_count)
					)
				);
				$query = new Querylet('
					SELECT *, UNIX_TIMESTAMP(posted) AS posted_timestamp
					FROM ('.$subquery->sql.') AS images ', $subquery->variables);

				if(!empty($img_search->sql)) {
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

		$tag_n = 0;
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
	public static function explode($tags, $tagme=true) {
		assert(is_string($tags) || is_array($tags));
	
		if(is_string($tags)) {
			$tags = explode(' ', trim($tags));
		}
		//else if(is_array($tags)) {
			// do nothing
		//}

		$tag_array = array();
		foreach($tags as $tag) {
			$tag = trim($tag, ", \t\n\r\0\x0B");
			if(is_string($tag) && !empty($tag)) {
				$tag_array[] = $tag;
			}
		}

		if(count($tag_array) == 0 && $tagme) {
			$tag_array = array("tagme");
		}

		sort($tag_array);

		return $tag_array;
	}

	public static function implode($tags) {
		assert(is_string($tags) || is_array($tags));

		if(is_array($tags)) {
			sort($tags);
			$tags = implode(' ', $tags);
		}
		//else if(is_string($tags)) {
			// do nothing
		//}

		return $tags;
	}

	public static function resolve_alias($tag) {
		assert(is_string($tag));

		$negative = false;
		if(!empty($tag) && ($tag[0] == '-')) {
			$negative = true;
			$tag = substr($tag, 1);
		}

		global $database;
		$newtag = $database->get_one(
			$database->scoreql_to_sql("SELECT newtag FROM aliases WHERE SCORE_STRNORM(oldtag)=SCORE_STRNORM(:tag)"),
			array("tag"=>$tag));
		if(empty($newtag)) {
			$newtag = $tag;
		}
		return $negative ? "-$newtag" : $newtag;
	}

	public static function resolve_wildcard($tag) {
		// if there is no wildcard, return the tag
		if(strpos($tag, "*") === false) {
			return array($tag);
		}

		// if the whole match is wild, return null to save the database
		else if(str_replace("*", "", $tag) == "") {
			return array();
		}

		// else find some matches
		else {
			global $database;
			$db_wild_tag = str_replace("%", "\%", $tag);
			$db_wild_tag = str_replace("*", "%", $tag);
			$newtags = $database->get_col($database->scoreql_to_sql("SELECT tag FROM tags WHERE SCORE_STRNORM(tag) LIKE SCORE_STRNORM(?)"), array($db_wild_tag));
			if(count($newtags) > 0) {
				$resolved = $newtags;
			} else {
				$resolved = array($tag);
			}
			return $resolved;
		}
	}

	/**
	 * This function takes a list (array) of tags and changes any tags that have aliases
	 *
	 * @param $tags Array of tags
	 * @return Array of tags
	 */
	public static function resolve_aliases($tags) {
		assert(is_array($tags));

		$new = array();
		foreach($tags as $tag) {
			$new_set = explode(' ', Tag::resolve_alias($tag));
			foreach($new_set as $new_one) {
				$new[] = $new_one;
			}
		}

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
function move_upload_to_archive(DataUploadEvent $event) {
	$target = warehouse_path("images", $event->hash);
	if(!@copy($event->tmpname, $target)) {
		$errors = error_get_last(); // note: requires php 5.2
		throw new UploadException("Failed to copy file from uploads ({$event->tmpname}) to archive ($target): {$errors['type']} / {$errors['message']}");
		return false;
	}
	return true;
}

/**
 * Given a full size pair of dimentions, return a pair scaled down to fit
 * into the configured thumbnail square, with ratio intact
 */
function get_thumbnail_size(/*int*/ $orig_width, /*int*/ $orig_height) {
	global $config;

	if($orig_width == 0) $orig_width = 192;
	if($orig_height == 0) $orig_height = 192;

	if($orig_width > $orig_height * 5) $orig_width = $orig_height * 5;
	if($orig_height > $orig_width * 5) $orig_height = $orig_width * 5;

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
