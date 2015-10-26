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


require_once "lib/flexihash.php";

/**
 * Class Image
 *
 * An object representing an entry in the images table.
 *
 * As of 2.2, this no longer necessarily represents an
 * image per se, but could be a video, sound file, or any
 * other supported upload type.
 */
class Image {
	private static $tag_n = 0; // temp hack
	public static $order_sql = null; // this feels ugly

	/** @var null|int */
	public $id = null;

	/** @var int */
	public $height;

	/** @var int */
	public $width;

	/** @var string */
	public $hash;

	public $filesize;

	/** @var string */
	public $filename;

	/** @var string */
	public $ext;

	/** @var string[]|null */
	public $tag_array;

	public $owner_id, $owner_ip;
	public $posted;
	public $source;
	public $locked;

	/**
	 * One will very rarely construct an image directly, more common
	 * would be to use Image::by_id, Image::by_hash, etc.
	 *
	 * @param null|mixed $row
	 */
	public function __construct($row=null) {
		assert('is_null($row) || is_array($row)');

		if(!is_null($row)) {
			foreach($row as $name => $value) {
				// some databases use table.name rather than name
				$name = str_replace("images.", "", $name);
				$this->$name = $value; // hax, this is likely the cause of much scrutinizer-ci complaints.
			}
			$this->locked = bool_escape($this->locked);

			assert(is_numeric($this->id));
			assert(is_numeric($this->height));
			assert(is_numeric($this->width));
		}
	}

	/**
	 * Find an image by ID.
	 *
	 * @param int $id
	 * @return Image
	 */
	public static function by_id(/*int*/ $id) {
		assert('is_numeric($id)');
		global $database;
		$row = $database->get_row("SELECT * FROM images WHERE images.id=:id", array("id"=>$id));
		return ($row ? new Image($row) : null);
	}

	/**
	 * Find an image by hash.
	 *
	 * @param string $hash
	 * @return Image
	 */
	public static function by_hash(/*string*/ $hash) {
		assert('is_string($hash)');
		global $database;
		$row = $database->get_row("SELECT images.* FROM images WHERE hash=:hash", array("hash"=>$hash));
		return ($row ? new Image($row) : null);
	}

