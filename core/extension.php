<?php declare(strict_types=1);
/**
 * Class Extension
 *
 * send_event(BlahEvent()) -> onBlah($event)
 *
 * Also loads the theme object into $this->theme if available
 *
 * The original concept came from Artanis's Extension extension
 * --> https://github.com/Artanis/simple-extension/tree/master
 * Then re-implemented by Shish after he broke the forum and couldn't
 * find the thread where the original was posted >_<
 */
abstract class Extension
{
    public string $key;
    protected ?Themelet $theme;
    public ?ExtensionInfo $info;

    private static array $enabled_extensions = [];

    public function __construct($class = null)
    {
        $class = $class ?? get_called_class();
        $this->theme = $this->get_theme_object($class);
        $this->info = ExtensionInfo::get_for_extension_class($class);
        if ($this->info===null) {
            throw new ScoreException("Info class not found for extension $class");
        }
        $this->key = $this->info->key;
    }

    /**
     * Find the theme object for a given extension.
     */
    private function get_theme_object(string $base): ?Themelet
    {
        $custom = 'Custom'.$base.'Theme';
        $normal = $base.'Theme';

        if (class_exists($custom)) {
            return new $custom();
        } elseif (class_exists($normal)) {
            return new $normal();
        } else {
            return null;
        }
    }

    /**
     * Override this to change the priority of the extension,
     * lower numbered ones will receive events first.
     */
    public function get_priority(): int
    {
        return 50;
    }

    public static function determine_enabled_extensions(): void
    {
        self::$enabled_extensions = [];
        foreach (array_merge(
            ExtensionInfo::get_core_extensions(),
            explode(",", EXTRA_EXTS)
        ) as $key) {
            $ext = ExtensionInfo::get_by_key($key);
            if ($ext===null || !$ext->is_supported()) {
                continue;
            }
            // FIXME: error if one of our dependencies isn't supported
            self::$enabled_extensions[] = $ext->key;
            if (!empty($ext->dependencies)) {
                foreach ($ext->dependencies as $dep) {
                    self::$enabled_extensions[] = $dep;
                }
            }
        }
    }

    public static function is_enabled(string $key): ?bool
    {
        return in_array($key, self::$enabled_extensions);
    }

    public static function get_enabled_extensions(): array
    {
        return self::$enabled_extensions;
    }
    public static function get_enabled_extensions_as_string(): string
    {
        return implode(",", self::$enabled_extensions);
    }

    protected function get_version(string $name): int
    {
        global $config;
        return $config->get_int($name, 0);
    }

    protected function set_version(string $name, int $ver)
    {
        global $config;
        $config->set_int($name, $ver);
        log_info("upgrade", "Set version for $name to $ver");
    }
}

abstract class ExtensionInfo
{
    // Every credit you get costs us RAM. It stops now.
    public const SHISH_NAME = "Shish";
    public const SHISH_EMAIL = "webmaster@shishnet.org";
    public const SHIMMIE_URL = "https://code.shishnet.org/shimmie2/";
    public const SHISH_AUTHOR = [self::SHISH_NAME=>self::SHISH_EMAIL];

    public const LICENSE_GPLV2 = "GPLv2";
    public const LICENSE_MIT = "MIT";
    public const LICENSE_WTFPL = "WTFPL";

    public const VISIBLE_DEFAULT = "default";
    public const VISIBLE_ADMIN = "admin";
    public const VISIBLE_HIDDEN = "hidden";
    private const VALID_VISIBILITY = [self::VISIBLE_DEFAULT, self::VISIBLE_ADMIN, self::VISIBLE_HIDDEN];

    public string $key;

    public bool $core = false;
    public bool $beta = false;

    public string $name;
    public string $license;
    public string $description;
    public array $authors = [];
    public array $dependencies = [];
    public array $conflicts = [];
    public string $visibility = self::VISIBLE_DEFAULT;
    public ?string $link = null;
    public ?string $version = null;
    public ?string $documentation = null;

    /** @var string[] which DBs this ext supports (blank for 'all') */
    public array $db_support = [];
    private ?bool $supported = null;
    private ?string $support_info = null;

    public function is_supported(): bool
    {
        if ($this->supported===null) {
            $this->check_support();
        }
        return $this->supported;
    }

    public function get_support_info(): string
    {
        if ($this->supported===null) {
            $this->check_support();
        }
        return $this->support_info;
    }

    private static array $all_info_by_key = [];
    private static array $all_info_by_class = [];
    private static array $core_extensions = [];

