<?php

declare(strict_types=1);

namespace Shimmie2;

use GQLA\Type;
use GQLA\Field;
use GQLA\Query;

/**
 * Class Image
 *
 * An object representing an entry in the images table.
 *
 * As of 2.2, this no longer necessarily represents an
 * image per se, but could be a video, sound file, or any
 * other supported upload type.
 */
#[\AllowDynamicProperties]
#[Type(name: "Post")]
class Image
{
    public const IMAGE_DIR = "images";
    public const THUMBNAIL_DIR = "thumbs";

    public ?int $id = null;
    #[Field]
    public int $height = 0;
    #[Field]
    public int $width = 0;
    #[Field]
    public string $hash;
    #[Field]
    public int $filesize;
    #[Field]
    public string $filename;
    #[Field]
    private string $ext;
    private string $mime;

    /** @var ?string[] */
    public ?array $tag_array;
    public int $owner_id;
    public string $owner_ip;
    #[Field]
    public ?string $posted = null;
    #[Field]
    public ?string $source;
    #[Field]
    public bool $locked = false;
    public ?bool $lossless = null;
    public ?bool $video = null;
    public ?string $video_codec = null;
    public ?bool $image = null;
    public ?bool $audio = null;
    public ?int $length = null;

    public static array $bool_props = ["locked", "lossless", "video", "audio", "image"];
    public static array $int_props = ["id", "owner_id", "height", "width", "filesize", "length"];

    /**
     * One will very rarely construct an image directly, more common
     * would be to use Image::by_id, Image::by_hash, etc.
     */
    public function __construct(?array $row = null)
    {
        if (!is_null($row)) {
            foreach ($row as $name => $value) {
                if (is_numeric($name)) {
                    continue;
                }

                // some databases use table.name rather than name
                $name = str_replace("images.", "", $name);

                // hax, this is likely the cause of much scrutinizer-ci complaints.
                if (is_null($value)) {
                    $this->$name = null;
                } elseif (in_array($name, self::$bool_props)) {
                    $this->$name = bool_escape((string)$value);
                } elseif (in_array($name, self::$int_props)) {
                    $this->$name = int_escape((string)$value);
                } else {
                    $this->$name = $value;
                }
            }
        }
    }

    #[Field(name: "post_id")]
    public function graphql_oid(): int
    {
        return $this->id;
    }
    #[Field(name: "id")]
    public function graphql_guid(): string
    {
        return "post:{$this->id}";
    }

    #[Query(name: "post")]
    public static function by_id(int $post_id): ?Image
    {
        global $database;
        if ($post_id > 2 ** 32) {
            // for some reason bots query huge numbers and pollute the DB error logs...
            return null;
        }
        $row = $database->get_row("SELECT * FROM images WHERE images.id=:id", ["id" => $post_id]);
        return ($row ? new Image($row) : null);
    }

    public static function by_hash(string $hash): ?Image
    {
        global $database;
        $hash = strtolower($hash);
        $row = $database->get_row("SELECT images.* FROM images WHERE hash=:hash", ["hash" => $hash]);
        return ($row ? new Image($row) : null);
    }

    public static function by_id_or_hash(string $id): ?Image
    {
        return (is_numeric($id) && strlen($id) != 32) ? Image::by_id((int)$id) : Image::by_hash($id);
    }

    public static function by_random(array $tags = [], int $limit_range = 0): ?Image
    {
        $max = Image::count_images($tags);
        if ($max < 1) {
            return null;
        }		// From Issue #22 - opened by HungryFeline on May 30, 2011.
        if ($limit_range > 0 && $max > $limit_range) {
            $max = $limit_range;
        }
        $rand = mt_rand(0, $max - 1);
        $set = Image::find_images($rand, 1, $tags);
        if (count($set) > 0) {
            return $set[0];
        } else {
            return null;
        }
    }

