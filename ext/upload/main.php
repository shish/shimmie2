<?php

declare(strict_types=1);

namespace Shimmie2;

require_once "config.php";

/**
 * Occurs when some data is being uploaded.
 */
class DataUploadEvent extends Event
{
    public string $hash;
    public string $mime;
    public int $size;

    public int $image_id = -1;
    public bool $handled = false;
    public bool $merged = false;

    /**
     * Some data is being uploaded.
     * This should be caught by a file handler.
     */
    public function __construct(
        public string $tmpname,
        public array $metadata,
        public ?int $replace_id = null
    ) {
        parent::__construct();

        $this->set_tmpname($tmpname);
        assert(is_string($metadata["filename"]));
        assert(is_array($metadata["tags"]));
        assert(is_string($metadata["source"]) || is_null($metadata["source"]));

        // DB limits to 255 char filenames
        $metadata['filename'] = substr($metadata['filename'], 0, 255);
    }

    public function set_tmpname(string $tmpname, ?string $mime=null)
    {
        assert(is_readable($tmpname));
        $this->tmpname = $tmpname;
        $this->hash = md5_file($tmpname);
        $this->size = filesize($tmpname);
        $mime = $mime ?? MimeType::get_for_file($tmpname, get_file_ext($this->metadata["filename"]) ?? null);
        if (empty($mime)) {
            throw new UploadException("Could not determine mime type");
        }

        $this->mime = strtolower($mime);
    }
}

class UploadException extends SCoreException
{
}

/**
 * Main upload class.
 * All files that are uploaded to the site are handled through this class.
 * This also includes transloaded files as well.
 */
class Upload extends Extension
{
    /** @var UploadTheme */
    protected ?Themelet $theme;
    public bool $is_full;

    /**
     * Early, so it can stop the DataUploadEvent before any data handlers see it.
     */
    public function get_priority(): int
    {
        return 40;
    }

    public function onInitExt(InitExtEvent $event)
    {
        global $config;
        $config->set_default_int(UploadConfig::COUNT, 3);
        $config->set_default_int(UploadConfig::SIZE, parse_shorthand_int('1MB'));
        $config->set_default_int(UploadConfig::MIN_FREE_SPACE, parse_shorthand_int('100MB'));
        $config->set_default_bool(UploadConfig::TLSOURCE, true);

        $this->is_full = false;

        $min_free_space = $config->get_int(UploadConfig::MIN_FREE_SPACE);
        if ($min_free_space > 0) {
            // SHIT: fucking PHP "security" measures -_-;;;
            $img_path = realpath("./images/");
            if ($img_path) {
                $free_num = @disk_free_space($img_path);
                if ($free_num !== false) {
                    $this->is_full = $free_num < $min_free_space;
                }
            }
        }

        $config->set_default_bool(UploadConfig::MIME_CHECK_ENABLED, false);
        $config->set_default_array(
            UploadConfig::ALLOWED_MIME_STRINGS,
            DataHandlerExtension::get_all_supported_mimes()
        );
    }

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $tes = [];
        $tes["Disabled"] = "none";
        if (function_exists("curl_init")) {
            $tes["cURL"] = "curl";
        }
        $tes["fopen"] = "fopen";
        $tes["WGet"] = "wget";

        $sb = $event->panel->create_new_block("Upload");
        $sb->position = 10;
        // Output the limits from PHP so the user has an idea of what they can set.
        $sb->add_int_option(UploadConfig::COUNT, "Max uploads: ");
        $sb->add_label("<i>PHP Limit = " . ini_get('max_file_uploads') . "</i>");
        $sb->add_shorthand_int_option(UploadConfig::SIZE, "<br/>Max size per file: ");
        $sb->add_label("<i>PHP Limit = " . ini_get('upload_max_filesize') . "</i>");
        $sb->add_choice_option(UploadConfig::TRANSLOAD_ENGINE, $tes, "<br/>Transload: ");
        $sb->add_bool_option(UploadConfig::TLSOURCE, "<br/>Use transloaded URL as source if none is provided: ");