    protected function __construct()
    {
        assert(!empty($this->key), "key field is required");
        assert(!empty($this->name), "name field is required for extension $this->key");
        assert(empty($this->visibility) || in_array($this->visibility, self::VALID_VISIBILITY), "Invalid visibility for extension $this->key");
        assert(is_array($this->db_support), "db_support has to be an array for extension $this->key");
        assert(is_array($this->authors), "authors has to be an array for extension $this->key");
        assert(is_array($this->dependencies), "dependencies has to be an array for extension $this->key");
    }

    public function is_enabled(): bool
    {
        return Extension::is_enabled($this->key);
    }

    private function check_support()
    {
        global $database;
        $this->support_info  = "";
        if (!empty($this->db_support) && !in_array($database->get_driver_name(), $this->db_support)) {
            $this->support_info .= "Database not supported. ";
        }
        if (!empty($this->conflicts)) {
            $intersects = array_intersect($this->conflicts, Extension::get_enabled_extensions());
            if (!empty($intersects)) {
                $this->support_info .= "Conflicts with other extension(s): " . join(", ", $intersects);
            }
        }

        // Additional checks here as needed

        $this->supported = empty($this->support_info);
    }

    public static function get_all(): array
    {
        return array_values(self::$all_info_by_key);
    }

    public static function get_all_keys(): array
    {
        return array_keys(self::$all_info_by_key);
    }

    public static function get_core_extensions(): array
    {
        return self::$core_extensions;
    }

    public static function get_by_key(string $key): ?ExtensionInfo
    {
        if (array_key_exists($key, self::$all_info_by_key)) {
            return self::$all_info_by_key[$key];
        } else {
            return null;
        }
    }

    public static function get_for_extension_class(string $base): ?ExtensionInfo
    {
        $normal = $base.'Info';

        if (array_key_exists($normal, self::$all_info_by_class)) {
            return self::$all_info_by_class[$normal];
        } else {
            return null;
        }
    }

    public static function load_all_extension_info()
    {
        foreach (get_subclasses_of("ExtensionInfo") as $class) {
            $extension_info = new $class();
            if (array_key_exists($extension_info->key, self::$all_info_by_key)) {
                throw new ScoreException("Extension Info $class with key $extension_info->key has already been loaded");
            }

            self::$all_info_by_key[$extension_info->key] = $extension_info;
            self::$all_info_by_class[$class] = $extension_info;
            if ($extension_info->core===true) {
                self::$core_extensions[] = $extension_info->key;
            }
        }
    }
}

/**
 * Class FormatterExtension
 *
 * Several extensions have this in common, make a common API.
 */
abstract class FormatterExtension extends Extension
{
    public function onTextFormatting(TextFormattingEvent $event)
    {
        $event->formatted = $this->format($event->formatted);
        $event->stripped  = $this->strip($event->stripped);
    }

    abstract public function format(string $text): string;
    abstract public function strip(string $text): string;
}

/**
 * Class DataHandlerExtension
 *
 * This too is a common class of extension with many methods in common,
 * so we have a base class to extend from.
 */
abstract class DataHandlerExtension extends Extension
{
    protected array $SUPPORTED_MIME = [];

    protected function move_upload_to_archive(DataUploadEvent $event)
    {
        $target = warehouse_path(Image::IMAGE_DIR, $event->hash);
        if (!@copy($event->tmpname, $target)) {
            $errors = error_get_last();
            throw new UploadException(
                "Failed to copy file from uploads ({$event->tmpname}) to archive ($target): ".
                "{$errors['type']} / {$errors['message']}"
            );
        }
    }

