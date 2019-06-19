<?php

/*
 * Name: Cron Uploader
 * Authors: YaoiFox <admin@yaoifox.com>, Matthew Barbour <matthew@darkholme.net>
 * Link: http://www.yaoifox.com/
 * License: GPLv2
 * Description: Uploads images automatically using Cron Jobs
 * Documentation: Installation guide: activate this extension and navigate to www.yoursite.com/cron_upload
 */

class CronUploader extends Extension
{
    // TODO: Checkbox option to only allow localhost + a list of additional IP adresses that can be set in /cron_upload
    // TODO: Change logging to MySQL + display log at /cron_upload
    // TODO: Move stuff to theme.php

    const QUEUE_DIR = "queue";
    const UPLOADED_DIR = "uploaded";
    const FAILED_DIR = "failed_to_upload";

    const CONFIG_KEY = "cron_uploader_key";
    const CONFIG_COUNT = "cron_uploader_count";
    const CONFIG_DIR = "cron_uploader_dir";

    /**
     * Lists all log events this session
     * @var string
     */
    private $upload_info = "";

    /**
     * Lists all files & info required to upload.
     * @var array
     */
    private $image_queue = [];

    /**
     * Cron Uploader root directory
     * @var string
     */
    private $root_dir = "";

    /**
     * Key used to identify uploader
     * @var string
     */
    private $upload_key = "";

    /**
     * Checks if the cron upload page has been accessed
     * and initializes the upload.
     */
    public function onPageRequest(PageRequestEvent $event)
    {
        global $config, $user;

        if ($event->page_matches("cron_upload")) {
            $this->upload_key = $config->get_string(self::CONFIG_KEY, "");

            // If the key is in the url, upload
            if ($this->upload_key != "" && $event->get_arg(0) == $this->upload_key) {
                // log in as admin
                $this->set_dir();

                $lockfile = fopen($this->root_dir . "/.lock", "w");
                try {
                    if (!flock($lockfile, LOCK_EX | LOCK_NB)) {
                        throw new Exception("Cron upload process is already running");
                    }
                    $this->process_upload(); // Start upload
                } finally {
                    flock($lockfile, LOCK_UN);
                    fclose($lockfile);
                }
            } elseif ($user->is_admin()) {
                $this->set_dir();
                $this->display_documentation();
            }
        }
    }

    private function display_documentation()
    {
        global $page;
        $this->set_dir(); // Determines path to cron_uploader_dir


        $queue_dir = $this->root_dir . "/" . self::QUEUE_DIR;
        $uploaded_dir = $this->root_dir . "/" . self::UPLOADED_DIR;
        $failed_dir = $this->root_dir . "/" . self::FAILED_DIR;

        $queue_dirinfo = $this->scan_dir($queue_dir);
        $uploaded_dirinfo = $this->scan_dir($uploaded_dir);
        $failed_dirinfo = $this->scan_dir($failed_dir);

        $cron_url = make_http(make_link("/cron_upload/" . $this->upload_key));
        $cron_cmd = "curl --silent $cron_url";
        $log_path = $this->root_dir . "/uploads.log";

        $info_html = "<b>Information</b>
			<br>
			<table style='width:470px;'>
			<tr>
			<td style='width:90px;'><b>Directory</b></td>
			<td style='width:90px;'><b>Files</b></td>
			<td style='width:90px;'><b>Size (MB)</b></td>
			<td style='width:200px;'><b>Directory Path</b></td>
			</tr><tr>
			<td>Queue</td>
			<td>{$queue_dirinfo['total_files']}</td>
			<td>{$queue_dirinfo['total_mb']}</td>
			<td><input type='text' style='width:150px;' value='$queue_dir'></td>
			</tr><tr>
			<td>Uploaded</td>
			<td>{$uploaded_dirinfo['total_files']}</td>
			<td>{$uploaded_dirinfo['total_mb']}</td>
			<td><input type='text' style='width:150px;' value='$uploaded_dir'></td>
			</tr><tr>
			<td>Failed</td>
			<td>{$failed_dirinfo['total_files']}</td>
			<td>{$failed_dirinfo['total_mb']}</td>
			<td><input type='text' style='width:150px;' value='$failed_dir'></td>
			</tr></table>
	
			<br>Cron Command: <input type='text' size='60' value='$cron_cmd'><br>
			Create a cron job with the command above.<br/>
				Read the documentation if you're not sure what to do.<br>";

        $install_html = "
			This cron uploader is fairly easy to use but has to be configured first.
			<br />1. Install & activate this plugin.
			<br />
			<br />2. Upload your images you want to be uploaded to the queue directory using your FTP client. 
			<br />(<b>$queue_dir</b>)
			<br />This also supports directory names to be used as tags.
			<br />	
			<br />3. Go to the Board Config to the Cron Uploader menu and copy the Cron Command.
			<br />(<b>$cron_cmd</b>)
			<br />
			<br />4. Create a cron job or something else that can open a url on specified times.
			<br />If you're not sure how to do this, you can give the command to your web host and you can ask them to create the cron job for you.
			<br />When you create the cron job, you choose when to upload new images.
			<br />
			<br />5. When the cron command is set up, your image queue will upload x file(s) at the specified times.
			<br />You can see any uploads or failed uploads in the log file. (<b>$log_path</b>)
			<br />Your uploaded images will be moved to the 'uploaded' directory, it's recommended that you remove everything out of this directory from time to time.
			<br />(<b>$uploaded_dir</b>)
			<br />		
			<br />Whenever the url in that cron job command is opened, a new file will upload from the queue.
			<br />So when you want to manually upload an image, all you have to do is open the link once.
			<br />This link can be found under 'Cron Command' in the board config, just remove the 'wget ' part and only the url remains.
			<br />(<b>$cron_url</b>)";

        $page->set_title("Cron Uploader");
        $page->set_heading("Cron Uploader");

        $block = new Block("Cron Uploader", $info_html, "main", 10);
        $block_install = new Block("Installation Guide", $install_html, "main", 20);
        $page->add_block($block);
        $page->add_block($block_install);
    }

