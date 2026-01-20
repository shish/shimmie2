<?php

declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<CronUploaderTheme> */
final class CronUploader extends Extension
{
    public const KEY = "cron_uploader";

    public const NAME = "cron_uploader";

    // TODO: Checkbox option to only allow localhost + a list of additional IP addresses that can be set in /cron_upload

    public const QUEUE_DIR = "queue";
    public const UPLOADED_DIR = "uploaded";
    public const FAILED_DIR = "failed_to_upload";

    private static bool $IMPORT_RUNNING = false;

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "system") {
            $event->add_nav_link(make_link('cron_upload'), "Cron Upload", "cron_upload");
        }
    }

    /**
     * Checks if the cron upload page has been accessed
     * and initializes the upload.
     */
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("cron_upload/run")) {
            $this->process_upload();
        } elseif ($event->page_matches("cron_upload", permission: CronUploaderPermission::CRON_RUN)) {
            $this->display_documentation();
        }
    }

    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $failed_dir = $this->get_failed_dir();
        $results = Filesystem::get_dir_contents($failed_dir);

        $failed_dirs = [];
        foreach ($results as $result) {
            $path = Filesystem::join_path($failed_dir, $result);
            if ($path->is_dir()) {
                $failed_dirs[] = $result;
            }
        }

        $this->theme->display_form($failed_dirs);
    }

    public function onAdminAction(AdminActionEvent $event): void
    {
        $action = $event->action;
        switch ($action) {
            case "cron_uploader_clear_queue":
                $event->redirect = true;
                $this->clear_folder(self::QUEUE_DIR);
                break;
            case "cron_uploader_clear_uploaded":
                $event->redirect = true;
                $this->clear_folder(self::UPLOADED_DIR);
                break;
            case "cron_uploader_clear_failed":
                $event->redirect = true;
                $this->clear_folder(self::FAILED_DIR);
                break;
            case "cron_uploader_restage":
                $event->redirect = true;
                if (!empty($event->params["failed_dir"])) {
                    $this->restage_folder(new Path($event->params["failed_dir"]));
                }
                break;
        }
    }

    public function onLog(LogEvent $event): void
    {
        if (self::$IMPORT_RUNNING) {
            $all = Ctx::$user->get_config()->get(CronUploaderUserConfig::INCLUDE_ALL_LOGS);
            if ($event->priority >= Ctx::$user->get_config()->get(CronUploaderUserConfig::LOG_LEVEL) &&
                ($event->section === self::NAME || $all)) {
                $output = "[" . date('Y-m-d H:i:s') . "] " . ($all ? '[' . $event->section . '] ' : '') . "[" . LogLevel::from($event->priority)->name . "] " . $event->message;

                echo $output . "\r\n";
                flush_output();

                $log_path = $this->get_log_file();
                $log_path->put_contents($output);
            }
        }
    }

    private function restage_folder(Path $folder): void
    {
        $queue_dir = $this->get_queue_dir();
        $stage_dir = Filesystem::join_path($this->get_failed_dir(), $folder);

        if (!$stage_dir->is_dir()) {
            throw new InvalidInput("Could not find {$stage_dir->str()}");
        }

        $this->prep_root_dir();

        $results = Filesystem::get_files_recursively($stage_dir);

        if (count($results) === 0) {
            if (Filesystem::remove_empty_dirs($stage_dir) === false) {
                Ctx::$page->flash("Nothing to stage from {$folder->str()}, cannot remove folder");
            } else {
                Ctx::$page->flash("Nothing to stage from {$folder->str()}, removing folder");
            }
            return;
        }
        foreach ($results as $result) {
            $new_path = Filesystem::join_path($queue_dir, $result->relative_to($stage_dir));

            if ($new_path->exists()) {
                Ctx::$page->flash("File already exists in queue folder: " .$result->str());
                return;
            }
        }

        foreach ($results as $result) {
            $new_path = Filesystem::join_path($queue_dir, $result->relative_to($stage_dir));

            $dir = $new_path->dirname();
            if (!$dir->is_dir()) {
                $dir->mkdir(0775, true);
            }

            $result->rename($new_path);
        }

        Ctx::$page->flash("Re-staged {$folder->str()} to queue");
        if (Filesystem::remove_empty_dirs($stage_dir) === false) {
            Ctx::$page->flash("Could not remove {$folder->str()}");
        }
    }

    private function clear_folder(string $folder): void
    {
        $path = Filesystem::join_path($this->get_user_dir(), $folder);
        Filesystem::deltree($path);
        Ctx::$page->flash("Cleared {$path->str()}");
    }

    private function get_cron_url(): string
    {
        $user_api_key = Ctx::$user->get_config()->get(UserApiKeysUserConfig::API_KEY) ?? "API_KEY";
        return (string)make_link("cron_upload/run", ["api_key" => $user_api_key])->asAbsolute();
    }

    private function get_cron_cmd(): string
    {
        return "curl --silent " . $this->get_cron_url();
    }

    private function display_documentation(): void
    {
        global $database;

        $this->prep_root_dir();

        $queue_dir = $this->get_queue_dir();
        $uploaded_dir = $this->get_uploaded_dir();
        $failed_dir = $this->get_failed_dir();

        $queue_dirinfo = Filesystem::scan_dir($queue_dir);
        $uploaded_dirinfo = Filesystem::scan_dir($uploaded_dir);
        $failed_dirinfo = Filesystem::scan_dir($failed_dir);


        $running = false;
        $lockfile = \Safe\fopen($this->get_lock_file()->str(), "w");
        try {
            if (!flock($lockfile, LOCK_EX | LOCK_NB)) {
                $running = true;
            } else {
                flock($lockfile, LOCK_UN);
            }
        } finally {
            fclose($lockfile);
        }

        $logs = [];
        if (LogDatabaseInfo::is_enabled()) {
            /** @var array<array{date_sent: string, message: string}> $logs */
            $logs = $database->get_all(
                "SELECT * FROM score_log WHERE section = :section ORDER BY date_sent DESC LIMIT 100",
                ["section" => self::NAME]
            );
        }

        $this->theme->display_documentation(
            $running,
            $queue_dirinfo,
            $uploaded_dirinfo,
            $failed_dirinfo,
            $this->get_cron_cmd(),
            $this->get_cron_url(),
            $logs
        );
    }

    private function get_user_dir(): Path
    {
        return new Path(
            Ctx::$user->get_config()->get(CronUploaderUserConfig::DIR)
            ?? Filesystem::data_path(Filesystem::join_path("cron_uploader", Ctx::$user->name))->str()
        );
    }

    public function get_queue_dir(): Path
    {
        return Filesystem::join_path($this->get_user_dir(), self::QUEUE_DIR);
    }

    public function get_uploaded_dir(): Path
    {
        return Filesystem::join_path($this->get_user_dir(), self::UPLOADED_DIR);
    }

    public function get_failed_dir(): Path
    {
        return Filesystem::join_path($this->get_user_dir(), self::FAILED_DIR);
    }

    private function prep_root_dir(): Path
    {
        // Make the directory if it doesn't exist yet
        if (!$this->get_queue_dir()->is_dir()) {
            $this->get_queue_dir()->mkdir(0775, true);
        }
        if (!$this->get_uploaded_dir()->is_dir()) {
            $this->get_uploaded_dir()->mkdir(0775, true);
        }
        if (!$this->get_failed_dir()->is_dir()) {
            $this->get_failed_dir()->mkdir(0775, true);
        }

        return $this->get_user_dir();
    }

    private function get_lock_file(): Path
    {
        return Filesystem::join_path($this->get_user_dir(), ".lock");
    }

    /**
     * Uploads the image & handles everything
     */
    public function process_upload(): bool
    {
        global $database;

        $max_time = intval(ini_get('max_execution_time')) * .8;

        Ctx::$page->set_mode(PageMode::MANUAL);
        Ctx::$page->add_http_header("Content-Type: text/plain");
        Ctx::$page->send_headers();

        if (!Ctx::$user->can(CronUploaderPermission::CRON_RUN)) {
            throw new PermissionDenied("User does not have permission to run cron upload");
        }

        Log::info(self::NAME, "Logged in as user " . Ctx::$user->name);

        $lockfile = \Safe\fopen($this->get_lock_file()->str(), "w");
        if (!flock($lockfile, LOCK_EX | LOCK_NB)) {
            throw new ServerError("Cron upload process is already running");
        }

        self::$IMPORT_RUNNING = true;
        try {
            //Ctx::$event_bus->set_timeout(null);

            $output_subdir = date('Ymd-His', time());
            $image_queue = $this->generate_image_queue();

            // Randomize Images
            //shuffle($this->image_queue);

            $merged = 0;
            $added = 0;
            $failed = 0;

            // Upload the file(s)
            foreach ($image_queue as $img) {
                $execution_time = ftime() - $_SERVER["REQUEST_TIME_FLOAT"];
                if ($execution_time > $max_time) {
                    break;
                } else {
                    $remaining = $max_time - $execution_time;
                    Log::debug(self::NAME, "Max run time remaining: $remaining");
                }
                try {
                    $result = $database->with_savepoint(function () use ($img, $output_subdir) {
                        Log::info(self::NAME, "Adding file: {$img[0]} - tags: {$img[2]}");
                        $result = $this->add_image($img[0], $img[1], $img[2]);
                        $this->move_uploaded($img[0], $img[1], $output_subdir, false);
                        return $result;
                    });
                    if ($result->merged) {
                        $merged++;
                    } else {
                        $added++;
                    }
                } catch (\Exception $e) {
                    $failed++;
                    Log::error(self::NAME, "(" . gettype($e) . ") " . $e->getMessage());
                    Log::error(self::NAME, $e->getTraceAsString());
                    if (Ctx::$user->get_config()->get(CronUploaderUserConfig::STOP_ON_ERROR)) {
                        break;
                    } else {
                        $this->move_uploaded($img[0], $img[1], $output_subdir, true);
                    }
                }
            }

            // Throw exception if there's nothing in the queue
            if ($merged + $failed + $added === 0) {
                Log::warning(self::NAME, "Your queue is empty so nothing could be uploaded.");
                return false;
            }

            Log::info(self::NAME, "Items added: $added");
            Log::info(self::NAME, "Items merged: $merged");
            Log::info(self::NAME, "Items failed: $failed");


            return true;
        } finally {
            self::$IMPORT_RUNNING = false;
            flock($lockfile, LOCK_UN);
            fclose($lockfile);
        }
    }

    private function move_uploaded(Path $path, string $filename, string $output_subdir, bool $corrupt = false): void
    {
        $relativeDir = $path->relative_to($this->get_user_dir())->dirname();

        // Determine which dir to move to
        if ($corrupt) {
            // Move to corrupt dir
            $newDir = Filesystem::join_path($this->get_failed_dir(), $output_subdir, $relativeDir);
            $info = "ERROR: Post was not uploaded. ";
        } else {
            $newDir = Filesystem::join_path($this->get_uploaded_dir(), $output_subdir, $relativeDir);
            $info = "Post successfully uploaded. ";
        }

        if (!$newDir->is_dir()) {
            $newDir->mkdir(0775, true);
        }

        $newFile = Filesystem::join_path($newDir, $filename);
        // move file to correct dir
        $path->rename($newFile);

        Log::info(self::NAME, $info . "Post \"$filename\" moved from queue to \"{$newDir->str()}\".");
    }

    /**
     * Generate the necessary DataUploadEvent for a given image and tags.
     *
     * @param tag-array $tags
     */
    private function add_image(Path $tmpname, string $filename, array $tags): DataUploadEvent
    {
        $event = send_event(new DataUploadEvent($tmpname, basename($filename), 0, new QueryArray([
            'tags' => Tag::implode($tags),
        ])));

        // Generate info message
        if (count($event->images) === 0) {
            throw new UploadException("File type not recognised (".$event->mime."). Filename: {$filename}");
        } elseif ($event->merged === true) {
            $infomsg = "Post merged. ID: {$event->images[0]->id} - Filename: {$filename}";
        } else {
            $infomsg = "Post uploaded. ID: {$event->images[0]->id} - Filename: {$filename}";
        }
        Log::info(self::NAME, $infomsg);

        return $event;
    }

    private const PARTIAL_DOWNLOAD_EXTENSIONS = ['crdownload','part'];
    private const SKIPPABLE_FILES = ['.ds_store', 'thumbs.db', 'desktop.ini', '.listing'];

    private function is_skippable_file(string $path): bool
    {
        $info = pathinfo($path);

        if (in_array(strtolower($info['basename']), self::SKIPPABLE_FILES)) {
            return true;
        }

        if (array_key_exists("extension", $info) && in_array(strtolower($info['extension']), self::PARTIAL_DOWNLOAD_EXTENSIONS)) {
            return true;
        }

        return false;
    }

    private function generate_image_queue(): \Generator
    {
        $base = $this->get_queue_dir();

        if (!$base->is_dir()) {
            Log::warning(self::NAME, "Post Queue Directory could not be found at \"{$base->str()}\".");
            return;
        }

        $ite = new \RecursiveDirectoryIterator($base->str(), \FilesystemIterator::SKIP_DOTS);
        foreach (new \RecursiveIteratorIterator($ite) as $fullpath => $cur) {
            if (!is_link($fullpath) && !is_dir($fullpath) && !$this->is_skippable_file($fullpath)) {
                $relativePath = substr($fullpath, strlen($base->str()));
                assert(!empty($relativePath), "Relative path cannot be empty");
                $tags = Filesystem::path_to_tags(new Path($relativePath));

                yield [
                    0 => $fullpath,
                    1 => pathinfo($fullpath, PATHINFO_BASENAME),
                    2 => $tags
                ];
            }
        }
    }

    private function get_log_file(): Path
    {
        return Filesystem::join_path($this->get_user_dir(), "uploads.log");
    }
}
