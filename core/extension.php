<?php
/**
 * \page eande Events and Extensions
 *
 * An event is a little blob of data saying "something happened", possibly
 * "something happened, here's the specific data". Events are sent with the
 * send_event() function. Since events can store data, they can be used to
 * return data to the extension which sent them, for example:
 *
 * \code
 * $tfe = new TextFormattingEvent($original_text);
 * send_event($tfe);
 * $formatted_text = $tfe->formatted;
 * \endcode
 *
 * An extension is something which is capable of reacting to events.
 *
 *
 * \page hello The Hello World Extension
 *
 * \code
 * // ext/hello/main.php
 * public class HelloEvent extends Event {
 *     public function __construct($username) {
 *         $this->username = $username;
 *     }
 * }
 *
 * public class Hello extends Extension {
 *     public function onPageRequest(PageRequestEvent $event) {   // Every time a page request is sent
 *         global $user;                                          // Look at the global "currently logged in user" object
 *         send_event(new HelloEvent($user->name));               // Broadcast a signal saying hello to that user
 *     }
 *     public function onHello(HelloEvent $event) {               // When the "Hello" signal is recieved
 *         $this->theme->display_hello($event->username);         // Display a message on the web page
 *     }
 * }
 *
 * // ext/hello/theme.php
 * public class HelloTheme extends Themelet {
 *     public function display_hello($username) {
 *         global $page;
 *         $h_user = html_escape($username);                     // Escape the data before adding it to the page
 *         $block = new Block("Hello!", "Hello there $h_user");  // HTML-safe variables start with "h_"
 *         $page->add_block($block);                             // Add the block to the page
 *     }
 * }
 *
 * // ext/hello/test.php
 * public class HelloTest extends SCorePHPUnitTestCase {
 *     public function testHello() {
 *         $this->get_page("post/list");                   // View a page, any page
 *         $this->assert_text("Hello there");              // Check that the specified text is in that page
 *     }
 * }
 *
 * // themes/mytheme/hello.theme.php
 * public class CustomHelloTheme extends HelloTheme {     // CustomHelloTheme overrides HelloTheme
 *     public function display_hello($username) {         // the display_hello() function is customised
 *         global $page;
 *         $h_user = html_escape($username);
 *         $page->add_block(new Block(
 *             "Hello!",
 *             "Hello there $h_user, look at my snazzy custom theme!"
 *         );
 *     }
 * }
 * \endcode
 *
 */

/**
 * Class Extension
 *
 * send_event(BlahEvent()) -> onBlah($event)
 *
 * Also loads the theme object into $this->theme if available
 *
 * The original concept came from Artanis's Extension extension
 * --> http://github.com/Artanis/simple-extension/tree/master
 * Then re-implemented by Shish after he broke the forum and couldn't
 * find the thread where the original was posted >_<
 */
abstract class Extension
{
    public $key;

    /** @var Themelet this theme's Themelet object */
    public $theme;

    public $info;

    private static $enabled_extensions = [];