    public function onInitExt(InitExtEvent $event)
    {
        global $config;
        // Set default values
        $config->set_default_int(self::CONFIG_COUNT, 1);
        $this->set_dir();

        $this->upload_key = $config->get_string(self::CONFIG_KEY, "");
        if (empty($this->upload_key)) {
            $this->upload_key = $this->generate_key();

            $config->set_string(self::CONFIG_KEY, $this->upload_key);
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $this->set_dir();

        $cron_url = make_http(make_link("/cron_upload/" . $this->upload_key));
        $cron_cmd = "curl --silent $cron_url";
        $documentation_link = make_http(make_link("cron_upload"));

        $sb = new SetupBlock("Cron Uploader");
        $sb->add_label("<b>Settings</b><br>");
        $sb->add_int_option(self::CONFIG_COUNT, "How many to upload each time");
        $sb->add_text_option(self::CONFIG_DIR, "<br>Set Cron Uploader root directory<br>");

        $sb->add_label("<br>Cron Command: <input type='text' size='60' readonly='readonly' value='" . html_escape($cron_cmd) . "'><br>
		Create a cron job with the command above.<br/>
		<a href='$documentation_link'>Read the documentation</a> if you're not sure what to do.");

        $event->panel->add_block($sb);
    }

    /*
     * Generates a unique key for the website to prevent unauthorized access.
     */
    private function generate_key()
    {
        $length = 20;
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters [rand(0, strlen($characters) - 1)];
        }

        return $randomString;
    }

    /*
     * Set the directory for the image queue. If no directory was given, set it to the default directory.
     */
    private function set_dir()
    {
        global $config;
        // Determine directory (none = default)

        $dir = $config->get_string(self::CONFIG_DIR, "");

        // Sets new default dir if not in config yet/anymore
        if ($dir == "") {
            $dir = data_path("cron_uploader");
            $config->set_string(self::CONFIG_DIR, $dir);
        }

        // Make the directory if it doesn't exist yet
        if (!is_dir($dir . "/" . self::QUEUE_DIR . "/")) {
            mkdir($dir . "/" . self::QUEUE_DIR . "/", 0775, true);
        }
        if (!is_dir($dir . "/" . self::UPLOADED_DIR . "/")) {
            mkdir($dir . "/" . self::UPLOADED_DIR . "/", 0775, true);
        }
        if (!is_dir($dir . "/" . self::FAILED_DIR . "/")) {
            mkdir($dir . "/" . self::FAILED_DIR . "/", 0775, true);
        }

        $this->root_dir = $dir;
        return $dir;
    }

    /**
     * Returns amount of files & total size of dir.
     */
    public function scan_dir(string $path): array
    {
        $bytestotal = 0;
        $nbfiles = 0;

        $ite = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
        foreach (new RecursiveIteratorIterator($ite) as $filename => $cur) {
            $filesize = $cur->getSize();
            $bytestotal += $filesize;
            $nbfiles++;
        }

        $size_mb = $bytestotal / 1048576; // to mb
        $size_mb = number_format($size_mb, 2, '.', '');
        return ['total_files' => $nbfiles, 'total_mb' => $size_mb];
    }

    /**
     * Uploads the image & handles everything
     */
    public function process_upload(int $upload_count = 0): bool
    {
        global $config, $database;

        //set_time_limit(0);


        $output_subdir = date('Ymd-His', time()) . "/";
        $this->generate_image_queue();

        // Gets amount of imgs to upload
        if ($upload_count == 0) {
            $upload_count = $config->get_int(self::CONFIG_COUNT, 1);
        }

        // Throw exception if there's nothing in the queue
        if (count($this->image_queue) == 0) {
            $this->add_upload_info("Your queue is empty so nothing could be uploaded.");
            $this->handle_log();
            return false;
        }

        // Randomize Images
        //shuffle($this->image_queue);

        $merged = 0;
        $added = 0;
        $failed = 0;

        // Upload the file(s)
        for ($i = 0; $i < $upload_count && sizeof($this->image_queue) > 0; $i++) {
            $img = array_pop($this->image_queue);

            try {
                $database->beginTransaction();
                $result = $this->add_image($img[0], $img[1], $img[2]);
                $database->commit();
                $this->move_uploaded($img[0], $img[1], $output_subdir, false);
                if ($result == null) {
                    $merged++;
                } else {
                    $added++;
                }
            } catch (Exception $e) {
                $failed++;
                $this->move_uploaded($img[0], $img[1], $output_subdir, true);
                $msgNumber = $this->add_upload_info("(" . gettype($e) . ") " . $e->getMessage());
                $msgNumber = $this->add_upload_info($e->getTraceAsString());

                try {
                    $database->rollback();
                } catch (Exception $e) {
                }
            }
        }


        $msgNumber = $this->add_upload_info("Items added: $added");
        $msgNumber = $this->add_upload_info("Items merged: $merged");
        $msgNumber = $this->add_upload_info("Items failed: $failed");


        // Display & save upload log
        $this->handle_log();

        return true;

    }

    private function move_uploaded($path, $filename, $output_subdir, $corrupt = false)
    {
        global $config;

        // Create
        $newDir = $this->root_dir;

        $relativeDir = dirname(substr($path, strlen($this->root_dir) + 7));

        // Determine which dir to move to
        if ($corrupt) {
            // Move to corrupt dir
            $newDir .= "/" . self::FAILED_DIR . "/" . $output_subdir . $relativeDir;
            $info = "ERROR: Image was not uploaded.";
        } else {
            $newDir .= "/" . self::UPLOADED_DIR . "/" . $output_subdir . $relativeDir;
            $info = "Image successfully uploaded. ";
        }
        $newDir = str_replace("//", "/", $newDir . "/");

        if (!is_dir($newDir)) {
            mkdir($newDir, 0775, true);
        }

        // move file to correct dir
        rename($path, $newDir . $filename);

        $this->add_upload_info($info . "Image \"$filename\" moved from queue to \"$newDir\".");
    }

    /**
     * Generate the necessary DataUploadEvent for a given image and tags.
     */
    private function add_image(string $tmpname, string $filename, string $tags)
    {
        assert(file_exists($tmpname));

        $pathinfo = pathinfo($filename);
        $metadata = [];
        $metadata ['filename'] = $pathinfo ['basename'];
        if (array_key_exists('extension', $pathinfo)) {
            $metadata ['extension'] = $pathinfo ['extension'];
        }
        $metadata ['tags'] = Tag::explode($tags);
        $metadata ['source'] = null;
        $event = new DataUploadEvent($tmpname, $metadata);
        send_event($event);

        // Generate info message
        $infomsg = ""; // Will contain info message
        if ($event->image_id == -1) {
            throw new Exception("File type not recognised. Filename: {$filename}");
        } elseif ($event->image_id == null) {
            $infomsg = "Image merged. Filename: {$filename}";
        } else {
            $infomsg = "Image uploaded. ID: {$event->image_id} - Filename: {$filename}";
        }
        $msgNumber = $this->add_upload_info($infomsg);
        return $event->image_id;
    }

    private function generate_image_queue(): void
    {
        $base = $this->root_dir . "/" . self::QUEUE_DIR;

        if (!is_dir($base)) {
            $this->add_upload_info("Image Queue Directory could not be found at \"$base\".");
            return;
        }

        $ite = new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS);
        foreach (new RecursiveIteratorIterator($ite) as $fullpath => $cur) {
            if (!is_link($fullpath) && !is_dir($fullpath)) {
                $pathinfo = pathinfo($fullpath);

                $relativePath = substr($fullpath, strlen($base));
                $tags = path_to_tags($relativePath);

                $img = [
                    0 => $fullpath,
                    1 => $pathinfo ["basename"],
                    2 => $tags
                ];
                array_push($this->image_queue, $img);
            }
        }
    }

    /**
     * Adds a message to the info being published at the end
     */
    private function add_upload_info(string $text, int $addon = 0): int
    {
        $info = $this->upload_info;
        $time = "[" . date('Y-m-d H:i:s') . "]";

        // If addon function is not used
        if ($addon == 0) {
            $this->upload_info .= "$time $text\r\n";

            // Returns the number of the current line
            $currentLine = substr_count($this->upload_info, "\n") - 1;
            return $currentLine;
        }

        // else if addon function is used, select the line & modify it
        $lines = substr($info, "\n"); // Seperate the string to array in lines
        $lines[$addon] = "$lines[$addon] $text"; // Add the content to the line
        $this->upload_info = implode("\n", $lines); // Put string back together & update

        return $addon; // Return line number
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
        $page->set_data($this->upload_info);

        // Save log
        $log_path = $this->root_dir . "/uploads.log";

        if (file_exists($log_path)) {
            $prev_content = file_get_contents($log_path);
        } else {
            $prev_content = "";
        }

        $content = $prev_content . "\r\n" . $this->upload_info;
        file_put_contents($log_path, $content);
    }
}