        $sb->start_table();
        $sb->add_bool_option(UploadConfig::MIME_CHECK_ENABLED, "Enable upload MIME checks", true);
        $sb->add_multichoice_option(UploadConfig::ALLOWED_MIME_STRINGS, $this->get_mime_options(), "Allowed MIME uploads", true);
        $sb->end_table();
    }

    private function get_mime_options(): array
    {
        $output = [];
        foreach (DataHandlerExtension::get_all_supported_mimes() as $mime) {
            $output[MimeMap::get_name_for_mime($mime)] = $mime;
        }
        return $output;
    }

    public function onPageNavBuilding(PageNavBuildingEvent $event)
    {
        global $user;
        if ($user->can(Permissions::CREATE_IMAGE)) {
            $event->add_nav_link("upload", new Link('upload'), "Upload");
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event)
    {
        if ($event->parent=="upload") {
            if (class_exists("Shimmie2\Wiki")) {
                $event->add_nav_link("upload_guidelines", new Link('wiki/upload_guidelines'), "Guidelines");
            }
        }
    }

    public function onDataUpload(DataUploadEvent $event)
    {
        global $config;
        if ($this->is_full) {
            throw new UploadException("Upload failed; disk nearly full");
        }
        if ($event->size > $config->get_int(UploadConfig::SIZE)) {
            $size = to_shorthand_int($event->size);
            $limit = to_shorthand_int($config->get_int(UploadConfig::SIZE));
            throw new UploadException("File too large ($size > $limit)");
        }
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $cache, $page, $user;

        if ($user->can(Permissions::CREATE_IMAGE)) {
            if ($this->is_full) {
                $this->theme->display_full($page);
            } else {
                $this->theme->display_block($page);
            }
        }

        if ($event->page_matches("upload/replace")) {
            if (!$user->can(Permissions::REPLACE_IMAGE)) {
                $this->theme->display_error(403, "Error", "{$user->name} doesn't have permission to replace images");
                return;
            }
            if ($this->is_full) {
                $this->theme->display_error(507, "Error", "Can't replace images: disk nearly full");
                return;
            }

            // Try to get the image ID
            if ($event->count_args() >= 1) {
                $image_id = int_escape($event->get_arg(0));
            } elseif (isset($_POST['image_id'])) {
                $image_id = int_escape($_POST['image_id']);
            } else {
                throw new UploadException("Can not replace Post: No valid Post ID given.");
            }
            $image_old = Image::by_id($image_id);
            if (is_null($image_old)) {
                throw new UploadException("Can not replace Post: No post with ID $image_id");
            }

            $source = $_POST['source'] ?? null;

            if (!empty($_POST["url"])) {
                $image_ids = $this->try_transload($_POST["url"], [], $source, $image_id);
                $cache->delete("thumb-block:{$image_id}");
                $this->theme->display_upload_status($page, $image_ids);
            } elseif (count($_FILES) > 0) {
                $image_ids = $this->try_upload($_FILES["data"], [], $source, $image_id);
                $cache->delete("thumb-block:{$image_id}");
                $this->theme->display_upload_status($page, $image_ids);
            } elseif (!empty($_GET['url'])) {
                $image_ids = $this->try_transload($_GET['url'], [], $source, $image_id);
                $cache->delete("thumb-block:{$image_id}");
                $this->theme->display_upload_status($page, $image_ids);
            } else {
                $this->theme->display_replace_page($page, $image_id);
            }
        } elseif ($event->page_matches("upload")) {
            if (!$user->can(Permissions::CREATE_IMAGE)) {
                $this->theme->display_error(403, "Error", "{$user->name} doesn't have permission to upload images");
                return;
            }
            if ($this->is_full) {
                $this->theme->display_error(507, "Error", "Can't upload images: disk nearly full");
                return;
            }

            /* Regular Upload Image */
            if (count($_FILES) > 0 || count($_POST) > 0) {
                $image_ids = [];

                foreach ($_FILES as $name => $file) {
                    $tags = $this->tags_for_upload_slot(int_escape(substr($name, 4)));
                    $source = $_POST['source'] ?? null;
                    $image_ids += $this->try_upload($file, $tags, $source);
                }
                foreach ($_POST as $name => $value) {
                    if (str_starts_with($name, "url") && strlen($value) > 0) {
                        $tags = $this->tags_for_upload_slot(int_escape(substr($name, 3)));
                        $source = $_POST['source'] ?? $value;
                        $image_ids += $this->try_transload($value, $tags, $source);
                    }
                }

                $this->theme->display_upload_status($page, $image_ids);
            } elseif (!empty($_GET['url'])) {
                $url = $_GET['url'];
                $source = $_GET['source'] ?? $url;
                $tags = ['tagme'];
                if (!empty($_GET['tags']) && $_GET['tags'] != "null") {
                    $tags = Tag::explode($_GET['tags']);
                }

                $image_ids = $this->try_transload($url, $tags, $source);
                $this->theme->display_upload_status($page, $image_ids);
            } else {
                $this->theme->display_page($page);
            }
        }
    }

    private function tags_for_upload_slot(int $id): array
    {
        # merge then explode, not explode then merge - else
        # one of the merges may create a surplus "tagme"
        return Tag::explode(
            ($_POST["tags"] ?? "") .
            " " .
            ($_POST["tags$id"] ?? "")
        );
    }

    /**
     * Returns a descriptive error message for the specified PHP error code.
     *
     * This is a helper function based on the one from the online PHP Documentation
     * which is licensed under Creative Commons Attribution 3.0 License
     *
     * TODO: Make these messages user/admin editable
     */
    private function upload_error_message(int $error_code): string
    {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }

    /**
     * Handle an upload.
     * #param string[] $file
     * #param string[] $tags
     */
    private function try_upload(array $file, array $tags, ?string $source = null, ?int $replace_id = null): array
    {
        global $page, $config;

        // blank file boxes cause empty uploads, no need for error message
        if (empty($file['name'])) {
            return [];
        }

        if (empty($source)) {
            $source = null;
        }

        $image_ids = [];

        $num_files = count($file['name']);
        $limit = $config->get_int(UploadConfig::COUNT);
        try {
            if ($num_files > $limit) {
                throw new UploadException("Upload limited to $limit");
            }

            for ($i = 0; $i < $num_files; $i++) {
                if (empty($file['name'][$i])) {
                    continue;
                }
                try {
                    // check if the upload was successful
                    if ($file['error'][$i] !== UPLOAD_ERR_OK) {
                        throw new UploadException($this->upload_error_message($file['error'][$i]));
                    }

                    $metadata = [];
                    $metadata['filename'] = pathinfo($file['name'][$i], PATHINFO_BASENAME);
                    $metadata['tags'] = $tags;
                    $metadata['source'] = $source;

                    $event = new DataUploadEvent($file['tmp_name'][$i], $metadata, $replace_id);
                    send_event($event);
                    if ($event->image_id == -1) {
                        throw new UploadException("MIME type not supported: " . $event->mime);
                    }
                    $image_ids[] = $event->image_id;
                    $page->add_http_header("X-Shimmie-Post-ID: " . $event->image_id);
                } catch (UploadException $ex) {
                    $this->theme->display_upload_error(
                        $page,
                        "Error with " . html_escape($file['name'][$i]),
                        $ex->getMessage()
                    );
                }
            }
        } catch (UploadException $ex) {
            $this->theme->display_upload_error(
                $page,
                "Error with upload",
                $ex->getMessage()
            );
        }

        return $image_ids;
    }

    private function try_transload(string $url, array $tags, string $source = null, ?int $replace_id = null): array
    {
        global $page, $config, $user;

        $image_ids = [];
        $tmp_filename = tempnam(ini_get('upload_tmp_dir'), "shimmie_transload");

        try {
            // Fetch file
            $headers = fetch_url($url, $tmp_filename);
            if (is_null($headers)) {
                log_warning("core-util", "Failed to fetch $url");
                throw new UploadException("Error reading from " . html_escape($url));
            }
            if (filesize($tmp_filename) == 0) {
                throw new UploadException("No data found in " . html_escape($url) . " -- perhaps the site has hotlink protection?");
            }

            // Parse metadata
            $s_filename = find_header($headers, 'Content-Disposition');
            $h_filename = ($s_filename ? preg_replace('/^.*filename="([^ ]+)"/i', '$1', $s_filename) : null);
            $filename = $h_filename ?: basename($url);

            $metadata = [];
            $metadata['filename'] = $filename;
            $metadata['tags'] = $tags;
            $metadata['source'] = (($url == $source) && !$config->get_bool(UploadConfig::TLSOURCE) ? "" : $source);
            if ($user->can(Permissions::EDIT_IMAGE_LOCK) && !empty($_GET['locked'])) {
                $metadata['locked'] = bool_escape($_GET['locked']) ? "on" : "";
            }
            if (Extension::is_enabled(RatingsInfo::KEY) && !empty($_GET['rating'])) {
                // Rating event will validate that this is s/q/e/u
                $metadata['rating'] = strtolower($_GET['rating'])[0];
            }

            // Upload file
            $event = new DataUploadEvent($tmp_filename, $metadata, $replace_id);
            send_event($event);
            if ($event->image_id == -1) {
                throw new UploadException("File type not supported: " . $event->mime);
            }
            $image_ids[] = $event->image_id;
        } catch (UploadException $ex) {
            $this->theme->display_upload_error(
                $page,
                "Error with " . html_escape($url),
                $ex->getMessage()
            );
        } finally {
            if (file_exists($tmp_filename)) {
                unlink($tmp_filename);
            }
        }

        return $image_ids;
    }
}