	/**
	 * Pick a random image out of a set.
	 *
	 * @param string[] $tags
	 * @return Image
	 */
	public static function by_random($tags=array()) {
		assert('is_array($tags)');
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
	 * @param int $start
	 * @param int $limit
	 * @param string[] $tags
	 * @throws SCoreException
	 * @return Image[]
	 */
	public static function find_images(/*int*/ $start, /*int*/ $limit, $tags=array()) {
		assert('is_numeric($start)');
		assert('is_numeric($limit)');
		assert('is_array($tags)');
		global $database, $user, $config;

		$images = array();

		if($start < 0) $start = 0;
		if($limit < 1) $limit = 1;

		if(SPEED_HAX) {
			if(!$user->can("big_search") and count($tags) > 3) {
				throw new SCoreException("Anonymous users may only search for up to 3 tags at a time");
			}
		}

		$result = null;
		if(SEARCH_ACCEL) {
			$result = Image::get_accelerated_result($tags, $start, $limit);
		}

		if(!$result) {
			$querylet = Image::build_search_querylet($tags);
			$querylet->append(new Querylet(" ORDER BY ".(Image::$order_sql ?: "images.".$config->get_string("index_order"))));
			$querylet->append(new Querylet(" LIMIT :limit OFFSET :offset", array("limit"=>$limit, "offset"=>$start)));
			#var_dump($querylet->sql); var_dump($querylet->variables);
			$result = $database->execute($querylet->sql, $querylet->variables);
		}

		while($row = $result->fetch()) {
			$images[] = new Image($row);
		}
		Image::$order_sql = null;
		return $images;
	}

	public function validate_accel($tags) {
		$yays = 0;
		$nays = 0;
		foreach($tags as $tag) {
			if(!preg_match("/^-?[a-zA-Z0-9_]+$/", $tag)) {
				return false;
			}
			if($tag[0] == "-") $nays++;
			else $yays++;
		}
		return ($yays > 1 || $nays > 0);
	}

	/**
	 * @param string[] $tags
	 * @param int $offset
	 * @param int $limit
	 * @return null|PDOStatement
	 * @throws SCoreException
	 */
	public function get_accelerated_result($tags, $offset, $limit) {
		global $database;

		$tags = Tag::resolve_aliases($tags);
		if(!Image::validate_accel($tags)) {
			return null;
		}

		$yays = array();
		$nays = array();
		foreach($tags as $tag) {
			if($tag[0] == "-") {
				$nays[] = substr($tag, 1);
			}
			else {
				$yays[] = $tag;
			}
		}
		$req = array(
			"yays" => $yays,
			"nays" => $nays,
			"offset" => $offset,
			"limit" => $limit,
		);

		$fp = fsockopen("127.0.0.1", 21212);
		if (!$fp) {
			return null;
		}
		fwrite($fp, json_encode($req));
		$data = fgets($fp, 1024);
		fclose($fp);

		$response = json_decode($data);
		$list = implode(",", $response);
		if($list) {
			$result = $database->execute("SELECT * FROM images WHERE id IN ($list) ORDER BY images.id DESC");
		}
		else {
			$result = $database->execute("SELECT * FROM images WHERE 1=0 ORDER BY images.id DESC");
		}
		return $result;
	}

	/*
	 * Image-related utility functions
	 */

	/**
	 * Count the number of image results for a given search
	 *
	 * @param string[] $tags
	 * @return int
	 */
	public static function count_images($tags=array()) {
		assert('is_array($tags)');
		global $database;
		$tag_count = count($tags);

		if($tag_count === 0) {
			$total = $database->cache->get("image-count");
			if(!$total) {
				$total = $database->get_one("SELECT COUNT(*) FROM images");
				$database->cache->set("image-count", $total, 600);
			}
			return $total;
		}
		else if($tag_count === 1 && !preg_match("/[:=><\*\?]/", $tags[0])) {
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
	 *
	 * @param string[] $tags
	 * @return float
	 */
	public static function count_pages($tags=array()) {
		assert('is_array($tags)');
		global $config;
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
	 * @param string[] $tags
	 * @param bool $next
	 * @return Image
	 */
	public function get_next($tags=array(), $next=true) {
		assert('is_array($tags)');
		assert('is_bool($next)');
		global $database;

		if($next) {
			$gtlt = "<";
			$dir = "DESC";
		}
		else {
			$gtlt = ">";
			$dir = "ASC";
		}

		if(count($tags) === 0) {
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
	 * @param string[] $tags
	 * @return Image
	 */
	public function get_prev($tags=array()) {
		return $this->get_next($tags, false);
	}

	/**
	 * Find the User who owns this Image
	 *
	 * @return User
	 */
	public function get_owner() {
		return User::by_id($this->owner_id);
	}

	/**
	 * Set the image's owner.
	 *
	 * @param User $owner
	 */
	public function set_owner(User $owner) {
		global $database;
		if($owner->id != $this->owner_id) {
			$database->execute("UPDATE images SET owner_id=:owner_id WHERE id=:id", array("owner_id"=>$owner->id, "id"=>$this->id));
			log_info("core_image", "Owner for Image #{$this->id} set to {$owner->name}", false, array("image_id" => $this->id));
		}
	}

	/**
	 * Get this image's tags as an array.
	 *
	 * @return string[]
	 */
	public function get_tag_array() {
		global $database;
		if(!isset($this->tag_array)) {
			$this->tag_array = $database->get_col("SELECT tag FROM image_tags JOIN tags ON image_tags.tag_id = tags.id WHERE image_id=:id ORDER BY tag", array("id"=>$this->id));
		}
		return $this->tag_array;
	}

	/**
	 * Get this image's tags as a string.
	 *
	 * @return string
	 */
	public function get_tag_list() {
		return Tag::implode($this->get_tag_array());
	}

	/**
	 * Get the URL for the full size image
	 *
	 * @return string
	 */
	public function get_image_link() {
		return $this->get_link('image_ilink', '_images/$hash/$id%20-%20$tags.$ext', 'image/$id.jpg');
	}

	/**
	 * Get the URL for the thumbnail
	 *
	 * @return string
	 */
	public function get_thumb_link() {
		return $this->get_link('image_tlink', '_thumbs/$hash/thumb.jpg', 'thumb/$id.jpg');
	}

	/**
	 * Check configured template for a link, then try nice URL, then plain URL
	 *
	 * @param string $template
	 * @param string $nice
	 * @param string $plain
	 * @return string
	 */
	private function get_link($template, $nice, $plain) {
		global $config;

		$image_link = $config->get_string($template);

		if(!empty($image_link)) {
			if(!(strpos($image_link, "://") > 0) && !startsWith($image_link, "/")) {
				$image_link = make_link($image_link);
			}
			return $this->parse_link_template($image_link);
		}
		else if($config->get_bool('nice_urls', false)) {
			return $this->parse_link_template(make_link($nice));
		}
		else {
			return $this->parse_link_template(make_link($plain));
		}
	}

	/**
	 * Get the tooltip for this image, formatted according to the
	 * configured template.
	 *
	 * @return string
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
	 * Figure out where the full size image is on disk.
	 *
	 * @return string
	 */
	public function get_image_filename() {
		return warehouse_path("images", $this->hash);
	}

	/**
	 * Figure out where the thumbnail is on disk.
	 *
	 * @return string
	 */
	public function get_thumb_filename() {
		return warehouse_path("thumbs", $this->hash);
	}

	/**
	 * Get the original filename.
	 *
	 * @return string
	 */
	public function get_filename() {
		return $this->filename;
	}

	/**
	 * Get the image's mime type.
	 *
	 * @return string
	 */
	public function get_mime_type() {
		return getMimeType($this->get_image_filename(), $this->get_ext());
	}

	/**
	 * Get the image's filename extension
	 *
	 * @return string
	 */
	public function get_ext() {
		return $this->ext;
	}

	/**
	 * Get the image's source URL
	 *
	 * @return string
	 */
	public function get_source() {
		return $this->source;
	}

	/**
	 * Set the image's source URL
	 *
	 * @param string $new_source
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
	 * @return bool
	 */
	public function is_locked() {
		return $this->locked;
	}

	/**
	 * @param bool $tf
	 * @throws SCoreException
	 */
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
	 * Set the tags for this image.
	 *
	 * @param string[] $tags
	 * @throws Exception
	 */
	public function set_tags($tags) {
		assert('is_array($tags) && count($tags) > 0', var_export($tags, true));
		global $database;

		$tags = array_map(array('Tag', 'sanitise'), $tags);
		$tags = Tag::resolve_aliases($tags);

		if(count($tags) <= 0) {
			throw new SCoreException('Tried to set zero tags');
		}

		if(implode(" ", $tags) != $this->get_tag_list()) {
			// delete old
			$this->delete_tags_from_image();
			// insert each new tags
			foreach($tags as $tag) {
				$ttpe = new TagTermParseEvent($tag, $this->id);
				send_event($ttpe);
				if($ttpe->is_metatag()) {
					continue;
				}

				if(mb_strlen($tag, 'UTF-8') > 255){
					flash_message("The tag below is longer than 255 characters, please use a shorter tag.\n$tag\n");
					continue;
				}

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

			log_info("core_image", "Tags for Image #{$this->id} set to: ".implode(" ", $tags), null, array("image_id" => $this->id));
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
	 * @param string $tmpl
	 * @param string $_escape
	 * @return string
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
		$tmpl = str_replace('$date', $_escape(autodate($this->posted, false)), $tmpl);

		// nothing seems to use this, sending the event out to 50 exts is a lot of overhead
		if(!SPEED_HAX) {
			$plte = new ParseLinkTemplateEvent($tmpl, $this);
			send_event($plte);
			$tmpl = $plte->link;
		}

		static $flexihash = null;
		static $fh_last_opts = null;
		$matches = array();
		if(preg_match("/(.*){(.*)}(.*)/", $tmpl, $matches)) {
			$pre = $matches[1];
			$opts = $matches[2];
			$post = $matches[3];

			if($opts != $fh_last_opts) {
				$fh_last_opts = $opts;
				$flexihash = new Flexihash();
				foreach(explode(",", $opts) as $opt) {
					$parts = explode("=", $opt);
					$parts_count = count($parts);
					$opt_val = "";
					$opt_weight = 0;
					if($parts_count === 2) {
						$opt_val = $parts[0];
						$opt_weight = $parts[1];
					}
					elseif($parts_count === 1) {
						$opt_val = $parts[0];
						$opt_weight = 1;
					}
					$flexihash->addTarget($opt_val, $opt_weight);
				}
			}

			$choice = $flexihash->lookup($pre.$post);
			$tmpl = $pre.$choice.$post;
		}

		return $tmpl;
	}

	/**
	 * @param string[] $terms
	 * @return \Querylet
	 */
	private static function build_search_querylet($terms) {
		assert('is_array($terms)');
		global $database;
		if($database->get_driver_name() === "mysql")
			return Image::build_ugly_search_querylet($terms);
		else
			return Image::build_accurate_search_querylet($terms);
	}

	/**
	 * @param string[] $terms
	 * @return ImgQuerylet[]
	 */
	private static function parse_meta_terms($terms) {
		$img_querylets = array();
		$stpe = new SearchTermParseEvent(null, $terms);
		send_event($stpe);
		if ($stpe->is_querylet_set()) {
			foreach ($stpe->get_querylets() as $querylet) {
				$img_querylets[] = new ImgQuerylet($querylet, true);
			}
		}
		return $img_querylets;
	}

	/**
	 * @param ImgQuerylet[] $img_querylets
	 * @return Querylet
	 */
	private static function build_img_search($img_querylets) {
		// merge all the image metadata searches into one generic querylet
		$n = 0;
		$sql = "";
		$terms = array();
		foreach ($img_querylets as $iq) {
			if ($n++ > 0) $sql .= " AND";
			if (!$iq->positive) $sql .= " NOT";
			$sql .= " (" . $iq->qlet->sql . ")";
			$terms = array_merge($terms, $iq->qlet->variables);
		}
		return new Querylet($sql, $terms);
	}

	/**
	 * @param Querylet $img_search
	 * @return Querylet
	 */
	private static function build_simple_query($img_search) {
		$query = new Querylet("SELECT images.* FROM images ");

		if (!empty($img_search->sql)) {
			$query->append_sql(" WHERE ");
			$query->append($img_search);
			return $query;
		}
		return $query;
	}

	/**
	 * WARNING: this description is no longer accurate, though it does get across
	 * the general idea - the actual method has a few extra optimisations
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
	 *      These lists are calculated once, and the set intersection taken
	 *   C) Runs really slow on bad databases:
	 *      All the subqueries are executed every time for every row in the
	 *      images table. Yes, MySQL does suck this much.
	 *
	 * @param string[] $terms
	 * @return \Querylet
	 */
	private static function build_accurate_search_querylet($terms) {
		global $database;

		$tag_querylets = array();
		$img_querylets = self::parse_meta_terms($terms);
		$positive_tag_count = 0;

		// parse the words that are searched for into
		// various types of querylet
		$terms = Tag::resolve_aliases($terms);
		foreach($terms as $term) {
			$positive = true;
			if(is_string($term) && !empty($term) && ($term[0] == '-')) {
				$positive = false;
				$term = substr($term, 1);
			}
			if(strlen($term) === 0) {
				continue;
			}

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
				foreach($expansions as $expanded_term) {
					$tag_querylets[] = new TagQuerylet($expanded_term, $positive);
				}
			}
		}
		$img_search = self::build_img_search($img_querylets);

		// How many tag querylets are there?
		$count_tag_querylets = count($tag_querylets);

		// no tags, do a simple search (+image metadata if we have any)
		if($count_tag_querylets === 0) {
			$query = self::build_simple_query($img_search);
		}

		// one positive tag (a common case), do an optimised search
		else if($count_tag_querylets === 1 && $tag_querylets[0]->positive) {
			$query = new Querylet($database->scoreql_to_sql("
				SELECT images.*
				FROM images
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
					$database->scoreql_to_sql("
						SELECT id
						FROM tags
						WHERE SCORE_STRNORM(tag) = SCORE_STRNORM(:tag)
					"), array("tag"=>$tq->tag)
				);
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

				$sql = "";
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
				$query = new Querylet("
					SELECT images.*
					FROM images
					WHERE images.id IN ($sql)
				");

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
	 *
	 * @param array $terms
	 * @return Querylet
	 */
	private static function build_ugly_search_querylet($terms) {
		global $database;

		$tag_querylets = array();
		$img_querylets = self::parse_meta_terms($terms);
		$positive_tag_count = 0;
		$negative_tag_count = 0;

		$terms = Tag::resolve_aliases($terms);

		reset($terms); // rewind to first element in array.

		// turn each term into a specific type of querylet
		foreach($terms as $term) {
			$negative = false;
			if( !empty($term) && ($term[0] == '-')) {
				$negative = true;
				$term = substr($term, 1);
			}

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
			$sql .= ' '.$sign.' (tag LIKE :tag'.Image::$tag_n.')';
			$terms['tag'.Image::$tag_n] = $tq->tag;
			Image::$tag_n++;

			if($sign === "+") $positive_tag_count++;
			else $negative_tag_count++;
		}
		$tag_search = new Querylet($sql, $terms);
		$img_search = self::build_img_search($img_querylets);

		// no tags, do a simple search (+image metadata if we have any)
		if($positive_tag_count + $negative_tag_count == 0) {
			$query = self::build_simple_query($img_search);
		}

		// one positive tag (a common case), do an optimised search
		else if($positive_tag_count === 1 && $negative_tag_count === 0) {
			// MySQL is braindead, and does a full table scan on images, running the subquery once for each row -_-
			// "{$this->get_images} WHERE images.id IN (SELECT image_id FROM tags WHERE tag LIKE ?) ",
			$query = new Querylet("
				SELECT images.*
				FROM images
				JOIN image_tags ON images.id=image_tags.image_id
				JOIN tags ON image_tags.tag_id=tags.id
				WHERE tag LIKE :tag0
			", $tag_search->variables);

			if(!empty($img_search->sql)) {
				$query->append_sql(" AND ");
				$query->append($img_search);
			}
		}

		// more than one positive tag, and zero or more negative tags
		else if($positive_tag_count >= 1) {
			$tag_id_array = array();
			$tags_ok = true;

			$x = 0;
			foreach($tag_search->variables as $tag) {
				$tag_ids = $database->get_col(
					"SELECT id FROM tags WHERE tag LIKE :tag",
					array("tag"=>$tag)
				);
				$tag_id_array = array_merge($tag_id_array, $tag_ids);

				$tags_ok = count($tag_ids) > 0 || !$tag_querylets[$x]->positive;
				if(!$tags_ok) break;

				$x++;
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
					SELECT *
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

		//zero positive tags and one or more negative tags
		//TODO: This isn't currently implemented. SEE: https://github.com/shish/shimmie2/issues/66
		else{
			$query = new Querylet("
				SELECT images.*
				FROM images
				WHERE 1=0
			");
		}
		Image::$tag_n = 0;
		return $query;
	}
}

/**
 * Class Tag
 *
 * A class for organising the tag related functions.
 *
 * All the methods are static, one should never actually use a tag object.
 *
 */
class Tag {
	/**
	 * Remove any excess fluff from a user-input tag
	 *
	 * @param string $tag
	 * @return string
	 */
	public static function sanitise($tag) {
		assert('is_string($tag)');
		$tag = preg_replace("/[\s?*]/", "", $tag);            # whitespace
		$tag = preg_replace('/\x20(\x0e|\x0f)/', '', $tag);   # unicode RTL
		$tag = preg_replace("/\.+/", ".", $tag);              # strings of dots?
		$tag = preg_replace("/^(\.+[\/\\\\])+/", "", $tag);   # trailing slashes?
		return $tag;
	}

	/**
	 * Turn any string or array into a valid tag array.
	 *
	 * @param string|string[] $tags
	 * @param bool $tagme
	 * @return string[]
	 */
	public static function explode($tags, $tagme=true) {
		assert('is_string($tags) || is_array($tags)');

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

		if(count($tag_array) === 0 && $tagme) {
			$tag_array = array("tagme");
		}

		sort($tag_array);

		return $tag_array;
	}

	/**
	 * @param string|string[] $tags
	 * @return string
	 */
	public static function implode($tags) {
		assert('is_string($tags) || is_array($tags)');

		if(is_array($tags)) {
			sort($tags);
			$tags = implode(' ', $tags);
		}
		//else if(is_string($tags)) {
			// do nothing
		//}

		return $tags;
	}

	/**
	 * @param string $tag
	 * @return string
	 */
	public static function resolve_alias($tag) {
		assert('is_string($tag)');
		global $database;

		$negative = false;
		if(!empty($tag) && ($tag[0] == '-')) {
			$negative = true;
			$tag = substr($tag, 1);
		}


		$newtag = $database->get_one(
			$database->scoreql_to_sql("SELECT newtag FROM aliases WHERE SCORE_STRNORM(oldtag)=SCORE_STRNORM(:tag)"),
			array("tag"=>$tag)
		);

		if(empty($newtag)) {
			//tag has no alias, use old tag
			$newtag = $tag;
		}

		return !$negative ? $newtag : preg_replace("/(\S+)/", "-$1", $newtag);
	}

	/**
	 * @param string $tag
	 * @return array
	 */
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
			$db_wild_tag = str_replace("*", "%", $db_wild_tag);
			$newtags = $database->get_col(
				$database->scoreql_to_sql("SELECT tag FROM tags WHERE SCORE_STRNORM(tag) LIKE SCORE_STRNORM(?)"),
				array($db_wild_tag)
			);
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
	 * @param string[] $tags Array of tags
	 * @return array
	 */
	public static function resolve_aliases($tags) {
		assert('is_array($tags)');

		$new = array();

		$i = 0;
		$tag_count = count($tags);
		while($i<$tag_count) {
			$aliases = explode(' ', Tag::resolve_alias($tags[$i]));
			foreach($aliases as $alias){
				if(!in_array($alias, $new)){
					if($tags[$i] == $alias){
						$new[] = $alias;
					}elseif(!in_array($alias, $tags)){
						$tags[] = $alias;
						$tag_count++;
					}
				}
			}
			$i++;
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
 * hierarchy, or throw an exception trying.
 *
 * @param DataUploadEvent $event
 * @return bool
 * @throws UploadException
 */
function move_upload_to_archive(DataUploadEvent $event) {
	$target = warehouse_path("images", $event->hash);
	if(!@copy($event->tmpname, $target)) {
		$errors = error_get_last(); // note: requires php 5.2
		throw new UploadException("Failed to copy file from uploads ({$event->tmpname}) to archive ($target): {$errors['type']} / {$errors['message']}");
	}
	return true;
}

/**
 * Add a directory full of images
 *
 * @param $base string
 * @return array
 */
function add_dir($base) {
    $results = array();

    foreach(list_files($base) as $full_path) {
        $short_path = str_replace($base, "", $full_path);
        $filename = basename($full_path);

        $tags = path_to_tags($short_path);
        $result = "$short_path (".str_replace(" ", ", ", $tags).")... ";
        try {
            add_image($full_path, $filename, $tags);
            $result .= "ok";
        }
        catch(UploadException $ex) {
            $result .= "failed: ".$ex->getMessage();
        }
        $results[] = $result;
    }

    return $results;
}

/**
 * @param string $tmpname
 * @param string $filename
 * @param string $tags
 * @throws UploadException
 */
function add_image($tmpname, $filename, $tags) {
    assert(file_exists($tmpname));

    $pathinfo = pathinfo($filename);
    if(!array_key_exists('extension', $pathinfo)) {
        throw new UploadException("File has no extension");
    }
    $metadata = array();
    $metadata['filename'] = $pathinfo['basename'];
    $metadata['extension'] = $pathinfo['extension'];
    $metadata['tags'] = $tags;
    $metadata['source'] = null;
    $event = new DataUploadEvent($tmpname, $metadata);
    send_event($event);
    if($event->image_id == -1) {
        throw new UploadException("File type not recognised");
    }
}

/**
 * Given a full size pair of dimensions, return a pair scaled down to fit
 * into the configured thumbnail square, with ratio intact
 *
 * @param int $orig_width
 * @param int $orig_height
 * @return int[]
 */
function get_thumbnail_size(/*int*/ $orig_width, /*int*/ $orig_height) {
	global $config;

	if($orig_width === 0) $orig_width = 192;
	if($orig_height === 0) $orig_height = 192;

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

