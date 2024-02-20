<?php

declare(strict_types=1);

namespace Shimmie2;

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
    protected Themelet $theme;
    public ExtensionInfo $info;

    /** @var string[] */
    private static array $enabled_extensions = [];

    public function __construct(?string $class = null)
    {
        $class = $class ?? get_called_class();
        $this->theme = $this->get_theme_object($class);
        $this->info = ExtensionInfo::get_for_extension_class($class);
        $this->key = $this->info->key;
    }

    /**
     * Find the theme object for a given extension.
     */
    private function get_theme_object(string $base): Themelet
    {
        $base = str_replace("Shimmie2\\", "", $base);
        $custom = "Shimmie2\Custom{$base}Theme";
        $normal = "Shimmie2\\{$base}Theme";

        if (class_exists($custom)) {
            $c = new $custom();
            assert(is_a($c, Themelet::class));
            return $c;
        } elseif (class_exists($normal)) {
            $n = new $normal();
            assert(is_a($n, Themelet::class));
            return $n;
        } else {
            return new Themelet();
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
        $extras = defined("EXTRA_EXTS") ? explode(",", EXTRA_EXTS) : [];

        foreach (array_merge(
            ExtensionInfo::get_core_extensions(),
            $extras
        ) as $key) {
            $ext = ExtensionInfo::get_by_key($key);
            if ($ext === null || !$ext->is_supported()) {
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

    public static function is_enabled(string $key): bool
    {
        return in_array($key, self::$enabled_extensions);
    }

    /**
     * @return string[]
     */
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

    protected function set_version(string $name, int $ver): void
    {
        global $config;
        $config->set_int($name, $ver);
        log_info("upgrade", "Set version for $name to $ver");
    }
}

class ExtensionNotFound extends SCoreException
{
}

enum ExtensionVisibility
{
    case DEFAULT;
    case ADMIN;
    case HIDDEN;
}

enum ExtensionCategory: string
{
    case GENERAL = "General";
    case ADMIN = "Admin";
    case MODERATION = "Moderation";
    case FILE_HANDLING = "File Handling";
    case OBSERVABILITY = "Observability";
    case INTEGRATION = "Integration";
    case FEATURE = "Feature";
    case METADATA = "Metadata";
}

abstract class ExtensionInfo
{
    // Every credit you get costs us RAM. It stops now.
    public const SHISH_NAME = "Shish";
    public const SHISH_EMAIL = "webmaster@shishnet.org";
    public const SHIMMIE_URL = "https://code.shishnet.org/shimmie2/";
    public const SHISH_AUTHOR = [self::SHISH_NAME => self::SHISH_EMAIL];

    public const LICENSE_GPLV2 = "GPLv2";
    public const LICENSE_MIT = "MIT";
    public const LICENSE_WTFPL = "WTFPL";

    public string $key;

    public bool $core = false;
    public bool $beta = false;

    public string $name;
    public string $license;
    public string $description;
    /** @var array<string, string|null> */
    public array $authors = [];
    /** @var string[] */
    public array $dependencies = [];
    /** @var string[] */
    public array $conflicts = [];
    public ExtensionVisibility $visibility = ExtensionVisibility::DEFAULT;
    public ExtensionCategory $category = ExtensionCategory::GENERAL;
    public ?string $link = null;
    public ?string $documentation = null;

    /** @var DatabaseDriverID[] which DBs this ext supports (blank for 'all') */
    public array $db_support = [];
    private ?bool $supported = null;
    private ?string $support_info = null;

    public function is_supported(): bool
    {
        if ($this->supported === null) {
            $this->check_support();
        }
        return $this->supported;
    }

    public function get_support_info(): string
    {
        if ($this->supported === null) {
            $this->check_support();
        }
        return $this->support_info;
    }

    /** @var array<string, ExtensionInfo> */
    private static array $all_info_by_key = [];
    /** @var array<string, ExtensionInfo> */
    private static array $all_info_by_class = [];
    /** @var string[] */
    private static array $core_extensions = [];

    protected function __construct()
    {
        assert(!empty($this->key), "key field is required");
        assert(!empty($this->name), "name field is required for extension $this->key");
        assert(is_array($this->db_support), "db_support has to be an array for extension $this->key");
        assert(is_array($this->authors), "authors has to be an array for extension $this->key");
        assert(is_array($this->dependencies), "dependencies has to be an array for extension $this->key");
    }

    public function is_enabled(): bool
    {
        return Extension::is_enabled($this->key);
    }

    private function check_support(): void
    {
        global $database;
        $this->support_info  = "";
        if (!empty($this->db_support) && !in_array($database->get_driver_id(), $this->db_support)) {
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

    /**
     * @return ExtensionInfo[]
     */
    public static function get_all(): array
    {
        return array_values(self::$all_info_by_key);
    }

    /**
     * @return string[]
     */
    public static function get_all_keys(): array
    {
        return array_keys(self::$all_info_by_key);
    }

    /**
     * @return string[]
     */
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

    public static function get_for_extension_class(string $base): ExtensionInfo
    {
        $normal = "{$base}Info";

        if (array_key_exists($normal, self::$all_info_by_class)) {
            return self::$all_info_by_class[$normal];
        } else {
            $infos = print_r(array_keys(self::$all_info_by_class), true);
            throw new ExtensionNotFound("$normal not found in {$infos}");
        }
    }

    public static function load_all_extension_info(): void
    {
        foreach (get_subclasses_of(ExtensionInfo::class) as $class) {
            $extension_info = new $class();
            assert(is_a($extension_info, ExtensionInfo::class));
            if (array_key_exists($extension_info->key, self::$all_info_by_key)) {
                throw new ServerError("Extension Info $class with key $extension_info->key has already been loaded");
            }

            self::$all_info_by_key[$extension_info->key] = $extension_info;
            self::$all_info_by_class[$class] = $extension_info;
            if ($extension_info->core === true) {
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
    public function onTextFormatting(TextFormattingEvent $event): void
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
    /** @var string[] */
    protected array $SUPPORTED_MIME = [];

    public function onDataUpload(DataUploadEvent $event): void
    {
        global $config;

        if ($this->supported_mime($event->mime)) {
            if (!$this->check_contents($event->tmpname)) {
                // We DO support this extension - but the file looks corrupt
                throw new UploadException("Invalid or corrupted file");
            }

            $existing = Image::by_hash(\Safe\md5_file($event->tmpname));
            if (!is_null($existing)) {
                if ($config->get_string(ImageConfig::UPLOAD_COLLISION_HANDLER) == ImageConfig::COLLISION_MERGE) {
                    // Right now tags are the only thing that get merged, so
                    // we can just send a TagSetEvent - in the future we might
                    // want a dedicated MergeEvent?
                    if(!empty($event->metadata['tags'])) {
                        $tags = Tag::explode($existing->get_tag_list() . " " . $event->metadata['tags']);
                        send_event(new TagSetEvent($existing, $tags));
                    }
                    $event->images[] = $existing;
                    return;
                } else {
                    throw new UploadException(">>{$existing->id} already has hash {$existing->hash}");
                }
            }

            // Create a new Image object
            $filename = $event->tmpname;
            assert(is_readable($filename));
            $image = new Image();
            $image->tmp_file = $filename;
            $image->filesize = \Safe\filesize($filename);
            $image->hash = \Safe\md5_file($filename);
            // DB limits to 255 char filenames
            $image->filename = substr($event->filename, -250);
            $image->set_mime($event->mime);
            try {
                send_event(new MediaCheckPropertiesEvent($image));
            } catch (MediaException $e) {
                throw new UploadException("Unable to scan media properties $filename / $image->filename / $image->hash: ".$e->getMessage());
            }
            $image->save_to_db(); // Ensure the image has a DB-assigned ID

            $iae = send_event(new ImageAdditionEvent($image));
            send_event(new ImageInfoSetEvent($image, $event->slot, $event->metadata));

            // If everything is OK, then move the file to the archive
            $filename = warehouse_path(Image::IMAGE_DIR, $event->hash);
            if (!@copy($event->tmpname, $filename)) {
                $errors = error_get_last();
                throw new UploadException(
                    "Failed to copy file from uploads ({$event->tmpname}) to archive ($filename): ".
                    "{$errors['type']} / {$errors['message']}"
                );
            }

            $event->images[] = $iae->image;
        }
    }

    public function onThumbnailGeneration(ThumbnailGenerationEvent $event): void
    {
        $result = false;
        if ($this->supported_mime($event->image->get_mime())) {
            if ($event->force) {
                $result = $this->create_thumb($event->image);
            } else {
                $outname = $event->image->get_thumb_filename();
                if (file_exists($outname)) {
                    return;
                }
                $result = $this->create_thumb($event->image);
            }
        }
        if ($result) {
            $event->generated = true;
        }
    }

    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        global $config, $page;
        if ($this->supported_mime($event->image->get_mime())) {
            // @phpstan-ignore-next-line
            $this->theme->display_image($event->image);
            if ($config->get_bool(ImageConfig::SHOW_META) && method_exists($this->theme, "display_metadata")) {
                $this->theme->display_metadata($event->image);
            }
        }
    }

    public function onMediaCheckProperties(MediaCheckPropertiesEvent $event): void
    {
        if ($this->supported_mime($event->image->get_mime())) {
            $this->media_check_properties($event);
        }
    }

    abstract protected function media_check_properties(MediaCheckPropertiesEvent $event): void;
    abstract protected function check_contents(string $tmpname): bool;
    abstract protected function create_thumb(Image $image): bool;

    protected function supported_mime(string $mime): bool
    {
        return MimeType::matches_array($mime, $this->SUPPORTED_MIME);
    }

    /**
     * @return string[]
     */
    public static function get_all_supported_mimes(): array
    {
        $arr = [];
        foreach (get_subclasses_of(DataHandlerExtension::class) as $handler) {
            $handler = (new $handler());
            assert(is_a($handler, DataHandlerExtension::class));
            $arr = array_merge($arr, $handler->SUPPORTED_MIME);
        }

        // Not sure how to handle this otherwise, don't want to set up a whole other event for this one class
        if (Extension::is_enabled(TranscodeImageInfo::KEY)) {
            $arr = array_merge($arr, TranscodeImage::get_enabled_mimes());
        }

        $arr = array_unique($arr);
        return $arr;
    }

    /**
     * @return string[]
     */
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
