<?php

declare(strict_types=1);

namespace Shimmie2;

use GQLA\{Field, Query, Type};

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
final class Image implements \ArrayAccess
{
    public const IMAGE_DIR = "images";
    public const THUMBNAIL_DIR = "thumbs";

    private bool $in_db = false;

    public int $id;
    /** @var 0|positive-int */
    #[Field]
    public int $height = 0;
    /** @var 0|positive-int */
    #[Field]
    public int $width = 0;
    /** @var hash-string */
    #[Field]
    public string $hash;
    /** @var positive-int */
    #[Field]
    public int $filesize;
    #[Field]
    public string $filename;
    #[Field]
    private string $ext;
    private MimeType $mime;

    /** @var list<tag-string>|null */
    public ?array $tag_array;
    public int $owner_id;
    public string $owner_ip;
    #[Field]
    public string $posted;
    /** @var non-empty-string|null */
    #[Field]
    public ?string $source = null;
    #[Field]
    public bool $locked = false;
    public ?bool $lossless = null;
    public ?bool $video = null;
    public ?VideoCodec $video_codec = null;
    public ?bool $image = null;
    public ?bool $audio = null;
    public ?int $length = null;
    public ?Path $tmp_file = null;

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
                } elseif (property_exists($this, $name)) {
                    $t = (new \ReflectionProperty($this, $name))->getType();
                    assert(!is_null($t));
                    if (is_a($t, \ReflectionNamedType::class)) {
                        if (is_null($value)) {
                            $this->$name = null;
                        } else {
                            $this->$name = match($t->getName()) {
                                "int" => int_escape((string)$value),
                                "bool" => bool_escape((string)$value),
                                "string" => (string)$value,
                                "Shimmie2\MimeType" => new MimeType($value),
                                "Shimmie2\VideoCodec" => VideoCodec::from_or_unknown($value),
                                default => $value,
                            };
                        }

                    }
                } elseif (array_key_exists($name, static::$prop_types)) {
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
                    if (defined('UNITTEST')) {
                        throw new \Exception("Unknown column $name in images table");
                    }
                }
            }
            $this->in_db = true;
        }
    }

    public function offsetExists(mixed $offset): bool
    {
        // @phpstan-ignore-next-line
        assert(is_string($offset));
        return array_key_exists($offset, static::$prop_types);
    }
    public function offsetGet(mixed $offset): mixed
    {
        // @phpstan-ignore-next-line
        assert(is_string($offset));
        if (!$this->offsetExists($offset)) {
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
        // @phpstan-ignore-next-line
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
        if ($post_id > 2 ** 32) {
            // for some reason bots query huge numbers and pollute the DB error logs...
            return null;
        }
        $row = Ctx::$database->get_row("SELECT * FROM images WHERE images.id=:id", ["id" => $post_id]);
        return ($row ? new Image($row) : null);
    }

    public static function by_id_ex(int $post_id): Image
    {
        $maybe_post = static::by_id($post_id);
        if (!is_null($maybe_post)) {
            return $maybe_post;
        }
        throw new PostNotFound("Image $post_id not found");
    }

    /**
     * @param hash-string $hash
     */
    public static function by_hash(string $hash): ?Image
    {
        $hash = strtolower($hash);
        $row = Ctx::$database->get_row("SELECT images.* FROM images WHERE hash=:hash", ["hash" => $hash]);
        return ($row ? new Image($row) : null);
    }

    /**
     * @param numeric-string|hash-string $id
     */
    public static function by_id_or_hash(string $id): ?Image
    {
        return (is_numberish($id) && strlen($id) !== 32) ? Image::by_id((int)$id) : Image::by_hash($id);
    }

    /**
     * @param search-term-array $terms
     */
    public static function by_random(array $terms = [], int $limit_range = 0): ?Image
    {
        $max = Search::count_images($terms);
        if ($max < 1) {
            return null;
        }        // From Issue #22 - opened by HungryFeline on May 30, 2011.
        if ($limit_range > 0 && $max > $limit_range) {
            $max = $limit_range;
        }
        $rand = mt_rand(0, $max - 1);
        $set = Search::find_images($rand, 1, $terms);
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
     * @param search-term-array $terms
     */
    public function get_next(array $terms = [], bool $next = true): ?Image
    {
        if ($next) {
            $gtlt = "<";
            $dir = "DESC";
        } else {
            $gtlt = ">";
            $dir = "ASC";
        }

        $terms[] = 'id'. $gtlt . $this->id;
        $terms[] = 'order:id_'. strtolower($dir);
        $images = Search::find_images(0, 1, $terms);
        return (count($images) > 0) ? $images[0] : null;
    }

    /**
     * The reverse of get_next
     *
     * @param search-term-array $terms
     */
    public function get_prev(array $terms = []): ?Image
    {
        return $this->get_next($terms, false);
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
        if ($owner->id !== $this->owner_id) {
            Ctx::$database->execute("
                UPDATE images
                SET owner_id=:owner_id
                WHERE id=:id
            ", ["owner_id" => $owner->id, "id" => $this->id]);
            Log::info("core_image", "Owner for Post #{$this->id} set to {$owner->name}");
        }
    }

    public function save_to_db(): void
    {
        $props_to_save = [
            "filename" => truncate_filename($this->filename, 250),
            "filesize" => $this->filesize,
            "hash" => $this->hash,
            "mime" => (string)$this->mime,
            "ext" => strtolower($this->ext),
            "source" => $this->source,
            "width" => $this->width,
            "height" => $this->height,
            "lossless" => $this->lossless,
            "video" => $this->video,
            "video_codec" => $this->video_codec?->value,
            "image" => $this->image,
            "audio" => $this->audio,
            "length" => $this->length
        ];
        if (!$this->in_db) {
            $props_to_save["owner_id"] = Ctx::$user->id;
            $props_to_save["owner_ip"] = (string)Network::get_real_ip();
            $props_to_save["posted"] = date('Y-m-d H:i:s', time());

            $props_sql = implode(", ", array_keys($props_to_save));
            $vals_sql = implode(", ", array_map(fn ($prop) => ":$prop", array_keys($props_to_save)));

            Ctx::$database->execute(
                "INSERT INTO images($props_sql) VALUES ($vals_sql)",
                $props_to_save,
            );
            $this->id = Ctx::$database->get_last_insert_id('images_id_seq');
            $this->in_db = true;
        } else {
            $props_sql = implode(", ", array_map(fn ($prop) => "$prop = :$prop", array_keys($props_to_save)));
            Ctx::$database->execute(
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
        Ctx::$database->execute($props_sql, array_merge($this->dynamic_props, ["id" => $this->id]));
        */
    }

    /**
     * Get this image's tags as an array.
     *
     * @return list<tag-string>
     */
    #[Field(name: "tags", type: "[string!]!")]
    public function get_tag_array(): array
    {
        if (!isset($this->tag_array)) {
            /** @var list<tag-string> */
            $tarr = Ctx::$database->get_col("
                SELECT tag
                FROM image_tags
                JOIN tags ON image_tags.tag_id = tags.id
                WHERE image_id=:id
                ORDER BY tag
            ", ["id" => $this->id]);
            sort($tarr);
            $this->tag_array = $tarr;
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
    public function get_image_link(): Url
    {
        return $this->get_link(ImageConfig::ILINK, '_images/$hash/$id%20-%20$tags.$ext', 'image/$id/$id%20-%20$tags.$ext');
    }

    #[Field(name: "image_link")]
    public function graphql_image_link(): string
    {
        return (string)$this->get_image_link();
    }

    /**
     * Get the nicely formatted version of the file name
     * in a filesystem-safe manner
     */
    #[Field(name: "nice_name")]
    public function get_nice_image_name(): string
    {
        $text = send_event(new ParseLinkTemplateEvent('$id - $tags.$ext', $this))->text;
        $text = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $text);
        $text = rawurldecode($text);
        $text = truncate_filename($text);
        return $text;
    }

    /**
     * Get the URL for the thumbnail
     */
    public function get_thumb_link(): Url
    {
        $mime = new MimeType(Ctx::$config->get(ThumbnailConfig::MIME));
        $ext = FileExtension::get_for_mime($mime);
        return $this->get_link(ImageConfig::TLINK, '_thumbs/$hash/thumb.'.$ext, 'thumb/$id/thumb.'.$ext);
    }

    #[Field(name: "thumb_link")]
    public function graphql_thumb_link(): string
    {
        return (string)$this->get_thumb_link();
    }

    /**
     * Check configured template for a link, then try nice URL, then plain URL
     */
    private function get_link(string $config_name, string $nice, string $plain): Url
    {
        $image_link = Ctx::$config->get($config_name);

        if (is_string($image_link) && !empty($image_link)) {
            if (!str_contains($image_link, "://") && !str_starts_with($image_link, "/")) {
                $image_link = make_link($image_link);
            }
            $chosen = $image_link;
        } elseif (Url::are_niceurls_enabled()) {
            $chosen = make_link($nice);
        } else {
            $chosen = make_link($plain);
        }
        // HACK
        // $chosen = (string)make_link("foo/$var")
        // results in
        // $chosen = "/index.php?q=foo%2F%24var"
        // so we manually replace the %24 to make substitution work
        $link_string = $this->parse_link_template(str_replace("%24", "$", (string)$chosen));
        return Url::parse($link_string);
    }

    /**
     * Get the tooltip for this image, formatted according to the
     * configured template.
     */
    #[Field(name: "tooltip")]
    public function get_tooltip(): string
    {
        return send_event(new ParseLinkTemplateEvent(Ctx::$config->get(ThumbnailConfig::TIP), $this))->text;
    }

    /**
     * Figure out where the full size image is on disk.
     */
    public function get_image_filename(): Path
    {
        if (!is_null($this->tmp_file)) {
            return $this->tmp_file;
        }
        return Filesystem::warehouse_path(self::IMAGE_DIR, $this->hash);
    }

    /**
     * Figure out where the thumbnail is on disk.
     */
    public function get_thumb_filename(): Path
    {
        return Filesystem::warehouse_path(self::THUMBNAIL_DIR, $this->hash);
    }

    /**
     * @return array{0: positive-int, 1: positive-int}
     */
    public function get_thumb_size(): array
    {
        // TODO: Set up a function for fetching what kind of files are currently thumbnailable
        if (in_array($this->get_mime()->base, [MimeType::MP3])) {
            //Use max thumbnail size if using thumbless filetype
            $config_width = Ctx::$config->get(ThumbnailConfig::WIDTH);
            $config_height = Ctx::$config->get(ThumbnailConfig::HEIGHT);
            assert($config_width >= 0 && $config_height >= 0);
            $tsize = ThumbnailUtil::get_thumbnail_size($config_width, $config_height);
        } else {
            $tsize = ThumbnailUtil::get_thumbnail_size($this->width, $this->height);
        }
        return $tsize;
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
    public function get_mime(): MimeType
    {
        if ($this->mime->base === MimeType::WEBP && $this->lossless) {
            return new MimeType(MimeType::WEBP_LOSSLESS);
        }
        if ($this->mime->base === MimeType::GIF && $this->video) {
            return new MimeType(MimeType::GIF_ANIMATED);
        }
        return $this->mime;
    }

    #[Field(name: "mime")]
    public function graphql_mime(): string
    {
        return (string)$this->mime;
    }

    /**
     * Set the image's mime type.
     */
    public function set_mime(MimeType|string $mime): void
    {
        if (is_string($mime)) {
            $mime = new MimeType($mime);
        }
        $this->mime = $mime;
        $this->ext = FileExtension::get_for_mime($this->get_mime());
    }


    /**
     * Get the image's source URL
     * @return non-empty-string|null
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
        $old_source = $this->source;
        if (empty($new_source)) {
            $new_source = null;
        }
        if ($new_source !== $old_source) {
            Ctx::$database->execute("UPDATE images SET source=:source WHERE id=:id", ["source" => $new_source, "id" => $this->id]);
            Log::info("core_image", "Source for Post #{$this->id} set to: $new_source (was $old_source)");
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
        if ($locked !== $this->locked) {
            Ctx::$database->execute("UPDATE images SET locked=:yn WHERE id=:id", ["yn" => $locked, "id" => $this->id]);
            $s = $locked ? "locked" : "unlocked";
            Log::info("core_image", "Setting Post #{$this->id} to $s");
        }
    }

    /**
     * Delete all tags from this image.
     *
     * Normally in preparation to set them to a new set.
     */
    public function delete_tags_from_image(): void
    {
        Ctx::$database->execute("
            UPDATE tags
            SET count = count - 1
            WHERE id IN (
                SELECT tag_id
                FROM image_tags
                WHERE image_id = :id
            )
        ", ["id" => $this->id]);
        Ctx::$database->execute("
            DELETE
            FROM image_tags
            WHERE image_id=:id
        ", ["id" => $this->id]);
    }

    /**
     * Set the tags for this image.
     *
     * @param tag-string[] $unfiltered_tags
     */
    public function set_tags(array $unfiltered_tags): void
    {
        $tags = array_iunique($unfiltered_tags);

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

        if (strtolower(Tag::implode($tags)) !== strtolower($this->get_tag_list())) {
            // delete old
            $this->delete_tags_from_image();

            // insert each new tags
            $ids = array_map(fn ($tag) => Tag::get_or_create_id($tag), $tags);
            $values = implode(", ", array_map(fn ($id) => "({$this->id}, $id)", $ids));
            // @phpstan-ignore-next-line
            Ctx::$database->execute("INSERT INTO image_tags(image_id, tag_id) VALUES $values");
            Ctx::$database->execute("
                UPDATE tags
                SET count = count + 1
                WHERE id IN (
                    SELECT tag_id
                    FROM image_tags
                    WHERE image_id = :id
                )
            ", ["id" => $this->id]);

            Log::info("core_image", "Tags for Post #{$this->id} set to: ".Tag::implode($tags));
            Ctx::$cache->delete("image-{$this->id}-tags");
        }
    }

    /**
     * Delete this image from the database and disk
     */
    public function delete(): void
    {
        $this->delete_tags_from_image();
        Ctx::$database->execute("DELETE FROM images WHERE id=:id", ["id" => $this->id]);
        Log::info("core_image", 'Deleted Post #'.$this->id.' ('.$this->hash.')');
        $this->remove_image_only(quiet: true);
    }

    /**
     * This function removes an image (and thumbnail) from the DISK ONLY.
     * It DOES NOT remove anything from the database.
     */
    public function remove_image_only(bool $quiet = false): void
    {
        $img = $this->get_image_filename();
        if ($img->exists()) {
            $img->unlink();
        }

        $thumb = $this->get_thumb_filename();
        if ($thumb->exists()) {
            $thumb->unlink();
        }

        Log::info("core_image", "Deleted files for Post #{$this->id} ({$this->hash})");
    }

    public function parse_link_template(string $tmpl, int $n = 0): string
    {
        $plte = send_event(new ParseLinkTemplateEvent($tmpl, $this));
        $tmpl = $plte->link;
        return LoadBalancer::load_balance_url($tmpl, $this->hash, $n);
    }
}
