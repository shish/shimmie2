<?php

require_once "config.php";

class CronUploader extends Extension
{
    public const NAME = "cron_uploader";

    // TODO: Checkbox option to only allow localhost + a list of additional IP addresses that can be set in /cron_upload

    const QUEUE_DIR = "queue";
    const UPLOADED_DIR = "uploaded";
    const FAILED_DIR = "failed_to_upload";

    public $output_buffer = [];

    public function onInitExt(InitExtEvent $event)
    {
        // Set default values
        CronUploaderConfig::set_defaults();
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event)
    {
        if($event->parent=="system") {
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
            $key = $event->get_arg(0);
            if (!empty($key)) {
                $this->process_upload($key); // Start upload
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
        $sb->add_int_option(CronUploaderConfig::COUNT, "Upload per run", true);
        $sb->add_text_option(CronUploaderConfig::DIR, "Root dir", true);
        $sb->add_text_option(CronUploaderConfig::KEY, "Key", true);
        $sb->add_choice_option(CronUploaderConfig::USER, $users,"User", true);
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

    private function restage_folder(string $folder)
    {
        if (empty($folder)) {
            throw new Exception("folder empty");
        }
        $queue_dir = $this->get_queue_dir();
        $stage_dir = join_path($this->get_failed_dir(), $folder);

        if (!is_dir($stage_dir)) {
            throw new Exception("Could not find $stage_dir");
        }

        $this->prep_root_dir();

        $results = get_dir_contents($queue_dir);

        if (count($results) > 0) {
            flash_message("Queue folder must be empty to re-stage", "error");
            return;
        }

        $results = get_dir_contents($stage_dir);

        if (count($results) == 0) {
            if(rmdir($stage_dir)===false) {
                flash_message("Nothing to stage from $folder, cannot remove folder");
            } else {
                flash_message("Nothing to stage from $folder, removing folder");
            }
            return;
        }

        foreach ($results as $result) {
            $original_path = join_path($stage_dir, $result);
            $new_path = join_path($queue_dir, $result);

            rename($original_path, $new_path);
        }

        flash_message("Re-staged $folder to queue");
        rmdir($stage_dir);
    }

    private function clear_folder($folder)
    {
        $path = join_path(CronUploaderConfig::get_dir(), $folder);
        deltree($path);
        flash_message("Cleared $path");
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
            $running, $queue_dirinfo, $uploaded_dirinfo, $failed_dirinfo,
            $this->get_cron_cmd(), $this->get_cron_url(), $logs
        );
    }

    function get_queue_dir()
    {
        $dir = CronUploaderConfig::get_dir();
        return join_path($dir, self::QUEUE_DIR);
    }

    function get_uploaded_dir()
    {
        $dir = CronUploaderConfig::get_dir();
        return join_path($dir, self::UPLOADED_DIR);
    }

    function get_failed_dir()
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
        global $database;

        if ($key!=CronUploaderConfig::get_key()) {
            throw new SCoreException("Cron upload key incorrect");
        }
        $user_id = CronUploaderConfig::get_user();
        if(empty($user_id)) {
            throw new SCoreException("Cron upload user not set");
        }
        $user = User::by_id($user_id);
        if ($user == null) {
            throw new SCoreException("No user found for cron upload user $user_id");
        }

        send_event(new UserLoginEvent($user));
        $this->log_message(SCORE_LOG_INFO, "Logged in as user {$user->name}");

        $lockfile = fopen($this->get_lock_file(), "w");
        if (!flock($lockfile, LOCK_EX | LOCK_NB)) {
            throw new SCoreException("Cron upload process is already running");
        }

        try {
            //set_time_limit(0);

            // Gets amount of imgs to upload
            if ($upload_count == null) {
                $upload_count = CronUploaderConfig::get_count();
            }

            $output_subdir = date('Ymd-His', time());
            $image_queue = $this->generate_image_queue($upload_count);


            // Throw exception if there's nothing in the queue
            if (count($image_queue) == 0) {
                $this->log_message(SCORE_LOG_WARNING, "Your queue is empty so nothing could be uploaded.");
                $this->handle_log();
                return false;
            }

            // Randomize Images
            //shuffle($this->image_queue);

            $merged = 0;
            $added = 0;
            $failed = 0;

            // Upload the file(s)
            for ($i = 0; $i < $upload_count && sizeof($image_queue) > 0; $i++) {
                $img = array_pop($image_queue);

                try {
                    $database->beginTransaction();
                    $this->log_message(SCORE_LOG_INFO, "Adding file: {$img[0]} - tags: {$img[2]}");
                    $result = $this->add_image($img[0], $img[1], $img[2]);
                    $database->commit();
                    $this->move_uploaded($img[0], $img[1], $output_subdir, false);
                    if ($result->merged) {
                        $merged++;
                    } else {
                        $added++;
                    }
                } catch (Exception $e) {
                    try {
                        $database->rollback();
                    } catch (Exception $e) {
                    }

                    $failed++;
                    $this->move_uploaded($img[0], $img[1], $output_subdir, true);
                    $this->log_message(SCORE_LOG_ERROR, "(" . gettype($e) . ") " . $e->getMessage());
                    $this->log_message(SCORE_LOG_ERROR, $e->getTraceAsString());


                }
            }


            $this->log_message(SCORE_LOG_INFO, "Items added: $added");
            $this->log_message(SCORE_LOG_INFO, "Items merged: $merged");
            $this->log_message(SCORE_LOG_INFO, "Items failed: $failed");


            // Display upload log
            $this->handle_log();

            return true;
        } finally {
            flock($lockfile, LOCK_UN);
            fclose($lockfile);
        }

    }

    private function move_uploaded(string $path, string $filename, string $output_subdir, bool $corrupt = false)
    {
        $relativeDir = dirname(substr($path, strlen(CronUploaderConfig::get_dir()) + 7));

        if($relativeDir==".") {
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
        $metadata ['tags'] = $tagArray; // doesn't work when not logged in here, handled below
        $metadata ['source'] = null;
        $event = new DataUploadEvent($tmpname, $metadata);
        send_event($event);

        // Generate info message
        $infomsg = ""; // Will contain info message
        if ($event->image_id == -1) {
            throw new Exception("File type not recognised. Filename: {$filename}");
        } elseif ($event->merged === true) {
            $infomsg = "Image merged. ID: {$event->image_id} - Filename: {$filename}";
        } else {
            $infomsg = "Image uploaded. ID: {$event->image_id} - Filename: {$filename}";
        }
        $this->log_message(SCORE_LOG_INFO, $infomsg);

        // Set tags
        $img = Image::by_id($event->image_id);
        $img->set_tags(array_merge($tagArray, $img->get_tag_array()));

        return $event;
    }

    private const PARTIAL_DOWNLOAD_EXTENSIONS = ['crdownload','part'];

    private function is_skippable_file(string $path) {
        $info = pathinfo($path);

        if(in_array(strtolower($info['extension']),self::PARTIAL_DOWNLOAD_EXTENSIONS)) {
            return true;
        }

        return false;
    }

    private function generate_image_queue(string $root_dir, ?int $limit = null): array
    {
        $base = $this->get_queue_dir();
        $output = [];

        if (!is_dir($base)) {
            $this->log_message(SCORE_LOG_WARNING, "Image Queue Directory could not be found at \"$base\".");
            return [];
        }

        $ite = new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS);
        foreach (new RecursiveIteratorIterator($ite) as $fullpath => $cur) {
            if (!is_link($fullpath) && !is_dir($fullpath) && !$this->is_skippable_file($fullpath)) {
                $pathinfo = pathinfo($fullpath);

                $relativePath = substr($fullpath, strlen($base));
                $tags = path_to_tags($relativePath);

                $img = [
                    0 => $fullpath,
                    1 => $pathinfo ["basename"],
                    2 => $tags
                ];
                $output[] = $img;
                if (!empty($limit) && count($output) >= $limit) {
                    break;
                }
            }
        }
        return $output;
    }


    private function log_message(int $severity, string $message): void
    {
        global $database;

        log_msg(self::NAME, $severity, $message);

        $time = "[" . date('Y-m-d H:i:s') . "]";
        $this->output_buffer[] = $time . " " . $message;

        $log_path = $this->get_log_file();

        file_put_contents($log_path, $time . " " . $message);
    }

    private function get_log_file(): string
    {
        return join_path(CronUploaderConfig::get_dir(), "uploads.log");
    }

    /**
     * This is run at the end to display & save the log.
     */
    private function handle_log()
    {
        global $page;

        // Display message
        $page->set_mode(PageMode::DATA);
        $page->set_type("text/plain");
        $page->set_data(implode("\r\n", $this->output_buffer));
    }
}

