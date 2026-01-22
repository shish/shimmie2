<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Occurs when some data is being uploaded.
 */
final class DataUploadEvent extends Event
{
    /** @var hash-string */
    public string $hash;
    public MimeType $mime;
    public int $size;

    /** @var Image[] */
    public array $images = [];
    public bool $handled = false;
    public bool $merged = false;

    /**
     * Some data is being uploaded.
     * This should be caught by a file handler.
     *
     * @param Path $tmpname The name of a physical file on the local hard drive.
     * @param string $filename The name of the file as it was uploaded.
     * @param int $slot The slot number of the upload.
     * @param QueryArray $metadata Key-value pairs of metadata, the
     *    upload form can contain both common and slot-specific fields such as
     *    "source" and "source12", in which case the slot-specific field will
     *    override the common one.
     */
    public function __construct(
        public Path $tmpname,
        public string $filename,
        public int $slot,
        public QueryArray $metadata,
    ) {
        parent::__construct();
        $this->set_tmpname($tmpname);
    }

    public function set_tmpname(Path $tmpname, ?MimeType $mime = null): void
    {
        assert($tmpname->is_readable());
        $this->tmpname = $tmpname;
        $this->hash = $tmpname->md5();
        $this->size = $tmpname->filesize();
        $this->mime = MimeMap::get_canonical($mime ?? MimeType::get_for_file($tmpname, pathinfo($this->filename)['extension'] ?? null));
    }
}

final class DirectoryUploadEvent extends Event
{
    /** @var UploadResult[] */
    public array $results = [];

    /**
     * @param tag-array $extra_tags
     */
    public function __construct(
        public Path $base,
        public array $extra_tags = [],
    ) {
        parent::__construct();
    }
}

final class UploadException extends SCoreException
{
}

abstract class UploadResult
{
    public function __construct(
        public string $name
    ) {
    }
}

final class UploadError extends UploadResult
{
    public function __construct(
        string $name,
        public string $error
    ) {
        parent::__construct($name);
    }
}

final class UploadSuccess extends UploadResult
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
 *
 * @extends Extension<UploadTheme>
 */