    private static function find_images_internal(int $start = 0, ?int $limit = null, array $tags = []): iterable
    {
        global $database, $user;

        if ($start < 0) {
            $start = 0;
        }
        if ($limit !== null && $limit < 1) {
            $limit = 1;
        }

        if (SPEED_HAX) {
            if (!$user->can(Permissions::BIG_SEARCH) and count($tags) > 3) {
                throw new PermissionDeniedException("Anonymous users may only search for up to 3 tags at a time");
            }
        }

        $querylet = Image::build_search_querylet($tags, $limit, $start);
        return $database->get_all_iterable($querylet->sql, $querylet->variables);
    }

    /**
     * Search for an array of images
     *
     * @param string[] $tags
     * @return Image[]
     */
    #[Query(name: "posts", type: "[Post!]!", args: ["tags" => "[string!]"])]
    public static function find_images(int $offset = 0, ?int $limit = null, array $tags = []): array
    {
        $result = self::find_images_internal($offset, $limit, $tags);

        $images = [];
        foreach ($result as $row) {
            $images[] = new Image($row);
        }
        return $images;
    }

    /**
     * Search for an array of images, returning a iterable object of Image
     */
    public static function find_images_iterable(int $start = 0, ?int $limit = null, array $tags = []): \Generator
    {
        $result = self::find_images_internal($start, $limit, $tags);
        foreach ($result as $row) {
            yield new Image($row);
        }
    }

    /*
     * Image-related utility functions
     */

    public static function count_total_images(): int
    {
        global $cache, $database;
        $total = $cache->get("image-count");
        if (is_null($total)) {
            $total = (int)$database->get_one("SELECT COUNT(*) FROM images");
            $cache->set("image-count", $total, 600);
        }
        return $total;
    }

    public static function count_tag(string $tag): int
    {
        global $database;
        return (int)$database->get_one(
            "SELECT count FROM tags WHERE LOWER(tag) = LOWER(:tag)",
            ["tag" => $tag]
        );
    }

    /**
     * Count the number of image results for a given search
     *
     * @param string[] $tags
     */
    public static function count_images(array $tags = []): int
    {
        global $cache, $database;
        $tag_count = count($tags);

        if (SPEED_HAX && $tag_count === 0) {
            // total number of images in the DB
            $total = self::count_total_images();
        } elseif (SPEED_HAX && $tag_count === 1 && !preg_match("/[:=><\*\?]/", $tags[0])) {
            if (!str_starts_with($tags[0], "-")) {
                // one tag - we can look that up directly
                $total = self::count_tag($tags[0]);
            } else {
                // one negative tag - subtract from the total
                $total = self::count_total_images() - self::count_tag(substr($tags[0], 1));
            }
        } else {
            // complex query
            // implode(tags) can be too long for memcache...
            $cache_key = "image-count:" . md5(Tag::implode($tags));
            $total = $cache->get($cache_key);
            if (is_null($total)) {
                if (Extension::is_enabled(RatingsInfo::KEY)) {
                    $tags[] = "rating:*";
                }
                $querylet = Image::build_search_querylet($tags);
                $total = (int)$database->get_one("SELECT COUNT(*) AS cnt FROM ($querylet->sql) AS tbl", $querylet->variables);
                if (SPEED_HAX && $total > 5000) {
                    // when we have a ton of images, the count
                    // won't change dramatically very often
                    $cache->set($cache_key, $total, 3600);
                }
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
     * @param string[] $tags
     */
    public static function count_pages(array $tags = []): int
    {
        global $config;
        return (int)ceil(Image::count_images($tags) / $config->get_int(IndexConfig::IMAGES));
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
     */
    public function get_next(array $tags = [], bool $next = true): ?Image
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
            $tags[] = 'order:id_'. strtolower($dir);
            $querylet = Image::build_search_querylet($tags);
            $querylet->append_sql(' LIMIT 1');
            $row = $database->get_row($querylet->sql, $querylet->variables);
        }

        return ($row ? new Image($row) : null);
    }

    /**
     * The reverse of get_next
     *
     * @param string[] $tags
     */
    public function get_prev(array $tags = []): ?Image
    {
        return $this->get_next($tags, false);
    }

    /**
     * Find the User who owns this Image
     */
    #[Field(name: "owner")]
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
			", ["owner_id" => $owner->id, "id" => $this->id]);
            log_info("core_image", "Owner for Post #{$this->id} set to {$owner->name}");
        }
    }

