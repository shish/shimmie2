<?php

declare(strict_types=1);

namespace Shimmie2;

use GQLA\Type;
use GQLA\Field;
use GQLA\Query;

enum ImagePropType
{
    case BOOL;
    case INT;
    case STRING;
}

/**
 * Class Image
 *
 * An object representing an entry in the images table.
 *
 * As of 2.2, this no longer necessarily represents an
 * image per se, but could be a video, sound file, or any
 * other supported upload type.
 *
 * @implements \ArrayAccess<string, mixed>
 */
#[Type(name: "Post")]
class Image implements \ArrayAccess
{
    public const IMAGE_DIR = "images";
    public const THUMBNAIL_DIR = "thumbs";

    private bool $in_db = false;

    public int $id;
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
    public string $posted;
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
    public ?string $tmp_file = null;

    /** @var array<string, ImagePropType> */
    public static array $prop_types = [];
    /** @var array<string, mixed> */
    private array $dynamic_props = [];

    /**
     * One will very rarely construct an image directly, more common
     * would be to use Image::by_id, Image::by_hash, etc.
     *
     * @param array<string|int, mixed>|null $row
     */
    public function __construct(?array $row = null)
    {
        if (!is_null($row)) {
            foreach ($row as $name => $value) {
                // some databases return both key=>value and numeric indices,
                // we only want the key=>value ones
                if (is_numeric($name)) {
                    continue;
                } elseif(property_exists($this, $name)) {
                    $t = (new \ReflectionProperty($this, $name))->getType();
                    assert(!is_null($t));
                    if(is_a($t, \ReflectionNamedType::class)) {
                        if(is_null($value)) {
                            $this->$name = null;
                        } else {
                            $this->$name = match($t->getName()) {
                                "int" => int_escape((string)$value),
                                "bool" => bool_escape((string)$value),
                                "string" => (string)$value,
                                default => $value,
                            };
                        }

                    }
                } elseif(array_key_exists($name, static::$prop_types)) {
                    if (is_null($value)) {
                        $value = null;
                    } else {
                        $value = match(static::$prop_types[$name]) {
                            ImagePropType::BOOL => bool_escape((string)$value),
                            ImagePropType::INT => int_escape((string)$value),
                            ImagePropType::STRING => (string)$value,
                        };
                    }
                    $this->dynamic_props[$name] = $value;
                } else {
                    // Database table has a column we don't know about,
                    // it isn't static and it isn't a known prop_type -
                    // maybe from an old extension that has since been
                    // disabled? Just ignore it.
                    if(defined('UNITTEST')) {
                        throw new \Exception("Unknown column $name in images table");
                    }
                }
            }
            $this->in_db = true;
        }
    }