    public function onDataUpload(DataUploadEvent $event)
    {
        $supported_mime = $this->supported_mime($event->mime);
        $check_contents = $this->check_contents($event->tmpname);
        if ($supported_mime && $check_contents) {
            $this->move_upload_to_archive($event);
            send_event(new ThumbnailGenerationEvent($event->hash, $event->mime));

            /* Check if we are replacing an image */
            if (!is_null($event->replace_id)) {
                /* hax: This seems like such a dirty way to do this.. */

                /* Check to make sure the image exists. */
                $existing = Image::by_id($event->replace_id);

                if (is_null($existing)) {
                    throw new UploadException("Post to replace does not exist!");
                }
                if ($existing->hash === $event->metadata['hash']) {
                    throw new UploadException("The uploaded post is the same as the one to replace.");
                }

                // even more hax..
                $event->metadata['tags'] = $existing->get_tag_list();
                $image = $this->create_image_from_data(warehouse_path(Image::IMAGE_DIR, $event->metadata['hash']), $event->metadata);
                if (is_null($image)) {
                    throw new UploadException("Data handler failed to create post object from data");
                }
                if (empty($image->get_mime())) {
                    throw new UploadException("Unable to determine MIME for ". $event->tmpname);
                }
                try {
                    send_event(new MediaCheckPropertiesEvent($image));
                } catch (MediaException $e) {
                    throw new UploadException("Unable to scan media properties: ".$e->getMessage());
                }

                send_event(new ImageReplaceEvent($event->replace_id, $image));
                $_id = $event->replace_id;
                assert(!is_null($_id));
                $event->image_id = $_id;
            } else {
                $image = $this->create_image_from_data(warehouse_path(Image::IMAGE_DIR, $event->hash), $event->metadata);
                if (is_null($image)) {
                    throw new UploadException("Data handler failed to create post object from data");
                }
                if (empty($image->get_mime())) {
                    throw new UploadException("Unable to determine MIME for ". $event->tmpname);
                }
                try {
                    send_event(new MediaCheckPropertiesEvent($image));
                } catch (MediaException $e) {
                    throw new UploadException("Unable to scan media properties: ".$e->getMessage());
                }

                $iae = send_event(new ImageAdditionEvent($image));
                $event->image_id = $iae->image->id;
                $event->merged = $iae->merged;

                // Rating Stuff.
                if (!empty($event->metadata['rating'])) {
                    $rating = $event->metadata['rating'];
                    send_event(new RatingSetEvent($image, $rating));
                }

                // Locked Stuff.
                if (!empty($event->metadata['locked'])) {
                    $locked = $event->metadata['locked'];
                    send_event(new LockSetEvent($image, !empty($locked)));
                }
            }
        } elseif ($supported_mime && !$check_contents) {
            // We DO support this extension - but the file looks corrupt
            throw new UploadException("Invalid or corrupted file");
        }
    }

    public function onThumbnailGeneration(ThumbnailGenerationEvent $event)
    {
        $result = false;
        if ($this->supported_mime($event->mime)) {
            if ($event->force) {
                $result = $this->create_thumb($event->hash, $event->mime);
            } else {
                $outname = warehouse_path(Image::THUMBNAIL_DIR, $event->hash);
                if (file_exists($outname)) {
                    return;
                }
                $result = $this->create_thumb($event->hash, $event->mime);
            }
        }
        if ($result) {
            $event->generated = true;
        }
    }

    public function onDisplayingImage(DisplayingImageEvent $event)
    {
        global $page;
        if ($this->supported_mime($event->image->get_mime())) {
            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            $this->theme->display_image($page, $event->image);
        }
    }

    public function onMediaCheckProperties(MediaCheckPropertiesEvent $event)
    {
        if ($this->supported_mime($event->mime)) {
            $this->media_check_properties($event);
        }
    }

    protected function create_image_from_data(string $filename, array $metadata): Image
    {
        $image = new Image();

        $image->filesize = $metadata['size'];
        $image->hash = $metadata['hash'];
        $image->filename = (($pos = strpos($metadata['filename'], '?')) !== false) ? substr($metadata['filename'], 0, $pos) : $metadata['filename'];

        if (array_key_exists("extension", $metadata)) {
            $image->set_mime(MimeType::get_for_file($filename, $metadata["extension"]));
        } else {
            $image->set_mime(MimeType::get_for_file($filename));
        }

        $image->tag_array = is_array($metadata['tags']) ? $metadata['tags'] : Tag::explode($metadata['tags']);
        $image->source = $metadata['source'];

        return $image;
    }

    abstract protected function media_check_properties(MediaCheckPropertiesEvent $event): void;
    abstract protected function check_contents(string $tmpname): bool;
    abstract protected function create_thumb(string $hash, string $mime): bool;

    protected function supported_mime(string $mime): bool
    {
        return MimeType::matches_array($mime, $this->SUPPORTED_MIME);
    }

    public static function get_all_supported_mimes(): array
    {
        $arr = [];
        foreach (get_subclasses_of("DataHandlerExtension") as $handler) {
            $handler = (new $handler());
            $arr = array_merge($arr, $handler->SUPPORTED_MIME);
        }

        // Not sure how to handle this otherwise, don't want to set up a whole other event for this one class
        if (class_exists("TranscodeImage")) {
            $arr = array_merge($arr, TranscodeImage::get_enabled_mimes());
        }

        $arr = array_unique($arr);
        return $arr;
    }

    public static function get_all_supported_exts(): array
    {
        $arr = [];
        foreach (self::get_all_supported_mimes() as $mime) {
            $arr = array_merge($arr, FileExtension::get_all_for_mime($mime));
        }
        $arr = array_unique($arr);
        return $arr;
    }
}