    public function save_to_db()
    {
        global $database, $user;
        $cut_name = substr($this->filename, 0, 255);

        if (is_null($this->posted) || $this->posted == "") {
            $this->posted = date('Y-m-d H:i:s', time());
        }

        if (is_null($this->id)) {
            $database->execute(
                "INSERT INTO images(
					owner_id, owner_ip,
                    filename, filesize,
				    hash, mime, ext,
                    width, height,
                    posted, source
				)
				VALUES (
					:owner_id, :owner_ip,
				    :filename, :filesize,
					:hash, :mime, :ext,
				    0, 0,
				    :posted, :source
				)",
                [
                    "owner_id" => $user->id, "owner_ip" => get_real_ip(),
                    "filename" => $cut_name, "filesize" => $this->filesize,
                    "hash" => $this->hash, "mime" => strtolower($this->mime),
                    "ext" => strtolower($this->ext),
                    "posted" => $this->posted, "source" => $this->source
                ]
            );
            $this->id = $database->get_last_insert_id('images_id_seq');
        } else {
            $database->execute(
                "UPDATE images SET ".
                "filename = :filename, filesize = :filesize, hash = :hash, ".
                "mime = :mime, ext = :ext, width = 0, height = 0, ".
                "posted = :posted, source = :source ".
                "WHERE id = :id",
                [
                    "filename" => $cut_name,
                    "filesize" => $this->filesize,
                    "hash" => $this->hash,
                    "mime" => strtolower($this->mime),
                    "ext" => strtolower($this->ext),
                    "posted" => $this->posted,
                    "source" => $this->source,
                    "id" => $this->id,
                ]
            );
        }

