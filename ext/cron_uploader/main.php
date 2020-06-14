<?php declare(strict_types=1);

require_once "config.php";

class CronUploader extends Extension
{
    /** @var CronUploaderTheme */
    protected $theme;

    public const NAME = "cron_uploader";

    // TODO: Checkbox option to only allow localhost + a list of additional IP addresses that can be set in /cron_upload

    const QUEUE_DIR = "queue";
    const UPLOADED_DIR = "uploaded";
    const FAILED_DIR = "failed_to_upload";

    private static $IMPORT_RUNNING = false;

    public function onInitExt(InitExtEvent $event)
    {
        // Set default values
        CronUploaderConfig::set_defaults();
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event)
    {
        if ($event->parent=="system") {
            $event->add_nav_link("cron_docs", new Link('cron_upload'), "Cron Upload");
        }
    }

    /**
     * Checks if the cron upload page has been accessed
     * and initializes the upload.
     */
    public function onPageRequest(PageRequestEvent $event)
    {
        global $user;

        if ($event->page_matches("cron_upload")) {
            if ($event->count_args() == 1) {
                $this->process_upload($event->get_arg(0)); // Start upload
            } elseif ($user->can(Permissions::CRON_ADMIN)) {
                $this->display_documentation();
            }
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        global $database;

        $documentation_link = make_http(make_link("cron_upload"));

        $users = $database->get_pairs("SELECT name, id FROM users UNION ALL SELECT '', null order by name");

        $sb = new SetupBlock("Cron Uploader");
        $sb->start_table();
        $sb->add_text_option(CronUploaderConfig::DIR, "Root dir", true);
        $sb->add_text_option(CronUploaderConfig::KEY, "Key", true);
        $sb->add_choice_option(CronUploaderConfig::USER, $users, "User", true);
        $sb->add_bool_option(CronUploaderConfig::STOP_ON_ERROR, "Stop On Error", true);
        $sb->add_choice_option(CronUploaderConfig::LOG_LEVEL, [
            LOGGING_LEVEL_NAMES[SCORE_LOG_DEBUG] => SCORE_LOG_DEBUG,
            LOGGING_LEVEL_NAMES[SCORE_LOG_INFO] => SCORE_LOG_INFO,
            LOGGING_LEVEL_NAMES[SCORE_LOG_WARNING] => SCORE_LOG_WARNING,
            LOGGING_LEVEL_NAMES[SCORE_LOG_ERROR] => SCORE_LOG_ERROR,
            LOGGING_LEVEL_NAMES[SCORE_LOG_CRITICAL] => SCORE_LOG_CRITICAL,
        ], "Output Log Level: ", true);
        $sb->add_bool_option(CronUploaderConfig::INCLUDE_ALL_LOGS, "Include All Logs", true);
        $sb->end_table();
        $sb->add_label("<a href='$documentation_link'>Read the documentation</a> for cron setup instructions.");

        $event->panel->add_block($sb);
    }

    public function onAdminBuilding(AdminBuildingEvent $event)
    {
        $failed_dir = $this->get_failed_dir();
        $results = get_dir_contents($failed_dir);

        $failed_dirs = [];
        foreach ($results as $result) {
            $path = join_path($failed_dir, $result);
            if (is_dir($path)) {
                $failed_dirs[] = $result;
            }
        }

        $this->theme->display_form($failed_dirs);
    }

    public function onAdminAction(AdminActionEvent $event)
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
                if (array_key_exists("failed_dir", $_POST) && !empty($_POST["failed_dir"])) {
                    $this->restage_folder($_POST["failed_dir"]);
                }
                break;
        }
    }

    public function onLog(LogEvent $event)
    {
        global $config;
        $all = $config->get_bool(CronUploaderConfig::INCLUDE_ALL_LOGS);
        if (self::$IMPORT_RUNNING &&
            $event->priority >= $config->get_int(CronUploaderConfig::LOG_LEVEL) &&
            ($event->section==self::NAME || $all)
        ) {
            $output =  "[" . date('Y-m-d H:i:s') . "] " . ($all ? '['. $event->section .'] ' :'') . "[" . LOGGING_LEVEL_NAMES[$event->priority] . "] " . $event->message  ;

            echo $output . "\r\n";
            flush_output();

            $log_path = $this->get_log_file();
            file_put_contents($log_path, $output);
        }
    }

    private function restage_folder(string $folder)
    {
        global $page;
        if (empty($folder)) {
            throw new SCoreException("folder empty");
        }
        $queue_dir = $this->get_queue_dir();
        $stage_dir = join_path($this->get_failed_dir(), $folder);

        if (!is_dir($stage_dir)) {
            throw new SCoreException("Could not find $stage_dir");
        }

        $this->prep_root_dir();

        $results = get_files_recursively($stage_dir);

        if (count($results) == 0) {
            if (remove_empty_dirs($stage_dir)===false) {
                $page->flash("Nothing to stage from $folder, cannot remove folder");
            } else {
                $page->flash("Nothing to stage from $folder, removing folder");
            }
            return;
        }
        foreach ($results as $result) {
            $new_path = join_path($queue_dir, substr($result, strlen($stage_dir)));

            if (file_exists($new_path)) {
                $page->flash("File already exists in queue folder: " .$result);
                return;
            }
        }

        $success = true;
        foreach ($results as $result) {
            $new_path = join_path($queue_dir, substr($result, strlen($stage_dir)));

            $dir = dirname($new_path);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            if (rename($result, $new_path)===false) {
                $page->flash("Could not move file: " .$result);
                $success = false;
            }
        }

        if ($success===true) {
            $page->flash("Re-staged $folder to queue");
            if (remove_empty_dirs($stage_dir)===false) {
                $page->flash("Could not remove $folder");
            }
        }
    }

    private function clear_folder($folder)
    {
        global $page;
        $path = join_path(CronUploaderConfig::get_dir(), $folder);
        deltree($path);
        $page->flash("Cleared $path");
    }


    private function get_cron_url()
    {
        return make_http(make_link("/cron_upload/" . CronUploaderConfig::get_key()));
    }

    private function get_cron_cmd()
    {
        return "curl --silent " . $this->get_cron_url();
    }

    private function display_documentation()
    {
        global $database;

        $this->prep_root_dir();

        $queue_dir = $this->get_queue_dir();
        $uploaded_dir = $this->get_uploaded_dir();
        $failed_dir = $this->get_failed_dir();

        $queue_dirinfo = scan_dir($queue_dir);
        $uploaded_dirinfo = scan_dir($uploaded_dir);
        $failed_dirinfo = scan_dir($failed_dir);


        $running = false;
        $lockfile = fopen($this->get_lock_file(), "w");
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
        if (Extension::is_enabled(LogDatabaseInfo::KEY)) {
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

    public function get_queue_dir()
    {
        $dir = CronUploaderConfig::get_dir();
        return join_path($dir, self::QUEUE_DIR);
    }

    public function get_uploaded_dir()
    {
        $dir = CronUploaderConfig::get_dir();
        return join_path($dir, self::UPLOADED_DIR);
    }

    public function get_failed_dir()
    {
        $dir = CronUploaderConfig::get_dir();
        return join_path($dir, self::FAILED_DIR);
    }

    private function prep_root_dir(): string
    {
        // Determine directory (none = default)
        $dir = CronUploaderConfig::get_dir();

        // Make the directory if it doesn't exist yet
        if (!is_dir($this->get_queue_dir())) {
            mkdir($this->get_queue_dir(), 0775, true);
        }
        if (!is_dir($this->get_uploaded_dir())) {
            mkdir($this->get_uploaded_dir(), 0775, true);
        }
        if (!is_dir($this->get_failed_dir())) {
            mkdir($this->get_failed_dir(), 0775, true);
        }

        return $dir;
    }

    private function get_lock_file(): string
    {
        $root_dir = CronUploaderConfig::get_dir();
        return join_path($root_dir, ".lock");
    }

    /**
     * Uploads the image & handles everything
     */
    public function process_upload(string $key, ?int $upload_count = null): bool
    {
        global $database, $config, $_shm_load_start;

        $max_time = intval(ini_get('max_execution_time'))*.8;

        $this->set_headers();

        if ($key!=CronUploaderConfig::get_key()) {
            throw new SCoreException("Cron upload key incorrect");
        }
        $user_id = CronUploaderConfig::get_user();
        if (empty($user_id)) {
            throw new SCoreException("Cron upload user not set");
        }
        $my_user = User::by_id($user_id);
        if ($my_user == null) {
            throw new SCoreException("No user found for cron upload user $user_id");
        }

        send_event(new UserLoginEvent($my_user));
        $this->log_message(SCORE_LOG_INFO, "Logged in as user {$my_user->name}");

        $lockfile = fopen($this->get_lock_file(), "w");
        if (!flock($lockfile, LOCK_EX | LOCK_NB)) {
            throw new SCoreException("Cron upload process is already running");
        }

        self::$IMPORT_RUNNING = true;
        try {
            //set_time_limit(0);

            $output_subdir = date('Ymd-His', time());
            $image_queue = $this->generate_image_queue(CronUploaderConfig::get_dir());

            // Randomize Images
            //shuffle($this->image_queue);

            $merged = 0;
            $added = 0;
            $failed = 0;

            // Upload the file(s)
            foreach ($image_queue as $img) {
                $execution_time = microtime(true) - $_shm_load_start;
                if ($execution_time>$max_time) {
                    break;
                }
                try {
                    $database->begin_transaction();
                    $this->log_message(SCORE_LOG_INFO, "Adding file: {$img[0]} - tags: {$img[2]}");
                    $result = $this->add_image($img[0], $img[1], $img[2]);
                    if ($database->is_transaction_open()) {
                        $database->commit();
                    }
                    $this->move_uploaded($img[0], $img[1], $output_subdir, false);
                    if ($result->merged) {
                        $merged++;
                    } else {
                        $added++;
                    }
                } catch (Exception $e) {
                    try {
                        if ($database->is_transaction_open()) {
                            $database->rollback();
                        }
                    } catch (Exception $e) {
                    }

                    $failed++;
                    $this->log_message(SCORE_LOG_ERROR, "(" . gettype($e) . ") " . $e->getMessage());
                    $this->log_message(SCORE_LOG_ERROR, $e->getTraceAsString());
                    if ($config->get_bool(CronUploaderConfig::STOP_ON_ERROR)) {
                        break;
                    } else {
                        $this->move_uploaded($img[0], $img[1], $output_subdir, true);
                    }
                }
            }

            // Throw exception if there's nothing in the queue
            if ($merged+$failed+$added === 0) {
                $this->log_message(SCORE_LOG_WARNING, "Your queue is empty so nothing could be uploaded.");
                return false;
            }

            $this->log_message(SCORE_LOG_INFO, "Items added: $added");
            $this->log_message(SCORE_LOG_INFO, "Items merged: $merged");
            $this->log_message(SCORE_LOG_INFO, "Items failed: $failed");


            return true;
        } finally {
            self::$IMPORT_RUNNING = false;
            flock($lockfile, LOCK_UN);
            fclose($lockfile);
        }
    }

    private function move_uploaded(string $path, string $filename, string $output_subdir, bool $corrupt = false)
    {
        $rootDir = CronUploaderConfig::get_dir();
        $rootLength = strlen($rootDir);
        if ($rootDir[$rootLength-1]=="/"||$rootDir[$rootLength-1]=="\\") {
            $rootLength--;
        }

        $relativeDir = dirname(substr($path, $rootLength + 7));

        if ($relativeDir==".") {
            $relativeDir = "";
        }

        // Determine which dir to move to
        if ($corrupt) {
            // Move to corrupt dir
            $newDir = join_path($this->get_failed_dir(), $output_subdir, $relativeDir);
            $info = "ERROR: Image was not uploaded. ";
        } else {
            $newDir = join_path($this->get_uploaded_dir(), $output_subdir, $relativeDir);
            $info = "Image successfully uploaded. ";
        }
        $newDir = str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $newDir);

        if (!is_dir($newDir)) {
            mkdir($newDir, 0775, true);
        }

        $newFile = join_path($newDir, $filename);
        // move file to correct dir
        rename($path, $newFile);

        $this->log_message(SCORE_LOG_INFO, $info . "Image \"$filename\" moved from queue to \"$newDir\".");
    }

    /**
     * Generate the necessary DataUploadEvent for a given image and tags.
     */
    private function add_image(string $tmpname, string $filename, string $tags): DataUploadEvent
    {
        assert(file_exists($tmpname));

        $tagArray = Tag::explode($tags);
        if (count($tagArray) == 0) {
            $tagArray[] = "tagme";
        }

        $pathinfo = pathinfo($filename);
        $metadata = [];
        $metadata ['filename'] = $pathinfo ['basename'];
        if (array_key_exists('extension', $pathinfo)) {
            $metadata ['extension'] = $pathinfo ['extension'];
        }
        $metadata ['tags'] = $tagArray;
        $metadata ['source'] = null;
        $event = new DataUploadEvent($tmpname, $metadata);
        send_event($event);

        // Generate info message
        if ($event->image_id == -1) {
            throw new UploadException("File type not recognised. Filename: {$filename}");
        } elseif ($event->merged === true) {
            $infomsg = "Image merged. ID: {$event->image_id} - Filename: {$filename}";
        } else {
            $infomsg = "Image uploaded. ID: {$event->image_id} - Filename: {$filename}";
        }
        $this->log_message(SCORE_LOG_INFO, $infomsg);

        return $event;
    }

    private const PARTIAL_DOWNLOAD_EXTENSIONS = ['crdownload','part'];
    private const SKIPPABLE_FILES = ['.ds_store','thumbs.db'];
    private const SKIPPABLE_DIRECTORIES = ['__macosx'];

    private function is_skippable_dir(string $path)
    {
        $info = pathinfo($path);

        if (array_key_exists("basename", $info) && in_array(strtolower($info['basename']), self::SKIPPABLE_DIRECTORIES)) {
            return true;
        }

        return false;
    }

    private function is_skippable_file(string $path)
    {
        $info = pathinfo($path);

        if (array_key_exists("basename", $info) && in_array(strtolower($info['basename']), self::SKIPPABLE_FILES)) {
            return true;
        }

        if (array_key_exists("extension", $info) && in_array(strtolower($info['extension']), self::PARTIAL_DOWNLOAD_EXTENSIONS)) {
            return true;
        }

        return false;
    }

    private function generate_image_queue(string $root_dir, ?int $limit = null): Generator
    {
        $base = $this->get_queue_dir();

        if (!is_dir($base)) {
            $this->log_message(SCORE_LOG_WARNING, "Image Queue Directory could not be found at \"$base\".");
            return;
        }

        $ite = new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS);
        foreach (new RecursiveIteratorIterator($ite) as $fullpath => $cur) {
            if (!is_link($fullpath) && !is_dir($fullpath) && !$this->is_skippable_file($fullpath)) {
                $pathinfo = pathinfo($fullpath);

                $relativePath = substr($fullpath, strlen($base));
                $tags = path_to_tags($relativePath);

                yield [
                    0 => $fullpath,
                    1 => $pathinfo ["basename"],
                    2 => $tags
                ];
            }
        }
    }


    private function log_message(int $severity, string $message): void
    {
        log_msg(self::NAME, $severity, $message);
    }

    private function get_log_file(): string
    {
        return join_path(CronUploaderConfig::get_dir(), "uploads.log");
    }

    private function set_headers(): void
    {
        global $page;

        $page->set_mode(PageMode::MANUAL);
        $page->set_mime(MimeType::TEXT);
        $page->send_headers();
    }
}