final class Upload extends Extension
{
    public const KEY = "upload";
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
        $this->is_full = false;
        $min_free_space = Ctx::$config->get(UploadConfig::MIN_FREE_SPACE);
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
        if (Ctx::$user->can(ImagePermission::CREATE_IMAGE)) {
            $event->add_nav_link(make_link('upload'), "Upload", "upload");
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "upload") {
            if (WikiInfo::is_enabled()) {
                $event->add_nav_link(make_link('wiki/upload_guidelines'), "Guidelines", "guidelines");
            }
        }
    }

    public function onDataUpload(DataUploadEvent $event): void
    {
        if ($this->is_full) {
            throw new UploadException("Upload failed; disk nearly full");
        }
        if ($event->size > Ctx::$config->get(UploadConfig::SIZE)) {
            $size = to_shorthand_int($event->size);
            $limit = to_shorthand_int(Ctx::$config->get(UploadConfig::SIZE));
            throw new UploadException("File too large ($size > $limit)");
        }
    }

    public function onDirectoryUpload(DirectoryUploadEvent $event): void
    {
        global $database;
        $results = [];

        foreach (Filesystem::list_files($event->base) as $full_path) {
            $short_path = $full_path->relative_to($event->base);
            $filename = $full_path->basename()->str();

            $tags = array_merge(Filesystem::path_to_tags($short_path), $event->extra_tags);
            try {
                $more_results = $database->with_savepoint(function () use ($full_path, $filename, $tags) {
                    $dae = send_event(new DataUploadEvent($full_path, $filename, 0, new QueryArray([
                        'filename' => pathinfo($filename, PATHINFO_BASENAME),
                        'tags' => Tag::implode($tags),
                    ])));
                    $results = [];
                    foreach ($dae->images as $image) {
                        $results[] = new UploadSuccess($filename, $image->id);
                    }
                    return $results;
                });
                $results = array_merge($results, $more_results);
            } catch (UploadException $ex) {
                $results[] = new UploadError($filename, $ex->getMessage());
            }
        }

        $event->results = array_merge($event->results, $results);
    }

    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('post:upload')
            ->setDescription("Upload a file from disk")
            ->addArgument('file', InputArgument::REQUIRED, 'The file to upload')
            ->addArgument('metadata', InputArgument::OPTIONAL, 'Key-value pairs for metadata', 'tags=test')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                if (!Ctx::$user->can(ImagePermission::CREATE_IMAGE)) {
                    $output->writeln("<error>Permission denied</error>");
                    return Command::FAILURE;
                }
                $file_path = new Path($input->getArgument('file'));
                if (!$file_path->is_readable()) {
                    $output->writeln("<error>File not found: {$file_path->str()}</error>");
                    return Command::FAILURE;
                }
                $arr = [];
                parse_str((string)$input->getArgument('metadata'), $arr);
                /** @var array<string,string> $arr */
                $metadata = new QueryArray($arr);
                $event = send_event(new DataUploadEvent($file_path, $file_path->basename()->str(), 0, $metadata));
                $images = $event->images;
                if (count($images) === 0) {
                    $output->writeln("<error>No crash, but no posts uploaded</error>");
                    return Command::FAILURE;
                }
                return Command::SUCCESS;
            });
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        if (Ctx::$user->can(ImagePermission::CREATE_IMAGE)) {
            if ($this->is_full) {
                $this->theme->display_full();
            } else {
                $this->theme->display_block();
            }
        }

        if ($event->page_matches("upload", method: "GET", permission: ImagePermission::CREATE_IMAGE)) {
            if ($this->is_full) {
                throw new ServerError("Can't upload images: disk nearly full");
            }
            $this->theme->display_page();
        }
        if ($event->page_matches("upload", method: "POST", permission: ImagePermission::CREATE_IMAGE)) {
            if ($this->is_full) {
                throw new ServerError("Can't upload images: disk nearly full");
            }
            if (!Captcha::check(UploadPermission::SKIP_UPLOAD_CAPTCHA)) {
                throw new PermissionDenied("Invalid CAPTCHA");
            }

            $results = [];

            $files = array_filter($_FILES, function ($file) {
                return !empty($file['name']);
            });
            foreach ($files as $name => $file) {
                $slot = int_escape(substr($name, 4));
                $results = array_merge($results, $this->try_upload($file, $slot, $event->POST));
            }

            $urls = array_filter($event->POST->toArray(), function ($value, $key) {
                return str_starts_with($key, "url") && is_string($value) && strlen($value) > 0;
            }, ARRAY_FILTER_USE_BOTH);
            foreach ($urls as $name => $value) {
                $slot = int_escape(substr($name, 3));
                $results = array_merge($results, $this->try_transload($value, $slot, $event->POST));
            }

            $this->theme->display_upload_status($results);
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
        return match ($error_code) {
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
            default => 'Unknown upload error',
        };
    }

    /**
     * Handle an upload.
     * @param mixed[] $file
     * @return UploadResult[]
     */
    private function try_upload(array $file, int $slot, QueryArray $metadata): array
    {
        global $database;

        // blank file boxes cause empty uploads, no need for error message
        if (empty($file['name'])) {
            return [];
        }

        $results = [];

        for ($i = 0; $i < count($file['name']); $i++) {
            $name = $file['name'][$i];
            $error = $file['error'][$i];
            $tmp_name = new Path($file['tmp_name'][$i]);

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
                    if (count($event->images) === 0) {
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
     * @return UploadResult[]
     */
    private function try_transload(string $url, int $slot, QueryArray $metadata): array
    {
        $results = [];
        $tmp_filename = shm_tempnam("transload");

        try {
            // Fetch file
            try {
                $headers = Network::fetch_url($url, $tmp_filename);
            } catch (FetchException $e) {
                throw new UploadException("Error reading from $url: $e");
            }

            // Parse metadata
            $s_filename = Network::find_header($headers, 'Content-Disposition');
            assert(is_string($s_filename));
            $h_filename = ($s_filename ? \Safe\preg_replace('/^.*filename="([^ ]+)"/i', '$1', $s_filename) : null);
            $filename = $h_filename ?: basename($url);

            $new_images = Ctx::$database->with_savepoint(function () use ($tmp_filename, $filename, $slot, $metadata) {
                $event = send_event(new DataUploadEvent($tmp_filename, $filename, $slot, $metadata));
                if (count($event->images) === 0) {
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
            if ($tmp_filename->exists()) {
                $tmp_filename->unlink();
            }
        }

        return $results;
    }
}