        $database->execute(
            "UPDATE images SET ".
            "lossless = :lossless, ".
            "video = :video, video_codec = :video_codec, audio = :audio,image = :image, ".
            "height = :height, width = :width, ".
            "length = :length WHERE id = :id",
            [
                "id" => $this->id,
                "width" => $this->width ?? 0,
                "height" => $this->height ?? 0,
                "lossless" => $this->lossless,
                "video" => $this->video,
                "video_codec" => $this->video_codec,
                "image" => $this->image,
                "audio" => $this->audio,
                "length" => $this->length
            ]
        );
    }

    /**
     * Get this image's tags as an array.
     *
     * @return string[]
     */
    #[Field(name: "tags", type: "[string!]!")]
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
			", ["id" => $this->id]);
            sort($this->tag_array);
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
    #[Field(name: "image_link")]
    public function get_image_link(): string
    {
        return $this->get_link(ImageConfig::ILINK, '_images/$hash/$id%20-%20$tags.$ext', 'image/$id.$ext');
    }

    /**
     * Get the nicely formatted version of the file name
     */
    #[Field(name: "nice_name")]
    public function get_nice_image_name(): string
    {
        return send_event(new ParseLinkTemplateEvent('$id - $tags.$ext', $this))->text;
    }

    /**
     * Get the URL for the thumbnail
     */
    #[Field(name: "thumb_link")]
    public function get_thumb_link(): string
    {
        global $config;
        $mime = $config->get_string(ImageConfig::THUMB_MIME);
        $ext = FileExtension::get_for_mime($mime);
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
            if (!str_contains($image_link, "://") && !str_starts_with($image_link, "/")) {
                $image_link = make_link($image_link);
            }
            $chosen = $image_link;
        } elseif ($config->get_bool('nice_urls', false)) {
            $chosen = make_link($nice);
        } else {
            $chosen = make_link($plain);
        }
        return $this->parse_link_template($chosen);
    }

    /**
     * Get the tooltip for this image, formatted according to the
     * configured template.
     */
    #[Field(name: "tooltip")]
    public function get_tooltip(): string
    {
        global $config;
        return send_event(new ParseLinkTemplateEvent($config->get_string(ImageConfig::TIP), $this))->text;
    }

    /**
     * Get the info for this image, formatted according to the
     * configured template.
     */
    #[Field(name: "info")]
    public function get_info(): string
    {
        global $config;
        return send_event(new ParseLinkTemplateEvent($config->get_string(ImageConfig::INFO), $this))->text;
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
    #[Field(name: "filename")]
    public function get_filename(): string
    {
        return $this->filename;
    }

    /**
     * Get the image's extension.
     */
    #[Field(name: "ext")]
    public function get_ext(): string
    {
        return $this->ext;
    }

    /**
     * Get the image's mime type.
     */
    #[Field(name: "mime")]
    public function get_mime(): ?string
    {
        if ($this->mime === MimeType::WEBP && $this->lossless) {
            return MimeType::WEBP_LOSSLESS;
        }
        $m = $this->mime;
        if (is_null($m)) {
            $m = MimeMap::get_for_extension($this->ext)[0];
        }
        return $m;
    }

    /**
     * Set the image's mime type.
     */
    public function set_mime($mime): void
    {
        $this->mime = $mime;
        $ext = FileExtension::get_for_mime($this->get_mime());
        assert($ext !== null);
        $this->ext = $ext;
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
            $database->execute("UPDATE images SET source=:source WHERE id=:id", ["source" => $new_source, "id" => $this->id]);
            log_info("core_image", "Source for Post #{$this->id} set to: $new_source (was $old_source)");
        }
    }

    /**
     * Check if the image is locked.
     */
    public function is_locked(): bool
    {
        return $this->locked;
    }

    public function set_locked(bool $locked): void
    {
        global $database;
        if ($locked !== $this->locked) {
            $database->execute("UPDATE images SET locked=:yn WHERE id=:id", ["yn" => $locked, "id" => $this->id]);
            log_info("core_image", "Setting Post #{$this->id} lock to: $locked");
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
        $database->execute("
            UPDATE tags
            SET count = count - 1
            WHERE id IN (
                SELECT tag_id
                FROM image_tags
                WHERE image_id = :id
            )
        ", ["id" => $this->id]);
        $database->execute("
			DELETE
			FROM image_tags
			WHERE image_id=:id
		", ["id" => $this->id]);
    }

    /**
     * Set the tags for this image.
     */
    public function set_tags(array $unfiltered_tags): void
    {
        global $cache, $database, $page;

        $unfiltered_tags = array_unique($unfiltered_tags);

        $tags = [];
        foreach ($unfiltered_tags as $tag) {
            if (mb_strlen($tag, 'UTF-8') > 255) {
                $page->flash("Can't set a tag longer than 255 characters");
                continue;
            }
            if (str_starts_with($tag, "-")) {
                $page->flash("Can't set a tag which starts with a minus");
                continue;
            }

            $tags[] = $tag;
        }

        if (count($tags) <= 0) {
            throw new SCoreException('Tried to set zero tags');
        }

        if (strtolower(Tag::implode($tags)) != strtolower($this->get_tag_list())) {
            // delete old
            $this->delete_tags_from_image();

            // insert each new tags
            $ids = array_map(fn ($tag) => Tag::get_or_create_id($tag), $tags);
            $values = implode(", ", array_map(fn ($id) => "({$this->id}, $id)", $ids));
            $database->execute("INSERT INTO image_tags(image_id, tag_id) VALUES $values");
            $database->execute("
                UPDATE tags
                SET count = count + 1
                WHERE id IN (
                    SELECT tag_id
                    FROM image_tags
                    WHERE image_id = :id
                )
            ", ["id" => $this->id]);

            log_info("core_image", "Tags for Post #{$this->id} set to: ".Tag::implode($tags));
            $cache->delete("image-{$this->id}-tags");
        }
    }

    /**
     * Delete this image from the database and disk
     */
    public function delete(): void
    {
        global $database;
        $this->delete_tags_from_image();
        $database->execute("DELETE FROM images WHERE id=:id", ["id" => $this->id]);
        log_info("core_image", 'Deleted Post #'.$this->id.' ('.$this->hash.')');

        unlink($this->get_image_filename());
        unlink($this->get_thumb_filename());
    }

    /**
     * This function removes an image (and thumbnail) from the DISK ONLY.
     * It DOES NOT remove anything from the database.
     */
    public function remove_image_only(): void
    {
        log_info("core_image", 'Removed Post File ('.$this->hash.')');
        @unlink($this->get_image_filename());
        @unlink($this->get_thumb_filename());
    }

    public function parse_link_template(string $tmpl, int $n = 0): string
    {
        $plte = send_event(new ParseLinkTemplateEvent($tmpl, $this));
        $tmpl = $plte->link;
        return load_balance_url($tmpl, $this->hash, $n);
    }

    private static function tag_or_wildcard_to_ids(string $tag): array
    {
        global $database;
        $sq = "SELECT id FROM tags WHERE LOWER(tag) LIKE LOWER(:tag)";
        if ($database->get_driver_id() === DatabaseDriverID::SQLITE) {
            $sq .= "ESCAPE '\\'";
        }
        return $database->get_col($sq, ["tag" => Tag::sqlify($tag)]);
    }

    /**
     * @param string[] $terms
     */
    private static function build_search_querylet(
        array $terms,
        ?int $limit = null,
        ?int $offset = null
    ): Querylet {
        global $config;

        $tag_conditions = [];
        $img_conditions = [];
        $order = null;

        /*
         * Turn a bunch of strings into a bunch of TagCondition
         * and ImgCondition objects
         */
        $stpen = 0;  // search term parse event number
        foreach (array_merge([null], $terms) as $term) {
            $stpe = send_event(new SearchTermParseEvent($stpen++, $term, $terms));
            $order ??= $stpe->order;
            $img_conditions = array_merge($img_conditions, $stpe->img_conditions);
            $tag_conditions = array_merge($tag_conditions, $stpe->tag_conditions);
        }

        $order = ($order ?: "images.".$config->get_string(IndexConfig::ORDER));

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
        if (count($tag_conditions) === 0) {
            $query = new Querylet("SELECT images.* FROM images WHERE 1=1");
        }

        // one tag sorted by ID - we can fetch this from the image_tags table,
        // and do the offset / limit there, which is 10x faster than fetching
        // all the image_tags and doing the offset / limit on the result.
        elseif (
            count($tag_conditions) === 1
            && empty($img_conditions)
            && ($order == "id DESC" || $order == "images.id DESC")
            && !is_null($offset)
            && !is_null($limit)
        ) {
            $tc = $tag_conditions[0];
            $in = $tc->positive ? "IN" : "NOT IN";
            // IN (SELECT id FROM tags) is 100x slower than doing a separate
            // query and then a second query for IN(first_query_results)??
            $tag_array = self::tag_or_wildcard_to_ids($tc->tag);
            if (count($tag_array) == 0) {
                // if wildcard expanded to nothing, take a shortcut
                if ($tc->positive) {
                    $query = new Querylet("SELECT images.* FROM images WHERE 1=0");
                } else {
                    $query = new Querylet("SELECT images.* FROM images WHERE 1=1");
                }
            } else {
                $set = implode(', ', $tag_array);
                $query = new Querylet("
                    SELECT images.*
                    FROM images INNER JOIN (
                        SELECT it.image_id
                        FROM image_tags it
                        WHERE it.tag_id $in ($set)
                        ORDER BY it.image_id DESC
                        LIMIT :limit OFFSET :offset
                    ) a on a.image_id = images.id
                    ORDER BY images.id DESC
                ", ["limit" => $limit, "offset" => $offset]);
                // don't offset at the image level because
                // we already offset at the image_tags level
                $order = null;
                $limit = null;
                $offset = null;
            }
        }

        // more than one tag, or more than zero other conditions, or a non-default sort order
        else {
            $positive_tag_id_array = [];
            $positive_wildcard_id_array = [];
            $negative_tag_id_array = [];
            $all_nonexistent_negatives = true;

            foreach ($tag_conditions as $tq) {
                $tag_ids = self::tag_or_wildcard_to_ids($tq->tag);
                $tag_count = count($tag_ids);

                if ($tq->positive) {
                    $all_nonexistent_negatives = false;
                    if ($tag_count == 0) {
                        # one of the positive tags had zero results, therefor there
                        # can be no results; "where 1=0" should shortcut things
                        return new Querylet("SELECT images.* FROM images WHERE 1=0");
                    } elseif ($tag_count == 1) {
                        // All wildcard terms that qualify for a single tag can be treated the same as non-wildcards
                        $positive_tag_id_array[] = $tag_ids[0];
                    } else {
                        // Terms that resolve to multiple tags act as an OR within themselves
                        // and as an AND in relation to all other terms,
                        $positive_wildcard_id_array[] = $tag_ids;
                    }
                } else {
                    if ($tag_count > 0) {
                        $all_nonexistent_negatives = false;
                        // Unlike positive criteria, negative criteria are all handled in an OR fashion,
                        // so we can just compile them all into a single sub-query.
                        $negative_tag_id_array = array_merge($negative_tag_id_array, $tag_ids);
                    }
                }
            }

            assert($positive_tag_id_array || $positive_wildcard_id_array || $negative_tag_id_array || $all_nonexistent_negatives, @$_GET['q']);

            if ($all_nonexistent_negatives) {
                $query = new Querylet("SELECT images.* FROM images WHERE 1=1");
            } elseif (!empty($positive_tag_id_array) || !empty($positive_wildcard_id_array)) {
                $inner_joins = [];
                if (!empty($positive_tag_id_array)) {
                    foreach ($positive_tag_id_array as $tag) {
                        $inner_joins[] = "= $tag";
                    }
                }
                if (!empty($positive_wildcard_id_array)) {
                    foreach ($positive_wildcard_id_array as $tags) {
                        $positive_tag_id_list = join(', ', $tags);
                        $inner_joins[] = "IN ($positive_tag_id_list)";
                    }
                }

                $first = array_shift($inner_joins);
                $sub_query = "SELECT it.image_id FROM image_tags it ";
                $i = 0;
                foreach ($inner_joins as $inner_join) {
                    $i++;
                    $sub_query .= " INNER JOIN image_tags it$i ON it$i.image_id = it.image_id AND it$i.tag_id $inner_join ";
                }
                if (!empty($negative_tag_id_array)) {
                    $negative_tag_id_list = join(', ', $negative_tag_id_array);
                    $sub_query .= " LEFT JOIN image_tags negative ON negative.image_id = it.image_id AND negative.tag_id IN ($negative_tag_id_list) ";
                }
                $sub_query .= "WHERE it.tag_id $first ";
                if (!empty($negative_tag_id_array)) {
                    $sub_query .= " AND negative.image_id IS NULL";
                }
                $sub_query .= " GROUP BY it.image_id ";

                $query = new Querylet("
                    SELECT images.*
                    FROM images
                    INNER JOIN ($sub_query) a on a.image_id = images.id
                ");
            } elseif (!empty($negative_tag_id_array)) {
                $negative_tag_id_list = join(', ', $negative_tag_id_array);
                $query = new Querylet("
                    SELECT images.*
                    FROM images
                    LEFT JOIN image_tags negative ON negative.image_id = images.id AND negative.tag_id in ($negative_tag_id_list)
                    WHERE negative.image_id IS NULL
                ");
            } else {
                throw new SCoreException("No criteria specified");
            }
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

        if (!is_null($order)) {
            $query->append(new Querylet(" ORDER BY ".$order));
        }
        if (!is_null($limit)) {
            $query->append(new Querylet(" LIMIT :limit ", ["limit" => $limit]));
            $query->append(new Querylet(" OFFSET :offset ", ["offset" => $offset]));
        }

        return $query;
    }
}
