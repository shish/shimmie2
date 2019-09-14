<?php
/**
 * Class Image
 *
 * An object representing an entry in the images table.
 *
 * As of 2.2, this no longer necessarily represents an
 * image per se, but could be a video, sound file, or any
 * other supported upload type.
 */
class Image
{
    public const IMAGE_DIR = "images";
    public const THUMBNAIL_DIR = "thumbs";

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

    /** @var int */
    public $owner_id;
    
    /** @var string */
    public $owner_ip;
    
    /** @var string */
    public $posted;
    
    /** @var string */
    public $source;
    
    /** @var boolean */
    public $locked = false;

    /** @var boolean */
    public $lossless = null;

    /** @var boolean */
    public $video = null;

    /** @var boolean */
    public $audio = null;

    /** @var int */
    public $length = null;


    /**
     * One will very rarely construct an image directly, more common
     * would be to use Image::by_id, Image::by_hash, etc.
     */
    public function __construct(?array $row=null)
    {
        if (!is_null($row)) {
            foreach ($row as $name => $value) {
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

    public static function by_id(int $id): ?Image
    {
        global $database;
        $row = $database->get_row("SELECT * FROM images WHERE images.id=:id", ["id"=>$id]);
        return ($row ? new Image($row) : null);
    }

    public static function by_hash(string $hash): ?Image
    {
        global $database;
        $row = $database->get_row("SELECT images.* FROM images WHERE hash=:hash", ["hash"=>$hash]);
        return ($row ? new Image($row) : null);
    }

    public static function by_random(array $tags=[], int $limit_range=0): ?Image
    {
        $max = Image::count_images($tags);
        if ($max < 1) {
            return null;
        }		// From Issue #22 - opened by HungryFeline on May 30, 2011.
        if ($max > $limit_range) {
            $max = $limit_range;
        }
        $rand = mt_rand(0, $max-1);
        $set = Image::find_images($rand, 1, $tags);
        if (count($set) > 0) {
            return $set[0];
        } else {
            return null;
        }
    }


    private static function find_images_internal(int $start = 0, ?int $limit = null, array $tags=[]): iterable
    {
        global $database, $user, $config;

        if ($start < 0) {
            $start = 0;
        }
        if ($limit!=null && $limit < 1) {
            $limit = 1;
        }

        if (SPEED_HAX) {
            if (!$user->can(Permissions::BIG_SEARCH) and count($tags) > 3) {
                throw new SCoreException("Anonymous users may only search for up to 3 tags at a time");
            }
        }

        list($tag_conditions, $img_conditions) = self::terms_to_conditions($tags);

        $result = Image::get_accelerated_result($tag_conditions, $img_conditions, $start, $limit);
        if (!$result) {
            $querylet = Image::build_search_querylet($tag_conditions, $img_conditions);
            $querylet->append(new Querylet(" ORDER BY ".(Image::$order_sql ?: "images.".$config->get_string("index_order"))));
            if($limit!=null) {
                $querylet->append(new Querylet(" LIMIT :limit ", ["limit" => $limit]));
            }
            $querylet->append(new Querylet(" OFFSET :offset ", ["offset"=>$start]));
            #var_dump($querylet->sql); var_dump($querylet->variables);
            $result = $database->get_all_iterable($querylet->sql, $querylet->variables);
        }

        Image::$order_sql = null;

        return $result;
    }

    /**
     * Search for an array of images
     *
     * #param string[] $tags
     * #return Image[]
     */
    public static function find_images(int $start, int $limit, array $tags=[]): array
    {
        $result = self::find_images_internal($start, $limit, $tags);

        $images = [];
        foreach ($result as $row) {
            $images[] = new Image($row);
        }
        return $images;
    }

    /**
     * Search for an array of images, returning a iterable object of Image
     */
    public static function find_images_iterable(int $start = 0, ?int $limit = null, array $tags=[]): Generator
    {
        $result = self::find_images_internal($start, $limit, $tags);
        foreach ($result as $row) {
            yield new Image($row);
        }
    }

    /*
     * Accelerator stuff
     */
    public static function get_acceleratable(array $tag_conditions): ?array
    {
        $ret = [
            "yays" => [],
            "nays" => [],
        ];
        $yays = 0;
        $nays = 0;
        foreach ($tag_conditions as $tq) {
            if (strpos($tq->tag, "*") !== false) {
                // can't deal with wildcards
                return null;
            }
            if ($tq->positive) {
                $yays++;
                $ret["yays"][] = $tq->tag;
            } else {
                $nays++;
                $ret["nays"][] = $tq->tag;
            }
        }
        if ($yays > 1 || $nays > 0) {
            return $ret;
        }
        return null;
    }

    public static function get_accelerated_result(array $tag_conditions, array $img_conditions, int $offset, ?int $limit): ?PDOStatement
    {
        if (!SEARCH_ACCEL || !empty($img_conditions) || isset($_GET['DISABLE_ACCEL'])) {
            return null;
        }

        global $database;

        $req = Image::get_acceleratable($tag_conditions);
        if (!$req) {
            return null;
        }
        $req["offset"] = $offset;
        $req["limit"] = $limit;

        $response = Image::query_accelerator($req);
        if ($response) {
			$list = implode(",", $response);
            $result = $database->execute("SELECT * FROM images WHERE id IN ($list) ORDER BY images.id DESC");
        } else {
            $result = $database->execute("SELECT * FROM images WHERE 1=0 ORDER BY images.id DESC");
        }
        return $result;
    }

    public static function get_accelerated_count(array $tag_conditions, array $img_conditions): ?int
    {
        if (!SEARCH_ACCEL || !empty($img_conditions) || isset($_GET['DISABLE_ACCEL'])) {
            return null;
        }

        $req = Image::get_acceleratable($tag_conditions);
        if (!$req) {
            return null;
        }
        $req["count"] = true;

        return Image::query_accelerator($req);
    }

    public static function query_accelerator($req)
    {
		global $_tracer;
        $fp = @fsockopen("127.0.0.1", 21212);
        if (!$fp) {
            return null;
        }
		$req_str = json_encode($req);
		$_tracer->begin("Accelerator Query", ["req"=>$req_str]);
        fwrite($fp, $req_str);
        $data = "";
        while (($buffer = fgets($fp, 4096)) !== false) {
            $data .= $buffer;
        }
		$_tracer->end();
        if (!feof($fp)) {
            die("Error: unexpected fgets() fail in query_accelerator($req_str)\n");
        }
        fclose($fp);
        return json_decode($data);
    }

    /*
     * Image-related utility functions
     */

    /**
     * Count the number of image results for a given search
     *
     * #param string[] $tags
     */
    public static function count_images(array $tags=[]): int
    {
        global $database;
        $tag_count = count($tags);

        if ($tag_count === 0) {
            $total = $database->cache->get("image-count");
            if (!$total) {
                $total = $database->get_one("SELECT COUNT(*) FROM images");
                $database->cache->set("image-count", $total, 600);
            }
        } elseif ($tag_count === 1 && !preg_match("/[:=><\*\?]/", $tags[0])) {
            $total = $database->get_one(
                $database->scoreql_to_sql("SELECT count FROM tags WHERE SCORE_STRNORM(tag) = SCORE_STRNORM(:tag)"),
                ["tag"=>$tags[0]]
            );
        } else {
            list($tag_conditions, $img_conditions) = self::terms_to_conditions($tags);
            $total = Image::get_accelerated_count($tag_conditions, $img_conditions);
            if (is_null($total)) {
                $querylet = Image::build_search_querylet($tag_conditions, $img_conditions);
                $total = $database->get_one("SELECT COUNT(*) AS cnt FROM ($querylet->sql) AS tbl", $querylet->variables);
            }
        }
        if (is_null($total)) {
            return 0;
        }
        return $total;
    }

    /**
     * Count the number of pages for a given search
     *
     * #param string[] $tags
     */
    public static function count_pages(array $tags=[]): float
    {
        global $config;
        return ceil(Image::count_images($tags) / $config->get_int('index_images'));
    }

    private static function terms_to_conditions(array $terms): array
    {
        $tag_conditions = [];
        $img_conditions = [];

        /*
         * Turn a bunch of strings into a bunch of TagCondition
         * and ImgCondition objects
         */
        $stpe = new SearchTermParseEvent(null, $terms);
        send_event($stpe);
        if ($stpe->is_querylet_set()) {
            foreach ($stpe->get_querylets() as $querylet) {
                $img_conditions[] = new ImgCondition($querylet, true);
            }
        }

        foreach ($terms as $term) {
            $positive = true;
            if (is_string($term) && !empty($term) && ($term[0] == '-')) {
                $positive = false;
                $term = substr($term, 1);
            }
            if (strlen($term) === 0) {
                continue;
            }

            $stpe = new SearchTermParseEvent($term, $terms);
            send_event($stpe);
            if ($stpe->is_querylet_set()) {
                foreach ($stpe->get_querylets() as $querylet) {
                    $img_conditions[] = new ImgCondition($querylet, $positive);
                }
            } else {
                // if the whole match is wild, skip this
                if (str_replace("*", "", $term) != "") {
                    $tag_conditions[] = new TagCondition($term, $positive);
                }
            }
        }
        return [$tag_conditions, $img_conditions];
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
     * #param string[] $tags
     */
    public function get_next(array $tags=[], bool $next=true): ?Image
    {
        global $database;

        if ($next) {
            $gtlt = "<";
            $dir = "DESC";
        } else {
            $gtlt = ">";
            $dir = "ASC";
        }

        if (count($tags) === 0) {
            $row = $database->get_row('
				SELECT images.*
				FROM images
				WHERE images.id '.$gtlt.' '.$this->id.'
				ORDER BY images.id '.$dir.'
				LIMIT 1
			');
        } else {
            $tags[] = 'id'. $gtlt . $this->id;
            list($tag_conditions, $img_conditions) = self::terms_to_conditions($tags);
            $querylet = Image::build_search_querylet($tag_conditions, $img_conditions);
            $querylet->append_sql(' ORDER BY images.id '.$dir.' LIMIT 1');
            $row = $database->get_row($querylet->sql, $querylet->variables);
        }

        return ($row ? new Image($row) : null);
    }

    /**
     * The reverse of get_next
     *
     * #param string[] $tags
     */
    public function get_prev(array $tags=[]): ?Image
    {
        return $this->get_next($tags, false);
    }

    /**
     * Find the User who owns this Image
     */
    public function get_owner(): User
    {
        return User::by_id($this->owner_id);
    }

    /**
     * Set the image's owner.
     */
    public function set_owner(User $owner): void
    {
        global $database;
        if ($owner->id != $this->owner_id) {
            $database->execute("
				UPDATE images
				SET owner_id=:owner_id
				WHERE id=:id
			", ["owner_id"=>$owner->id, "id"=>$this->id]);
            log_info("core_image", "Owner for Image #{$this->id} set to {$owner->name}", null, ["image_id" => $this->id]);
        }
    }

    /**
     * Get this image's tags as an array.
     *
     * #return string[]
     */
    public function get_tag_array(): array
    {
        global $database;
        if (!isset($this->tag_array)) {
            $this->tag_array = $database->get_col("
				SELECT tag
				FROM image_tags
				JOIN tags ON image_tags.tag_id = tags.id
				WHERE image_id=:id
				ORDER BY tag
			", ["id"=>$this->id]);
        }
        return $this->tag_array;
    }

    /**
     * Get this image's tags as a string.
     */
    public function get_tag_list(): string
    {
        return Tag::implode($this->get_tag_array());
    }

    /**
     * Get the URL for the full size image
     */
    public function get_image_link(): string
    {
        return $this->get_link(ImageConfig::ILINK, '_images/$hash/$id%20-%20$tags.$ext', 'image/$id.$ext');
    }

    /**
     * Get the nicely formatted version of the file name
     */
    public function get_nice_image_name(): string
    {
        return $this->parse_link_template('$id - $tags.$ext');
    }

    /**
     * Get the URL for the thumbnail
     */
    public function get_thumb_link(): string
    {
        global $config;
        $ext = $config->get_string(ImageConfig::THUMB_TYPE);
        return $this->get_link(ImageConfig::TLINK, '_thumbs/$hash/thumb.'.$ext, 'thumb/$id.'.$ext);
    }

    /**
     * Check configured template for a link, then try nice URL, then plain URL
     */
    private function get_link(string $template, string $nice, string $plain): string
    {
        global $config;

        $image_link = $config->get_string($template);

        if (!empty($image_link)) {
            if (!(strpos($image_link, "://") > 0) && !startsWith($image_link, "/")) {
                $image_link = make_link($image_link);
            }
            return $this->parse_link_template($image_link);
        } elseif ($config->get_bool('nice_urls', false)) {
            return $this->parse_link_template(make_link($nice));
        } else {
            return $this->parse_link_template(make_link($plain));
        }
    }

    /**
     * Get the tooltip for this image, formatted according to the
     * configured template.
     */
    public function get_tooltip(): string
    {
        global $config;
        $tt = $this->parse_link_template($config->get_string(ImageConfig::TIP), "no_escape");

        // Removes the size tag if the file is an mp3
        if ($this->ext === 'mp3') {
            $iitip = $tt;
            $mp3tip = ["0x0"];
            $h_tip = str_replace($mp3tip, " ", $iitip);

            // Makes it work with a variation of the default tooltips (I.E $tags // $filesize // $size)
            $justincase = ["   //", "//   ", "  //", "//  ", "  "];
            if (strstr($h_tip, "  ")) {
                $h_tip = html_escape(str_replace($justincase, "", $h_tip));
            } else {
                $h_tip = html_escape($h_tip);
            }
            return $h_tip;
        } else {
            return $tt;
        }
    }

    /**
     * Figure out where the full size image is on disk.
     */
    public function get_image_filename(): string
    {
        return warehouse_path(self::IMAGE_DIR, $this->hash);
    }

    /**
     * Figure out where the thumbnail is on disk.
     */
    public function get_thumb_filename(): string
    {
        return warehouse_path(self::THUMBNAIL_DIR, $this->hash);
    }

    /**
     * Get the original filename.
     */
    public function get_filename(): string
    {
        return $this->filename;
    }

    /**
     * Get the image's mime type.
     */
    public function get_mime_type(): string
    {
        return getMimeType($this->get_image_filename(), $this->get_ext());
    }

    /**
     * Get the image's filename extension
     */
    public function get_ext(): string
    {
        return $this->ext;
    }

    /**
     * Get the image's source URL
     */
    public function get_source(): ?string
    {
        return $this->source;
    }

    /**
     * Set the image's source URL
     */
    public function set_source(string $new_source): void
    {
        global $database;
        $old_source = $this->source;
        if (empty($new_source)) {
            $new_source = null;
        }
        if ($new_source != $old_source) {
            $database->execute("UPDATE images SET source=:source WHERE id=:id", ["source"=>$new_source, "id"=>$this->id]);
            log_info("core_image", "Source for Image #{$this->id} set to: $new_source (was $old_source)", null, ["image_id" => $this->id]);
        }
    }

    /**
     * Check if the image is locked.
     */
    public function is_locked(): bool
    {
        return $this->locked;
    }

    public function set_locked(bool $tf): void
    {
        global $database;
        $ln = $tf ? "Y" : "N";
        $sln = $database->scoreql_to_sql('SCORE_BOOL_'.$ln);
        $sln = str_replace("'", "", $sln);
        $sln = str_replace('"', "", $sln);
        if (bool_escape($sln) !== $this->locked) {
            $database->execute("UPDATE images SET locked=:yn WHERE id=:id", ["yn"=>$sln, "id"=>$this->id]);
            log_info("core_image", "Setting Image #{$this->id} lock to: $ln", null, ["image_id" => $this->id]);
        }
    }

    /**
     * Delete all tags from this image.
     *
     * Normally in preparation to set them to a new set.
     */
    public function delete_tags_from_image(): void
    {
        global $database;
        if ($database->get_driver_name() == DatabaseDriver::MYSQL) {
            //mysql < 5.6 has terrible subquery optimization, using EXISTS / JOIN fixes this
            $database->execute(
                "
				UPDATE tags t
				INNER JOIN image_tags it ON t.id = it.tag_id
				SET count = count - 1
				WHERE it.image_id = :id",
                ["id"=>$this->id]
            );
        } else {
            $database->execute("
				UPDATE tags
				SET count = count - 1
				WHERE id IN (
					SELECT tag_id
					FROM image_tags
					WHERE image_id = :id
				)
			", ["id"=>$this->id]);
        }
        $database->execute("
			DELETE
			FROM image_tags
			WHERE image_id=:id
		", ["id"=>$this->id]);
    }

    /**
     * Set the tags for this image.
     */
    public function set_tags(array $unfiltered_tags): void
    {
        global $database;

        $unfiltered_tags = array_unique($unfiltered_tags);

        $tags = [];
        foreach ($unfiltered_tags as $tag) {
            if (mb_strlen($tag, 'UTF-8') > 255) {
                flash_message("Can't set a tag longer than 255 characters");
                continue;
            }
            if (startsWith($tag, "-")) {
                flash_message("Can't set a tag which starts with a minus");
                continue;
            }

            $tags[] = $tag;
        }

        if (count($tags) <= 0) {
            throw new SCoreException('Tried to set zero tags');
        }

        if (Tag::implode($tags) != $this->get_tag_list()) {
            // delete old
            $this->delete_tags_from_image();

            $written_tags = [];

            // insert each new tags
            foreach ($tags as $tag) {
                $id = $database->get_one(
                    $database->scoreql_to_sql("
						SELECT id
						FROM tags
						WHERE SCORE_STRNORM(tag) = SCORE_STRNORM(:tag)
					"),
                    ["tag"=>$tag]
                );
                if (empty($id)) {
                    // a new tag
                    $database->execute(
                        "INSERT INTO tags(tag) VALUES (:tag)",
                        ["tag"=>$tag]
                    );
                    $database->execute(
                        "INSERT INTO image_tags(image_id, tag_id)
							VALUES(:id, (SELECT id FROM tags WHERE tag = :tag))",
                        ["id"=>$this->id, "tag"=>$tag]
                    );
                } else {
                    // check if tag has already been written
                    if (in_array($id, $written_tags)) {
                        continue;
                    }

                    $database->execute("
                        INSERT INTO image_tags(image_id, tag_id)
                        VALUES(:iid, :tid)
                    ", ["iid"=>$this->id, "tid"=>$id]);

                    array_push($written_tags, $id);
                }
                $database->execute(
                    $database->scoreql_to_sql("
						UPDATE tags
						SET count = count + 1
						WHERE SCORE_STRNORM(tag) = SCORE_STRNORM(:tag)
					"),
                    ["tag"=>$tag]
                );
            }

            log_info("core_image", "Tags for Image #{$this->id} set to: ".Tag::implode($tags), null, ["image_id" => $this->id]);
            $database->cache->delete("image-{$this->id}-tags");
        }
    }

    /**
     * Send list of metatags to be parsed.
     *
     * #param string[] $metatags
     */
    public function parse_metatags(array $metatags, int $image_id): void
    {
        foreach ($metatags as $tag) {
            $ttpe = new TagTermParseEvent($tag, $image_id, true);
            send_event($ttpe);
        }
    }

    /**
     * Delete this image from the database and disk
     */
    public function delete(): void
    {
        global $database;
        $this->delete_tags_from_image();
        $database->execute("DELETE FROM images WHERE id=:id", ["id"=>$this->id]);
        log_info("core_image", 'Deleted Image #'.$this->id.' ('.$this->hash.')', null, ["image_id" => $this->id]);

        unlink($this->get_image_filename());
        unlink($this->get_thumb_filename());
    }

    /**
     * This function removes an image (and thumbnail) from the DISK ONLY.
     * It DOES NOT remove anything from the database.
     */
    public function remove_image_only(): void
    {
        log_info("core_image", 'Removed Image File ('.$this->hash.')', null, ["image_id" => $this->id]);
        @unlink($this->get_image_filename());
        @unlink($this->get_thumb_filename());
    }

    public function parse_link_template(string $tmpl, string $_escape="url_escape", int $n=0): string
    {
        global $config;

        // don't bother hitting the database if it won't be used...
        $tags = "";
        if (strpos($tmpl, '$tags') !== false) { // * stabs dynamically typed languages with a rusty spoon *
            $tags = $this->get_tag_list();
            $tags = str_replace("/", "", $tags);
            $tags = preg_replace("/^\.+/", "", $tags);
        }

        $base_href = $config->get_string('base_href');
        $fname = $this->get_filename();
        $base_fname = strpos($fname, '.') ? substr($fname, 0, strrpos($fname, '.')) : $fname;

        $tmpl = str_replace('$id', $this->id, $tmpl);
        $tmpl = str_replace('$hash_ab', substr($this->hash, 0, 2), $tmpl);
        $tmpl = str_replace('$hash_cd', substr($this->hash, 2, 2), $tmpl);
        $tmpl = str_replace('$hash', $this->hash, $tmpl);
        $tmpl = str_replace('$tags', $_escape($tags), $tmpl);
        $tmpl = str_replace('$base', $base_href, $tmpl);
        $tmpl = str_replace('$ext', $this->ext, $tmpl);
        $tmpl = str_replace('$size', "{$this->width}x{$this->height}", $tmpl);
        $tmpl = str_replace('$filesize', to_shorthand_int($this->filesize), $tmpl);
        $tmpl = str_replace('$filename', $_escape($base_fname), $tmpl);
        $tmpl = str_replace('$title', $_escape($config->get_string(SetupConfig::TITLE)), $tmpl);
        $tmpl = str_replace('$date', $_escape(autodate($this->posted, false)), $tmpl);

        // nothing seems to use this, sending the event out to 50 exts is a lot of overhead
        if (!SPEED_HAX) {
            $plte = new ParseLinkTemplateEvent($tmpl, $this);
            send_event($plte);
            $tmpl = $plte->link;
        }

        static $flexihashes = [];
        $matches = [];
        if (preg_match("/(.*){(.*)}(.*)/", $tmpl, $matches)) {
            $pre = $matches[1];
            $opts = $matches[2];
            $post = $matches[3];

            if(isset($flexihashes[$opts])) {
                $flexihash = $flexihashes[$opts];
            }
            else {
                $flexihash = new Flexihash\Flexihash();
                foreach (explode(",", $opts) as $opt) {
                    $parts = explode("=", $opt);
                    $parts_count = count($parts);
                    $opt_val = "";
                    $opt_weight = 0;
                    if ($parts_count === 2) {
                        $opt_val = $parts[0];
                        $opt_weight = $parts[1];
                    } elseif ($parts_count === 1) {
                        $opt_val = $parts[0];
                        $opt_weight = 1;
                    }
                    $flexihash->addTarget($opt_val, $opt_weight);
                }
                $flexihashes[$opts] = $flexihash;
            }

            // $choice = $flexihash->lookup($pre.$post);
            $choices = $flexihash->lookupList($this->hash, $n+1);  // hash doesn't change
            $choice = $choices[$n];
            $tmpl = $pre.$choice.$post;
        }

        return $tmpl;
    }

    /**
     * #param string[] $terms
     */
    private static function build_search_querylet(array $tag_conditions, array $img_conditions): Querylet
    {
        global $database;

        $positive_tag_count = 0;
        $negative_tag_count = 0;
        foreach ($tag_conditions as $tq) {
            if ($tq->positive) {
                $positive_tag_count++;
            } else {
                $negative_tag_count++;
            }
        }

        /*
         * Turn a bunch of Querylet objects into a base query
         *
         * Must follow the format
         *
         *   SELECT images.*
         *   FROM (...) AS images
         *   WHERE (...)
         *
         * ie, return a set of images.* columns, and end with a WHERE
         */

        // no tags, do a simple search
        if ($positive_tag_count === 0 && $negative_tag_count === 0) {
            $query = new Querylet("
				SELECT images.*
				FROM images
				WHERE 1=1
			");
        }

        // more than one positive tag, or more than zero negative tags
        else {
            $query = Image::build_accurate_search_querylet($tag_conditions);
        }

        /*
         * Merge all the image metadata searches into one generic querylet
         * and append to the base querylet with "AND blah"
         */
        if (!empty($img_conditions)) {
            $n = 0;
            $img_sql = "";
            $img_vars = [];
            foreach ($img_conditions as $iq) {
                if ($n++ > 0) {
                    $img_sql .= " AND";
                }
                if (!$iq->positive) {
                    $img_sql .= " NOT";
                }
                $img_sql .= " (" . $iq->qlet->sql . ")";
                $img_vars = array_merge($img_vars, $iq->qlet->variables);
            }
            $query->append_sql(" AND ");
            $query->append(new Querylet($img_sql, $img_vars));
        }

        return $query;
    }

    /**
     * #param TagQuerylet[] $tag_conditions
     */
    private static function build_accurate_search_querylet(array $tag_conditions): Querylet
    {
        global $database;

        $positive_tag_id_array = [];
        $positive_wildcard_id_array = [];
        $negative_tag_id_array = [];

        foreach ($tag_conditions as $tq) {
            $sq = "
                SELECT id
                FROM tags
                WHERE SCORE_STRNORM(tag) LIKE SCORE_STRNORM(:tag)
            ";
            if ($database->get_driver_name() === DatabaseDriver::SQLITE) {
                $sq .= "ESCAPE '\\'";
            }
            $tag_ids = $database->get_col(
                $database->scoreql_to_sql($sq),
                ["tag" => Tag::sqlify($tq->tag)]
            );

            $tag_count = count($tag_ids);

            if ($tq->positive) {
                if ($tag_count== 0) {
                    # one of the positive tags had zero results, therefor there
                    # can be no results; "where 1=0" should shortcut things
                    return new Querylet("
						SELECT images.*
						FROM images
						WHERE 1=0
					");
                } elseif($tag_count==1) {
                    // All wildcard terms that qualify for a single tag can be treated the same as non-wildcards
                    $positive_tag_id_array[] = $tag_ids[0];
                } else {
                    // Terms that resolve to multiple tags act as an OR within themselves
                    // and as an AND in relation to all other terms,
                    $positive_wildcard_id_array[] = $tag_ids;
                }
            } else {
                // Unlike positive criteria, negative criteria are all handled in an OR fashion,
                // so we can just compile them all into a single sub-query.
                $negative_tag_id_array = array_merge($negative_tag_id_array, $tag_ids);
            }
        }

        $sql = "";
        assert($positive_tag_id_array || $positive_wildcard_id_array || $negative_tag_id_array, @$_GET['q']);
        if(!empty($positive_tag_id_array) || !empty($positive_wildcard_id_array)) {
            $inner_joins = [];
            if (!empty($positive_tag_id_array)) {
                foreach($positive_tag_id_array as $tag) {
                    $inner_joins[] = "= $tag";
                }
            }
            if(!empty($positive_wildcard_id_array)) {
                foreach ($positive_wildcard_id_array as $tags) {
                    $positive_tag_id_list = join(', ', $tags);
                    $inner_joins[] = "IN ($positive_tag_id_list)";
                }
            }

            $first = array_shift($inner_joins);
            $sub_query = "SELECT it.image_id FROM  image_tags it ";
            $i = 0;
            foreach ($inner_joins as $inner_join) {
                $i++;
                $sub_query .= " INNER JOIN image_tags it$i ON it$i.image_id = it.image_id AND it$i.tag_id $inner_join ";
            }
            if(!empty($negative_tag_id_array)) {
                $negative_tag_id_list = join(', ', $negative_tag_id_array);
                $sub_query .= " LEFT JOIN image_tags negative ON negative.image_id = it.image_id AND negative.tag_id IN ($negative_tag_id_list) ";
            }
            $sub_query .= "WHERE it.tag_id $first ";
            if(!empty($negative_tag_id_array)) {
                $sub_query .= " AND negative.image_id IS NULL";
            }
            $sub_query .= " GROUP BY it.image_id ";

            $sql = "
                SELECT images.*
                FROM images INNER JOIN (
                $sub_query
                ) a on a.image_id = images.id 
            ";
        } elseif(!empty($negative_tag_id_array)) {
            $negative_tag_id_list = join(', ', $negative_tag_id_array);
            $sql = "
                SELECT images.*
                FROM images LEFT JOIN image_tags negative ON negative.image_id = images.id AND negative.tag_id in ($negative_tag_id_list)  
                WHERE negative.image_id IS NULL
            ";
        } else {
            throw new SCoreException("No criteria specified");
        }

        return new Querylet($sql);
    }
}
