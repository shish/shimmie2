<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\emptyHTML;
use function MicroHTML\I;

/**
 * Occurs when some data is being uploaded.
 */
class DataUploadEvent extends Event
{
    public string $hash;
    public string $mime;
    public int $size;

    /** @var Image[] */
    public array $images = [];
    public bool $handled = false;
    public bool $merged = false;

    /**
     * Some data is being uploaded.
     * This should be caught by a file handler.
     *
     * @param string $tmpname The name of a physical file on the local hard drive.
     * @param string $filename The name of the file as it was uploaded.
     * @param int $slot The slot number of the upload.
     * @param array<string, string> $metadata Key-value pairs of metadata, the
     *    upload form can contain both common and slot-specific fields such as
     *    "source" and "source12", in which case the slot-specific field will
     *    override the common one.
     */
    public function __construct(
        public string $tmpname,
        public string $filename,
        public int $slot,
        public array $metadata,
    ) {
        parent::__construct();
        $this->set_tmpname($tmpname);
    }

    public function set_tmpname(string $tmpname, ?string $mime = null): void
    {
        assert(is_readable($tmpname));
        $this->tmpname = $tmpname;
        $this->hash = \Safe\md5_file($tmpname);
        $this->size = \Safe\filesize($tmpname);
        $mime = $mime ?? MimeType::get_for_file($tmpname, get_file_ext($this->filename));
        if (empty($mime)) {
            throw new UploadException("Could not determine mime type");
        }

        $this->mime = strtolower($mime);
    }
}

class UploadException extends SCoreException
{
}

abstract class UploadResult
{
    public function __construct(
        public string $name
    ) {
    }
}

class UploadError extends UploadResult
{
    public function __construct(
        string $name,
        public string $error
    ) {
        parent::__construct($name);
    }
}

class UploadSuccess extends UploadResult
{
    public function __construct(
        string $name,
        public int $image_id
    ) {
        parent::__construct($name);
    }
}

/**
 * Main upload class.
 * All files that are uploaded to the site are handled through this class.
 * This also includes transloaded files as well.
 */
class Upload extends Extension
{
    public const KEY = "upload";
    /** @var UploadTheme */
    protected Themelet $theme;
    public bool $is_full;

    /**
     * Early, so it can stop the DataUploadEvent before any data handlers see it.
     */
    public function get_priority(): int
    {
        return 40;
    }

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;

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
    }

    public function onPageNavBuilding(PageNavBuildingEvent $event): void
    {
        global $user;
        if ($user->can(ImagePermission::CREATE_IMAGE)) {
            $event->add_nav_link("upload", make_link('upload'), "Upload");
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent == "upload") {
            if (Extension::is_enabled(WikiInfo::KEY)) {
                $event->add_nav_link("upload_guidelines", make_link('wiki/upload_guidelines'), "Guidelines");
            }
        }
    }

    public function onDataUpload(DataUploadEvent $event): void
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

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $cache, $page, $user;

        if ($user->can(ImagePermission::CREATE_IMAGE)) {
            if ($this->is_full) {
                $this->theme->display_full($page);
            } else {
                $this->theme->display_block($page);
            }
        }

        if ($event->page_matches("upload", method: "GET", permission: ImagePermission::CREATE_IMAGE)) {
            if ($this->is_full) {
                throw new ServerError("Can't upload images: disk nearly full");
            }
            $this->theme->display_page($page);
        }
        if ($event->page_matches("upload", method: "POST", permission: ImagePermission::CREATE_IMAGE)) {
            if ($this->is_full) {
                throw new ServerError("Can't upload images: disk nearly full");
            }
            $results = [];

            $files = array_filter($_FILES, function ($file) {
                return !empty($file['name']);
            });
            foreach ($files as $name => $file) {
                $slot = int_escape(substr($name, 4));
                $results = array_merge($results, $this->try_upload($file, $slot, only_strings($event->POST)));
            }

            $urls = array_filter($event->POST, function ($value, $key) {
                return str_starts_with($key, "url") && is_string($value) && strlen($value) > 0;
            }, ARRAY_FILTER_USE_BOTH);
            foreach ($urls as $name => $value) {
                $slot = int_escape(substr($name, 3));
                $results = array_merge($results, $this->try_transload($value, $slot, only_strings($event->POST)));
            }

            $this->theme->display_upload_status($page, $results);
        }
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
     * @param mixed[] $file
     * @param array<string, string> $metadata
     * @return UploadResult[]
     */
    private function try_upload(array $file, int $slot, array $metadata): array
    {
        global $page, $config, $database;

        // blank file boxes cause empty uploads, no need for error message
        if (empty($file['name'])) {
            return [];
        }

        $results = [];

        for ($i = 0; $i < count($file['name']); $i++) {
            $name = $file['name'][$i];
            $error = $file['error'][$i];
            $tmp_name = $file['tmp_name'][$i];

            if (empty($name)) {
                continue;
            }
            try {
                // check if the upload was successful
                if ($error !== UPLOAD_ERR_OK) {
                    throw new UploadException($this->upload_error_message($error));
                }

                $new_images = $database->with_savepoint(function () use ($tmp_name, $name, $slot, $metadata) {
                    $event = send_event(new DataUploadEvent($tmp_name, basename($name), $slot, $metadata));
                    if (count($event->images) == 0) {
                        throw new UploadException("MIME type not supported: " . $event->mime);
                    }
                    return $event->images;
                });
                foreach ($new_images as $image) {
                    $results[] = new UploadSuccess($name, $image->id);
                }
            } catch (UploadException $ex) {
                $results[] = new UploadError($name, $ex->getMessage());
            }
        }

        return $results;
    }

    /**
     * @param non-empty-string $url
     * @param array<string, string> $metadata
     * @return UploadResult[]
     */
    private function try_transload(string $url, int $slot, array $metadata): array
    {
        global $page, $config, $user, $database;

        $results = [];
        $tmp_filename = shm_tempnam("transload");

        try {
            // Fetch file
            try {
                $headers = fetch_url($url, $tmp_filename);
            } catch (FetchException $e) {
                throw new UploadException("Error reading from $url: $e");
            }

            // Parse metadata
            $s_filename = find_header($headers, 'Content-Disposition');
            $h_filename = ($s_filename ? \Safe\preg_replace('/^.*filename="([^ ]+)"/i', '$1', $s_filename) : null);
            $filename = $h_filename ?: basename($url);

            $new_images = $database->with_savepoint(function () use ($tmp_filename, $filename, $slot, $metadata) {
                $event = send_event(new DataUploadEvent($tmp_filename, $filename, $slot, $metadata));
                if (count($event->images) == 0) {
                    throw new UploadException("File type not supported: " . $event->mime);
                }
                return $event->images;
            });
            foreach ($new_images as $image) {
                $results[] = new UploadSuccess($url, $image->id);
            }
        } catch (UploadException $ex) {
            $results[] = new UploadError($url, $ex->getMessage());
        } finally {
            if (file_exists($tmp_filename)) {
                unlink($tmp_filename);
            }
        }

        return $results;
    }
}