    public function __construct($class = null)
    {
        $class = $class ?? get_called_class();
        $this->theme = $this->get_theme_object($class);
        $this->info = ExtensionInfo::get_for_extension_class($class);
        if($this->info===null) {
            throw new Exception("Info class not found for extension $class");
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

    public static function determine_enabled_extensions()
    {
        self::$enabled_extensions = [];
        foreach(array_merge(ExtensionInfo::get_core_extensions(),
                explode(",", EXTRA_EXTS)) as $key) {
            $ext = ExtensionInfo::get_by_key($key);
            if($ext===null) {
                continue;
            }
            self::$enabled_extensions[] = $ext->key;
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
        return implode(",",self::$enabled_extensions);
    }
}

abstract class ExtensionInfo
{
    // Every credit you get costs us RAM. It stops now.
    public const SHISH_NAME = "Shish";
    public const SHISH_EMAIL = "webmaster@shishnet.org";
    public const SHIMMIE_URL = "http://code.shishnet.org/shimmie2/";
    public const SHISH_AUTHOR = [self::SHISH_NAME=>self::SHISH_EMAIL];

    public const LICENSE_GPLV2 = "GPLv2";
    public const LICENSE_MIT = "MIT";
    public const LICENSE_WTFPL = "WTFPL";

    public const VISIBLE_ADMIN = "admin";
    private const VALID_VISIBILITY = [self::VISIBLE_ADMIN];

    public $key;

    public $core = false;

    public $beta = false;

    public $name;
    public $authors = [];
    public $link;
    public $license;
    public $version;
    public $visibility;
    public $description;
    public $documentation;

    /** @var array which DBs this ext supports (blank for 'all') */
    public $db_support = [];

    private $supported = null;
    private $support_info = null;

    public function is_supported(): bool
    {
        if($this->supported===null) {
            $this->check_support();
        }
        return $this->supported;
    }

    public function get_support_info(): string
    {
        if($this->supported===null) {
            $this->check_support();
        }
        return $this->support_info;
    }

    private static $all_info_by_key = [];
    private static $all_info_by_class = [];
    private static $core_extensions = [];

    protected function __construct()
    {
        if(empty($this->key)) {
            throw new Exception("key field is required");
        }
        if(empty($this->name)) {
            throw new Exception("name field is required for extension $this->key");
        }
        if(!empty($this->visibility)&&!in_array($this->visibility, self::VALID_VISIBILITY)) {
            throw new Exception("Invalid visibility for extension $this->key");
        }
        if(!is_array($this->db_support)) {
            throw new Exception("db_support has to be an array for extension $this->key");
        }
        if(!is_array($this->authors)) {
            throw new Exception("authors has to be an array for extension $this->key");
        }
    }

    public function is_enabled(): bool
    {
        return Extension::is_enabled($this->key);
    }

    private function check_support()
    {
        global $database;
        $this->support_info  = "";
        if(!empty($this->db_support)&&!in_array($database->get_driver_name(), $this->db_support)) {
            $this->support_info .= "Database not supported. ";
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
        if(array_key_exists($key, self::$all_info_by_key)) {
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

        foreach (get_declared_classes() as $class) {
            $rclass = new ReflectionClass($class);
            if ($rclass->isAbstract()) {
                // don't do anything
            } elseif (is_subclass_of($class, "ExtensionInfo")) {
                $extension_info = new $class();
                if(array_key_exists($extension_info->key, self::$all_info_by_key)) {
                    throw new Exception("Extension Info $class with key $extension_info->key has already been loaded");
                }

                self::$all_info_by_key[$extension_info->key] = $extension_info;
                self::$all_info_by_class[$class] = $extension_info;
                if($extension_info->core===true) {
                    self::$core_extensions[] = $extension_info->key;
                }
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
    public function onDataUpload(DataUploadEvent $event)
    {
        $supported_ext = $this->supported_ext($event->type);
        $check_contents = $this->check_contents($event->tmpname);
        if ($supported_ext && $check_contents) {
            move_upload_to_archive($event);
            send_event(new ThumbnailGenerationEvent($event->hash, $event->type));

            /* Check if we are replacing an image */
            if (array_key_exists('replace', $event->metadata) && isset($event->metadata['replace'])) {
                /* hax: This seems like such a dirty way to do this.. */

                /* Validate things */
                $image_id = int_escape($event->metadata['replace']);

                /* Check to make sure the image exists. */
                $existing = Image::by_id($image_id);

                if (is_null($existing)) {
                    throw new UploadException("Image to replace does not exist!");
                }
                if ($existing->hash === $event->metadata['hash']) {
                    throw new UploadException("The uploaded image is the same as the one to replace.");
                }

                // even more hax..
                $event->metadata['tags'] = $existing->get_tag_list();
                $image = $this->create_image_from_data(warehouse_path(Image::IMAGE_DIR, $event->metadata['hash']), $event->metadata);

                if (is_null($image)) {
                    throw new UploadException("Data handler failed to create image object from data");
                }

                $ire = new ImageReplaceEvent($image_id, $image);
                send_event($ire);
                $event->image_id = $image_id;
            } else {
                $image = $this->create_image_from_data(warehouse_path(Image::IMAGE_DIR, $event->hash), $event->metadata);
                if (is_null($image)) {
                    throw new UploadException("Data handler failed to create image object from data");
                }
                $iae = new ImageAdditionEvent($image);
                send_event($iae);
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
        } elseif ($supported_ext && !$check_contents) {
            throw new UploadException("Invalid or corrupted file");
        }
    }

    public function onThumbnailGeneration(ThumbnailGenerationEvent $event)
    {
        $result = false;
        if ($this->supported_ext($event->type)) {
            if ($event->force) {
                $result = $this->create_thumb($event->hash, $event->type);
            } else {
                $outname = warehouse_path(Image::THUMBNAIL_DIR, $event->hash);
                if (file_exists($outname)) {
                    return;
                }
                $result = $this->create_thumb($event->hash, $event->type);
            }
        }
        if ($result) {
            $event->generated = true;
        }
    }

    public function onDisplayingImage(DisplayingImageEvent $event)
    {
        global $page;
        if ($this->supported_ext($event->image->ext)) {
            $this->theme->display_image($page, $event->image);
        }
    }

    /*
    public function onSetupBuilding(SetupBuildingEvent $event) {
        $sb = $this->setup();
        if($sb) $event->panel->add_block($sb);
    }

    protected function setup() {}
    */

    abstract protected function supported_ext(string $ext): bool;
    abstract protected function check_contents(string $tmpname): bool;
    abstract protected function create_image_from_data(string $filename, array $metadata);
    abstract protected function create_thumb(string $hash, string $type): bool;
}
