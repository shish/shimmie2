<?php

declare(strict_types=1);

namespace Shimmie2;

use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Mutation;

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
    public ?string $source = null;
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
        $max = Search::count_images($tags);
        if ($max < 1) {
            return null;
        }		// From Issue #22 - opened by HungryFeline on May 30, 2011.
        if ($limit_range > 0 && $max > $limit_range) {
            $max = $limit_range;
        }
        $rand = mt_rand(0, $max - 1);
        $set = Search::find_images($rand, 1, $tags);
        if (count($set) > 0) {
            return $set[0];
        } else {
            return null;
        }
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

        $tags[] = 'id'. $gtlt . $this->id;
        $tags[] = 'order:id_'. strtolower($dir);
        $images = Search::find_images(0, 1, $tags);
        return (count($images) > 0) ? $images[0] : null;
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
        return strtolower($m);
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
}