    public function offsetExists(mixed $offset): bool
    {
        assert(is_string($offset));
        return array_key_exists($offset, static::$prop_types);
    }
    public function offsetGet(mixed $offset): mixed
    {
        assert(is_string($offset));
        if(!$this->offsetExists($offset)) {
            $known = implode(", ", array_keys(static::$prop_types));
            throw new \OutOfBoundsException("Undefined dynamic property: $offset (Known: $known)");
        }
        return $this->dynamic_props[$offset] ?? null;
    }
    public function offsetSet(mixed $offset, mixed $value): void
    {
        assert(is_string($offset));
        $this->dynamic_props[$offset] = $value;
    }
    public function offsetUnset(mixed $offset): void
    {
        assert(is_string($offset));
        unset($this->dynamic_props[$offset]);
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

    public static function by_id_ex(int $post_id): Image
    {
        $maybe_post = static::by_id($post_id);
        if(!is_null($maybe_post)) {
            return $maybe_post;
        }
        throw new ImageNotFound("Image $post_id not found");
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
        return (is_numberish($id) && strlen($id) != 32) ? Image::by_id((int)$id) : Image::by_hash($id);
    }

    /**
     * @param string[] $tags
     */
    public static function by_random(array $tags = [], int $limit_range = 0): ?Image
    {
        $max = Search::count_images($tags);
        if ($max < 1) {
            return null;
        }        // From Issue #22 - opened by HungryFeline on May 30, 2011.
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
        $user = User::by_id($this->owner_id);
        assert(!is_null($user));
        return $user;
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

    public function save_to_db(): void
    {
        global $database, $user;

        $props_to_save = [
            "filename" => substr($this->filename, 0, 255),
            "filesize" => $this->filesize,
            "hash" => $this->hash,
            "mime" => strtolower($this->mime),
            "ext" => strtolower($this->ext),
            "source" => $this->source,
            "width" => $this->width,
            "height" => $this->height,
            "lossless" => $this->lossless,
            "video" => $this->video,
            "video_codec" => $this->video_codec,
            "image" => $this->image,
            "audio" => $this->audio,
            "length" => $this->length
        ];
        if (!$this->in_db) {
            $props_to_save["owner_id"] = $user->id;
            $props_to_save["owner_ip"] = get_real_ip();
            $props_to_save["posted"] = date('Y-m-d H:i:s', time());

            $props_sql = implode(", ", array_keys($props_to_save));
            $vals_sql = implode(", ", array_map(fn ($prop) => ":$prop", array_keys($props_to_save)));

            $database->execute(
                "INSERT INTO images($props_sql) VALUES ($vals_sql)",
                $props_to_save,
            );
            $this->id = $database->get_last_insert_id('images_id_seq');
            $this->in_db = true;
        } else {
            $props_sql = implode(", ", array_map(fn ($prop) => "$prop = :$prop", array_keys($props_to_save)));
            $database->execute(
                "UPDATE images SET $props_sql WHERE id = :id",
                array_merge(
                    $props_to_save,
                    ["id" => $this->id]
                )
            );
        }

        // For the future: automatically save dynamic props instead of
        // requiring each extension to do it manually.
        /*
        $props_sql = "UPDATE images SET ";
        $props_sql .= implode(", ", array_map(fn ($prop) => "$prop = :$prop", array_keys($this->dynamic_props)));
        $props_sql .= " WHERE id = :id";
        $database->execute($props_sql, array_merge($this->dynamic_props, ["id" => $this->id]));
        */
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
        return $this->get_link(ImageConfig::ILINK, '_images/$hash/$id%20-%20$tags.$ext', 'image/$id/$id%20-%20$tags.$ext');
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
        return $this->get_link(ImageConfig::TLINK, '_thumbs/$hash/thumb.'.$ext, 'thumb/$id/thumb.'.$ext);
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
        } elseif ($config->get_bool(SetupConfig::NICE_URLS, false)) {
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
        if(!is_null($this->tmp_file)) {
            return $this->tmp_file;
        }
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
    public function get_mime(): string
    {
        if ($this->mime === MimeType::WEBP && $this->lossless) {
            return MimeType::WEBP_LOSSLESS;
        }
        return strtolower($this->mime);
    }

    /**
     * Set the image's mime type.
     */
    public function set_mime(string $mime): void
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
            $s = $locked ? "locked" : "unlocked";
            log_info("core_image", "Setting Post #{$this->id} to $s");
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
     *
     * @param string[] $unfiltered_tags
     */
    public function set_tags(array $unfiltered_tags): void
    {
        global $cache, $database, $page;

        $tags = array_unique($unfiltered_tags);

        foreach ($tags as $tag) {
            if (mb_strlen($tag, 'UTF-8') > 255) {
                throw new TagSetException("Can't set a tag longer than 255 characters");
            }
            if (str_starts_with($tag, "-")) {
                throw new TagSetException("Can't set a tag which starts with a minus");
            }
            if (str_contains($tag, "*")) {
                throw new TagSetException("Can't set a tag which contains a wildcard (*)");
            }
        }
        if (count($tags) <= 0) {
            throw new TagSetException('Tried to set zero tags');
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
        $this->remove_image_only(quiet: true);
    }

    /**
     * This function removes an image (and thumbnail) from the DISK ONLY.
     * It DOES NOT remove anything from the database.
     */
    public function remove_image_only(bool $quiet = false): void
    {
        $img_del = @unlink($this->get_image_filename());
        $thumb_del = @unlink($this->get_thumb_filename());
        if($img_del && $thumb_del) {
            if(!$quiet) {
                log_info("core_image", "Deleted files for Post #{$this->id} ({$this->hash})");
            }
        } else {
            $img = $img_del ? '' : ' image';
            $thumb = $thumb_del ? '' : ' thumbnail';
            log_error('core_image', "Failed to delete files for Post #{$this->id}{$img}{$thumb}");
        }
    }

    public function parse_link_template(string $tmpl, int $n = 0): string
    {
        $plte = send_event(new ParseLinkTemplateEvent($tmpl, $this));
        $tmpl = $plte->link;
        return load_balance_url($tmpl, $this->hash, $n);
    }
}
