<?php

require_once "config.php";
require_once "events.php";
require_once "media_engine.php";

/*
* This is used by the media code when there is an error
*/
class MediaException extends SCoreException
{
}

class Media extends Extension
{
    const WEBP_LOSSY = "webp-lossy";
    const WEBP_LOSSLESS = "webp-lossless";

    const IMAGE_MEDIA_ENGINES = [
        "GD" => MediaEngine::GD,
        "ImageMagick" => MediaEngine::IMAGICK,
    ];

    const LOSSLESS_FORMATS = [
        self::WEBP_LOSSLESS,
        "png",
        "psd",
        "bmp",
        "ico",
        "cur",
        "ani",
        "gif"

    ];

    const ALPHA_FORMATS = [
        self::WEBP_LOSSLESS,
        self::WEBP_LOSSY,
        "webp",
        "png",
    ];

    const FORMAT_ALIASES = [
        "tif" => "tiff",
        "jpeg" => "jpg",
    ];


    //RIFF####WEBPVP8?..............ANIM
    private const WEBP_ANIMATION_HEADER =
        [0x52, 0x49, 0x46, 0x46, null, null, null, null, 0x57, 0x45, 0x42, 0x50, 0x56, 0x50, 0x38, null,
            null, null, null, null, null, null, null, null, null, null, null, null, null, null, 0x41, 0x4E, 0x49, 0x4D];

    //RIFF####WEBPVP8L
    private const WEBP_LOSSLESS_HEADER =
        [0x52, 0x49, 0x46, 0x46, null, null, null, null, 0x57, 0x45, 0x42, 0x50, 0x56, 0x50, 0x38, 0x4C];


    public static function imagick_available(): bool
    {
        return extension_loaded("imagick");
    }

    /**
     * High priority just so that it can be early in the settings
     */
    public function get_priority(): int
    {
        return 30;
    }

    public function onInitExt(InitExtEvent $event)
    {
        global $config;
        $config->set_default_string(MediaConfig::FFPROBE_PATH, 'ffprobe');
        $config->set_default_int(MediaConfig::MEM_LIMIT, parse_shorthand_int('8MB'));
        $config->set_default_string(MediaConfig::FFMPEG_PATH, 'ffmpeg');
        $config->set_default_string(MediaConfig::CONVERT_PATH, 'convert');


        if ($config->get_int(MediaConfig::VERSION) < 2) {
            $this->setup();
        }
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $user;

        if ($event->page_matches("media_rescan/") && $user->can(Permissions::RESCAN_MEDIA) && isset($_POST['image_id'])) {
            $image = Image::by_id(int_escape($_POST['image_id']));

            $this->update_image_media_properties($image->hash, $image->ext);

            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/$image->id"));
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = new SetupBlock("Media Engines");

//        if (self::imagick_available()) {
//            try {
//                $image = new Imagick(realpath('tests/favicon.png'));
//                $image->clear();
//                $sb->add_label("ImageMagick detected");
//            } catch (ImagickException $e) {
//                $sb->add_label("<b style='color:red'>ImageMagick not detected</b>");
//            }
//        } else {
        $sb->start_table();
        $sb->add_table_header("Commands");

        $sb->add_text_option(MediaConfig::CONVERT_PATH, "convert", true);
//        }

        $sb->add_text_option(MediaConfig::FFMPEG_PATH, "<br/>ffmpeg", true);
        $sb->add_text_option(MediaConfig::FFPROBE_PATH, "<br/>ffprobe", true);

        $sb->add_shorthand_int_option(MediaConfig::MEM_LIMIT, "<br />Mem limit: ", true);
        $sb->end_table();

        $event->panel->add_block($sb);
    }

    public function onAdminBuilding(AdminBuildingEvent $event)
    {
        global $database;
        $types = $database->get_all("SELECT ext, count(*) count FROM images group by ext");

        $this->theme->display_form($types);
    }

    public function onAdminAction(AdminActionEvent $event)
    {
        $action = $event->action;
        if (method_exists($this, $action)) {
            $event->redirect = $this->$action();
        }
    }


    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event)
    {
        global $user;
        if ($user->can(Permissions::DELETE_IMAGE)) {
            $event->add_part($this->theme->get_buttons_html($event->image->id));
        }
    }


    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event)
    {
        global $user;

        if ($user->can(Permissions::RESCAN_MEDIA)) {
            $event->add_action("bulk_media_rescan", "Scan Media Properties");
        }
    }

    public function onBulkAction(BulkActionEvent $event)
    {
        global $user;

        switch ($event->action) {
            case "bulk_media_rescan":
                if ($user->can(Permissions::RESCAN_MEDIA)) {
                    $total = 0;
                    $failed = 0;
                    foreach ($event->items as $image) {
                        try {
                            $this->update_image_media_properties($image->hash, $image->ext);
                            $total++;
                        } catch (MediaException $e) {
                            $failed++;
                        }
                    }
                    flash_message("Scanned media properties for $total items, failed for $failed");
                }
                break;
        }
    }

    public function onCommand(CommandEvent $event)
    {
        if ($event->cmd == "help") {
            print "\tmedia-rescan <id>\n";
            print "\t\trefresh metadata for a given post\n\n";
        }
        if ($event->cmd == "media-rescan") {
            $uid = $event->args[0];
            $image = Image::by_id_or_hash($uid);
            if ($image) {
                $this->update_image_media_properties($image->hash, $image->ext);
            } else {
                print("No post with ID '$uid'\n");
            }
        }
    }

    /**
     * @param MediaResizeEvent $event
     * @throws MediaException
     * @throws InsufficientMemoryException
     */
    public function onMediaResize(MediaResizeEvent $event)
    {
        switch ($event->engine) {
            case MediaEngine::GD:
                $info = getimagesize($event->input_path);
                if ($info === false) {
                    throw new MediaException("getimagesize failed for " . $event->input_path);
                }

                self::image_resize_gd(
                    $event->input_path,
                    $info,
                    $event->target_width,
                    $event->target_height,
                    $event->output_path,
                    $event->target_format,
                    $event->ignore_aspect_ratio,
                    $event->target_quality,
                    $event->allow_upscale
                );

                break;
            case MediaEngine::IMAGICK:
//                if (self::imagick_available()) {
//                } else {
                self::image_resize_convert(
                    $event->input_path,
                    $event->input_type,
                    $event->target_width,
                    $event->target_height,
                    $event->output_path,
                    $event->target_format,
                    $event->ignore_aspect_ratio,
                    $event->target_quality,
                    $event->minimize,
                    $event->allow_upscale
                );
                //}
                break;
            default:
                throw new MediaException("Engine not supported for resize: " . $event->engine);
        }

        // TODO: Get output optimization tools working better
//        if ($config->get_bool("thumb_optim", false)) {
//            exec("jpegoptim $outname", $output, $ret);
//        }
    }


    const CONTENT_SEARCH_TERM_REGEX = "/^content[=|:]((video)|(audio)|(image)|(unknown))$/i";


    public function onSearchTermParse(SearchTermParseEvent $event)
    {
        global $database;

        $matches = [];
        if (preg_match(self::CONTENT_SEARCH_TERM_REGEX, $event->term, $matches)) {
            $field = $matches[1];
            if($field==="unknown") {
                $event->add_querylet(new Querylet($database->scoreql_to_sql("video IS NULL OR audio IS NULL OR image IS NULL")));
            } else {
                $event->add_querylet(new Querylet($database->scoreql_to_sql("$field = SCORE_BOOL_Y")));
            }
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event)
    {
        if ($event->key===HelpPages::SEARCH) {
            $block = new Block();
            $block->header = "Media";
            $block->body = $this->theme->get_help_html();
            $event->add_block($block);
        }
    }


    public function onTagTermParse(TagTermParseEvent $event)
    {
        $matches = [];

        if (preg_match(self::CONTENT_SEARCH_TERM_REGEX, strtolower($event->term), $matches) && $event->parse) {
            // Nothing to save, just helping filter out reserved tags
        }

        if (!empty($matches)) {
            $event->metatag = true;
        }
    }

    private function media_rescan(): bool
    {
        $ext = "";
        if (array_key_exists("media_rescan_type", $_POST)) {
            $ext = $_POST["media_rescan_type"];
        }

        $results = $this->get_images($ext);

        foreach ($results as $result) {
            $this->update_image_media_properties($result["hash"], $result["ext"]);
        }
        return true;
    }

    public static function update_image_media_properties(string $hash, string $ext)
    {
        global $database;

        $path = warehouse_path(Image::IMAGE_DIR, $hash);
        $mcpe = new MediaCheckPropertiesEvent($path, $ext);
        send_event($mcpe);


        $database->execute(
            "UPDATE images SET 
                  lossless = :lossless, video = :video, audio = :audio,image = :image, 
                  height = :height, width = :width, 
                  length = :length WHERE hash = :hash",
            [
                "hash" => $hash,
                "width" => $mcpe->width ?? 0,
                "height" => $mcpe->height ?? 0,
                "lossless" => $database->scoresql_value_prepare($mcpe->lossless),
                "video" => $database->scoresql_value_prepare($mcpe->video),
                "image" => $database->scoresql_value_prepare($mcpe->image),
                "audio" => $database->scoresql_value_prepare($mcpe->audio),
                "length" => $mcpe->length
            ]
        );
    }

    public function get_images(String $ext = null)
    {
        global $database;

        $query = "SELECT id, hash, ext FROM images  ";
        $args = [];
        if (!empty($ext)) {
            $query .= " WHERE ext = :ext";
            $args["ext"] = $ext;
        }
        return $database->get_all($query, $args);
    }

    /**
     * Check Memory usage limits
     *
     * Old check:   $memory_use = (filesize($image_filename)*2) + ($width*$height*4) + (4*1024*1024);
     * New check:   $memory_use = $width * $height * ($bits_per_channel) * channels * 2.5
     *
     * It didn't make sense to compute the memory usage based on the NEW size for the image. ($width*$height*4)
     * We need to consider the size that we are GOING TO instead.
     *
     * The factor of 2.5 is simply a rough guideline.
     * http://stackoverflow.com/questions/527532/reasonable-php-memory-limit-for-image-resize
     *
     * @param array $info The output of getimagesize() for the source file in question.
     * @return int The number of bytes an image resize operation is estimated to use.
     */
    public static function calc_memory_use(array $info): int
    {
        if (isset($info['bits']) && isset($info['channels'])) {
            $memory_use = ($info[0] * $info[1] * ($info['bits'] / 8) * $info['channels'] * 2.5) / 1024;
        } else {
            // If we don't have bits and channel info from the image then assume default values
            // of 8 bits per color and 4 channels (R,G,B,A) -- ie: regular 24-bit color
            $memory_use = ($info[0] * $info[1] * 1 * 4 * 2.5) / 1024;
        }
        return (int)$memory_use;
    }


    /**
     * Creates a thumbnail using ffmpeg.
     *
     * @param $hash
     * @return bool true if successful, false if not.
     * @throws MediaException
     */
    public static function create_thumbnail_ffmpeg($hash): bool
    {
        global $config;

        $ffmpeg = $config->get_string(MediaConfig::FFMPEG_PATH);
        if ($ffmpeg == null || $ffmpeg == "") {
            throw new MediaException("ffmpeg command configured");
        }

        $inname = warehouse_path(Image::IMAGE_DIR, $hash);
        $outname = warehouse_path(Image::THUMBNAIL_DIR, $hash);

        $orig_size = self::video_size($inname);
        $scaled_size = get_thumbnail_size($orig_size[0], $orig_size[1], true);

        $codec = "mjpeg";
        $quality = $config->get_int(ImageConfig::THUMB_QUALITY);
        if ($config->get_string(ImageConfig::THUMB_TYPE) == "webp") {
            $codec = "libwebp";
        } else {
            // mjpeg quality ranges from 2-31, with 2 being the best quality.
            $quality = floor(31 - (31 * ($quality / 100)));
            if ($quality < 2) {
                $quality = 2;
            }
        }

        $args = [
            escapeshellarg($ffmpeg),
            "-y", "-i", escapeshellarg($inname),
            "-vf", "thumbnail,scale={$scaled_size[0]}:{$scaled_size[1]}",
            "-f", "image2",
            "-vframes", "1",
            "-c:v", $codec,
            "-q:v", $quality,
            escapeshellarg($outname),
        ];

        $cmd = escapeshellcmd(implode(" ", $args));

        exec($cmd, $output, $ret);

        if ((int)$ret == (int)0) {
            log_debug('Media', "Generating thumbnail with command `$cmd`, returns $ret");
            return true;
        } else {
            log_error('Media', "Generating thumbnail with command `$cmd`, returns $ret");
            return false;
        }
    }


    public static function get_ffprobe_data($filename): array
    {
        global $config;

        $ffprobe = $config->get_string(MediaConfig::FFPROBE_PATH);
        if ($ffprobe == null || $ffprobe == "") {
            throw new MediaException("ffprobe command configured");
        }

        $args = [
            escapeshellarg($ffprobe),
            "-print_format", "json",
            "-v", "quiet",
            "-show_format",
            "-show_streams",
            escapeshellarg($filename),
        ];

        $cmd = escapeshellcmd(implode(" ", $args));

        exec($cmd, $output, $ret);

        if ((int)$ret == (int)0) {
            log_debug('Media', "Getting media data `$cmd`, returns $ret");
            $output = implode($output);
            $data = json_decode($output, true);

            return $data;
        } else {
            log_error('Media', "Getting media data `$cmd`, returns $ret");
            return [];
        }
    }

    public static function determine_ext(String $format): String
    {
        $format = self::normalize_format($format);
        switch ($format) {
            case self::WEBP_LOSSLESS:
            case self::WEBP_LOSSY:
                return "webp";
            default:
                return $format;
        }
    }

//    private static function image_save_imagick(Imagick $image, string $path, string $format, int $output_quality = 80, bool $minimize)
//    {
//        switch ($format) {
//            case "png":
//                $result = $image->setOption('png:compression-level', 9);
//                if ($result !== true) {
//                    throw new GraphicsException("Could not set png compression option");
//                }
//                break;
//            case Graphics::WEBP_LOSSLESS:
//                $result = $image->setOption('webp:lossless', true);
//                if ($result !== true) {
//                    throw new GraphicsException("Could not set lossless webp option");
//                }
//                break;
//            default:
//                $result = $image->setImageCompressionQuality($output_quality);
//                if ($result !== true) {
//                    throw new GraphicsException("Could not set compression quality for $path to $output_quality");
//                }
//                break;
//        }
//
//        if (self::supports_alpha($format)) {
//            $result = $image->setImageBackgroundColor(new \ImagickPixel('transparent'));
//        } else {
//            $result = $image->setImageBackgroundColor(new \ImagickPixel('black'));
//        }
//        if ($result !== true) {
//            throw new GraphicsException("Could not set background color");
//        }
//
//
//        if ($minimize) {
//            $profiles = $image->getImageProfiles("icc", true);
//            $result = $image->stripImage();
//            if ($result !== true) {
//                throw new GraphicsException("Could not strip information from image");
//            }
//            if (!empty($profiles)) {
//                $image->profileImage("icc", $profiles['icc']);
//            }
//        }
//
//        $ext = self::determine_ext($format);
//
//        $result = $image->writeImage($ext . ":" . $path);
//        if ($result !== true) {
//            throw new GraphicsException("Could not write image to $path");
//        }
//    }

//    public static function image_resize_imagick(
//        String $input_path,
//        String $input_type,
//        int $new_width,
//        int $new_height,
//        string $output_filename,
//        string $output_type = null,
//        bool $ignore_aspect_ratio = false,
//        int $output_quality = 80,
//        bool $minimize = false,
//        bool $allow_upscale = true
//    ): void
//    {
//        global $config;
//
//        if (!empty($input_type)) {
//            $input_type = self::determine_ext($input_type);
//        }
//
//        try {
//            $image = new Imagick($input_type . ":" . $input_path);
//            try {
//                $result = $image->flattenImages();
//                if ($result !== true) {
//                    throw new GraphicsException("Could not flatten image $input_path");
//                }
//
//                $height = $image->getImageHeight();
//                $width = $image->getImageWidth();
//                if (!$allow_upscale &&
//                    ($new_width > $width || $new_height > $height)) {
//                    $new_height = $height;
//                    $new_width = $width;
//                }
//
//                $result = $image->resizeImage($new_width, $new_width, Imagick::FILTER_LANCZOS, 0, !$ignore_aspect_ratio);
//                if ($result !== true) {
//                    throw new GraphicsException("Could not perform image resize on $input_path");
//                }
//
//
//                if (empty($output_type)) {
//                    $output_type = $input_type;
//                }
//
//                self::image_save_imagick($image, $output_filename, $output_type, $output_quality);
//
//            } finally {
//                $image->destroy();
//            }
//        } catch (ImagickException $e) {
//            throw new GraphicsException("Error while resizing with Imagick: " . $e->getMessage(), $e->getCode(), $e);
//        }
//    }

    public static function is_lossless(string $filename, string $format)
    {
        if (in_array($format, self::LOSSLESS_FORMATS)) {
            return true;
        }
        switch ($format) {
            case "webp":
                return self::is_lossless_webp($filename);
                break;
        }
        return false;
    }

    public static function image_resize_convert(
        String $input_path,
        String $input_type,
        int $new_width,
        int $new_height,
        string $output_filename,
        string $output_type = null,
        bool $ignore_aspect_ratio = false,
        int $output_quality = 80,
        bool $minimize = false,
        bool $allow_upscale = true
    ): void {
        global $config;

        $convert = $config->get_string(MediaConfig::CONVERT_PATH);

        if (empty($convert)) {
            throw new MediaException("convert command not configured");
        }

        if (empty($output_type)) {
            $output_type = $input_type;
        }

        if ($output_type=="webp" && self::is_lossless($input_path, $input_type)) {
            $output_type = self::WEBP_LOSSLESS;
        }

        $bg = "black";
        if (self::supports_alpha($output_type)) {
            $bg = "none";
        }
        if (!empty($input_type)) {
            $input_type = $input_type . ":";
        }


        $resize_args = "";
        if (!$allow_upscale) {
            $resize_args .= "\>";
        }
        if ($ignore_aspect_ratio) {
            $resize_args .= "\!";
        }

        $args = "";
        switch ($output_type) {
            case Media::WEBP_LOSSLESS:
                $args .= '-define webp:lossless=true';
                break;
            case "png":
                $args .= '-define png:compression-level=9';
                break;
        }

        if ($minimize) {
            $args .= " -strip -thumbnail";
        } else {
            $args .= " -resize";
        }


        $output_ext = self::determine_ext($output_type);

        $format = '"%s"  %s %ux%u%s -quality %u -background %s %s"%s[0]"  %s:"%s" 2>&1';
        $cmd = sprintf($format, $convert, $args, $new_width, $new_height, $resize_args, $output_quality, $bg, $input_type, $input_path, $output_ext, $output_filename);
        $cmd = str_replace("\"convert\"", "convert", $cmd); // quotes are only needed if the path to convert contains a space; some other times, quotes break things, see github bug #27
        exec($cmd, $output, $ret);
        if ($ret != 0) {
            throw new MediaException("Resizing image with command `$cmd`, returns $ret, outputting " . implode("\r\n", $output));
        } else {
            log_debug('Media', "Generating thumbnail with command `$cmd`, returns $ret");
        }
    }

    /**
     * Performs a resize operation on an image file using GD.
     *
     * @param String $image_filename The source file to be resized.
     * @param array $info The output of getimagesize() for the source file.
     * @param int $new_width
     * @param int $new_height
     * @param string $output_filename
     * @param string|null $output_type If set to null, the output file type will be automatically determined via the $info parameter. Otherwise an exception will be thrown.
     * @param int $output_quality Defaults to 80.
     * @throws MediaException
     * @throws InsufficientMemoryException if the estimated memory usage exceeds the memory limit.
     */
    public static function image_resize_gd(
        String $image_filename,
        array $info,
        int $new_width,
        int $new_height,
        string $output_filename,
        string $output_type = null,
        bool $ignore_aspect_ratio = false,
        int $output_quality = 80,
        bool $allow_upscale = true
    ) {
        $width = $info[0];
        $height = $info[1];

        if ($output_type == null) {
            /* If not specified, output to the same format as the original image */
            switch ($info[2]) {
                case IMAGETYPE_GIF:
                    $output_type = "gif";
                    break;
                case IMAGETYPE_JPEG:
                    $output_type = "jpeg";
                    break;
                case IMAGETYPE_PNG:
                    $output_type = "png";
                    break;
                case IMAGETYPE_WEBP:
                    $output_type = "webp";
                    break;
                case IMAGETYPE_BMP:
                    $output_type = "bmp";
                    break;
                default:
                    throw new MediaException("Failed to save the new image - Unsupported image type.");
            }
        }

        $memory_use = self::calc_memory_use($info);
        $memory_limit = get_memory_limit();
        if ($memory_use > $memory_limit) {
            throw new InsufficientMemoryException("The image is too large to resize given the memory limits. ($memory_use > $memory_limit)");
        }

        if (!$ignore_aspect_ratio) {
            list($new_width, $new_height) = get_scaled_by_aspect_ratio($width, $height, $new_width, $new_height);
        }
        if (!$allow_upscale &&
            ($new_width > $width || $new_height > $height)) {
            $new_height = $height;
            $new_width = $width;
        }

        $image = imagecreatefromstring(file_get_contents($image_filename));
        $image_resized = imagecreatetruecolor($new_width, $new_height);
        try {
            if ($image === false) {
                throw new MediaException("Could not load image: " . $image_filename);
            }
            if ($image_resized === false) {
                throw new MediaException("Could not create output image with dimensions $new_width c $new_height ");
            }

            // Handle transparent images
            switch ($info[2]) {
                case IMAGETYPE_GIF:
                    $transparency = imagecolortransparent($image);
                    $pallet_size = imagecolorstotal($image);

                    // If we have a specific transparent color
                    if ($transparency >= 0 && $transparency < $pallet_size) {
                        // Get the original image's transparent color's RGB values
                        $transparent_color = imagecolorsforindex($image, $transparency);

                        // Allocate the same color in the new image resource
                        $transparency = imagecolorallocate($image_resized, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
                        if ($transparency === false) {
                            throw new MediaException("Unable to allocate transparent color");
                        }

                        // Completely fill the background of the new image with allocated color.
                        if (imagefill($image_resized, 0, 0, $transparency) === false) {
                            throw new MediaException("Unable to fill new image with transparent color");
                        }

                        // Set the background color for new image to transparent
                        imagecolortransparent($image_resized, $transparency);
                    }
                    break;
                case IMAGETYPE_PNG:
                case IMAGETYPE_WEBP:
                    //
                    // More info here:  http://stackoverflow.com/questions/279236/how-do-i-resize-pngs-with-transparency-in-php
                    //
                    if (imagealphablending($image_resized, false) === false) {
                        throw new MediaException("Unable to disable image alpha blending");
                    }
                    if (imagesavealpha($image_resized, true) === false) {
                        throw new MediaException("Unable to enable image save alpha");
                    }
                    $transparent_color = imagecolorallocatealpha($image_resized, 255, 255, 255, 127);
                    if ($transparent_color === false) {
                        throw new MediaException("Unable to allocate transparent color");
                    }
                    if (imagefilledrectangle($image_resized, 0, 0, $new_width, $new_height, $transparent_color) === false) {
                        throw new MediaException("Unable to fill new image with transparent color");
                    }
                    break;
            }

            // Actually resize the image.
            if (imagecopyresampled(
                $image_resized,
                $image,
                0,
                0,
                0,
                0,
                $new_width,
                $new_height,
                $width,
                $height
                ) === false) {
                throw new MediaException("Unable to copy resized image data to new image");
            }

            switch ($output_type) {
                case "bmp":
                    $result = imagebmp($image_resized, $output_filename, true);
                    break;
                case "webp":
                case Media::WEBP_LOSSY:
                    $result = imagewebp($image_resized, $output_filename, $output_quality);
                    break;
                case "jpg":
                case "jpeg":
                    $result = imagejpeg($image_resized, $output_filename, $output_quality);
                    break;
                case "png":
                    $result = imagepng($image_resized, $output_filename, 9);
                    break;
                case "gif":
                    $result = imagegif($image_resized, $output_filename);
                    break;
                default:
                    throw new MediaException("Failed to save the new image - Unsupported image type: $output_type");
            }
            if ($result === false) {
                throw new MediaException("Failed to save the new image, function returned false when saving type: $output_type");
            }
        } finally {
            @imagedestroy($image);
            @imagedestroy($image_resized);
        }
    }

    /**
     * Determines if a file is an animated gif.
     *
     * @param String $image_filename The path of the file to check.
     * @return bool true if the file is an animated gif, false if it is not.
     */
    public static function is_animated_gif(String $image_filename): bool
    {
        $is_anim_gif = 0;
        if (($fh = @fopen($image_filename, 'rb'))) {
            try {
                //check if gif is animated (via http://www.php.net/manual/en/function.imagecreatefromgif.php#104473)
                while (!feof($fh) && $is_anim_gif < 2) {
                    $chunk = fread($fh, 1024 * 100);
                    $is_anim_gif += preg_match_all('#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $chunk, $matches);
                }
            } finally {
                @fclose($fh);
            }
        }
        return ($is_anim_gif == 0);
    }


    private static function compare_file_bytes(String $file_name, array $comparison): bool
    {
        $size = filesize($file_name);
        if ($size < count($comparison)) {
            // Can't match because it's too small
            return false;
        }

        if (($fh = @fopen($file_name, 'rb'))) {
            try {
                $chunk = unpack("C*", fread($fh, count($comparison)));

                for ($i = 0; $i < count($comparison); $i++) {
                    $byte = $comparison[$i];
                    if ($byte == null) {
                        continue;
                    } else {
                        $fileByte = $chunk[$i + 1];
                        if ($fileByte != $byte) {
                            return false;
                        }
                    }
                }
                return true;
            } finally {
                @fclose($fh);
            }
        } else {
            throw new MediaException("Unable to open file for byte check: $file_name");
        }
    }

    public static function is_animated_webp(String $image_filename): bool
    {
        return self::compare_file_bytes($image_filename, self::WEBP_ANIMATION_HEADER);
    }

    public static function is_lossless_webp(String $image_filename): bool
    {
        return self::compare_file_bytes($image_filename, self::WEBP_LOSSLESS_HEADER);
    }

    public static function supports_alpha(string $format)
    {
        return in_array(self::normalize_format($format), self::ALPHA_FORMATS);
    }

    public static function is_input_supported(string $engine, string $format, ?bool $lossless = null): bool
    {
        $format = self::normalize_format($format, $lossless);
        if (!in_array($format, MediaEngine::INPUT_SUPPORT[$engine])) {
            return false;
        }
        return true;
    }

    public static function is_output_supported(string $engine, string $format, ?bool $lossless = false): bool
    {
        $format = self::normalize_format($format, $lossless);
        if (!in_array($format, MediaEngine::OUTPUT_SUPPORT[$engine])) {
            return false;
        }
        return true;
    }

    /**
     * Checks if a format (normally a file extension) is a variant name of another format (ie, jpg and jpeg).
     * If one is found, then the maine name that the Media extension will recognize is returned,
     * otherwise the incoming format is returned.
     *
     * @param $format
     * @return string|null The format name that the media extension will recognize.
     */
    public static function normalize_format(string $format, ?bool $lossless = null): ?string
    {
        if ($format == "webp") {
            if ($lossless === true) {
                $format = Media::WEBP_LOSSLESS;
            } else {
                $format = Media::WEBP_LOSSY;
            }
        }

        if (array_key_exists($format, Media::FORMAT_ALIASES)) {
            return self::FORMAT_ALIASES[$format];
        }
        return $format;
    }


    /**
     * Determines the dimensions of a video file using ffmpeg.
     *
     * @param string $filename
     * @return array [width, height]
     */
    public static function video_size(string $filename): array
    {
        global $config;
        $ffmpeg = $config->get_string(MediaConfig::FFMPEG_PATH);
        $cmd = escapeshellcmd(implode(" ", [
            escapeshellarg($ffmpeg),
            "-y", "-i", escapeshellarg($filename),
            "-vstats"
        ]));
        $output = shell_exec($cmd . " 2>&1");
        // error_log("Getting size with `$cmd`");

        $regex_sizes = "/Video: .* ([0-9]{1,4})x([0-9]{1,4})/";
        if (preg_match($regex_sizes, $output, $regs)) {
            if (preg_match("/displaymatrix: rotation of (90|270).00 degrees/", $output)) {
                $size = [$regs[2], $regs[1]];
            } else {
                $size = [$regs[1], $regs[2]];
            }
        } else {
            $size = [1, 1];
        }
        log_debug('Media', "Getting video size with `$cmd`, returns $output -- $size[0], $size[1]");
        return $size;
    }

    private function setup()
    {
        global $config, $database;
        if ($config->get_int(MediaConfig::VERSION) < 1) {
            $current_value = $config->get_string("thumb_ffmpeg_path");
            if (!empty($current_value)) {
                $config->set_string(MediaConfig::FFMPEG_PATH, $current_value);
            } elseif ($ffmpeg = shell_exec((PHP_OS == 'WINNT' ? 'where' : 'which') . ' ffmpeg')) {
                //ffmpeg exists in PATH, check if it's executable, and if so, default to it instead of static
                if (is_executable(strtok($ffmpeg, PHP_EOL))) {
                    $config->set_default_string(MediaConfig::FFMPEG_PATH, 'ffmpeg');
                }
            }

            if ($ffprobe = shell_exec((PHP_OS == 'WINNT' ? 'where' : 'which') . ' ffprobe')) {
                //ffprobe exists in PATH, check if it's executable, and if so, default to it instead of static
                if (is_executable(strtok($ffprobe, PHP_EOL))) {
                    $config->set_default_string(MediaConfig::FFPROBE_PATH, 'ffprobe');
                }
            }

            $current_value = $config->get_string("thumb_convert_path");
            if (!empty($current_value)) {
                $config->set_string(MediaConfig::CONVERT_PATH, $current_value);
            } elseif ($convert = shell_exec((PHP_OS == 'WINNT' ? 'where' : 'which') . ' convert')) {
                //ffmpeg exists in PATH, check if it's executable, and if so, default to it instead of static
                if (is_executable(strtok($convert, PHP_EOL))) {
                    $config->set_default_string(MediaConfig::CONVERT_PATH, 'convert');
                }
            }

            $current_value = $config->get_int("thumb_mem_limit");
            if (!empty($current_value)) {
                $config->set_int(MediaConfig::MEM_LIMIT, $current_value);
            }

            $config->set_int(MediaConfig::VERSION, 1);
            log_info("media", "extension installed");
        }

        if ($config->get_int(MediaConfig::VERSION) < 2) {
            $database->execute($database->scoreql_to_sql(
                "ALTER TABLE images ADD COLUMN image SCORE_BOOL NULL"
            ));

            switch($database->get_driver_name()) {
                case DatabaseDriver::PGSQL:
                case DatabaseDriver::SQLITE:
                    $database->execute('CREATE INDEX images_image_idx ON images(image) WHERE image IS NOT NULL');
                    break;
                default:
                    $database->execute('CREATE INDEX images_image_idx ON images(image)');
                    break;
            }

            $database->set_timeout(300000); // These updates can take a little bit

            if ($database->transaction === true) {
               $database->commit(); // Each of these commands could hit a lot of data, combining them into one big transaction would not be a good idea.
            }
            log_info("upgrade", "Setting predictable media values for known file types");
            $database->execute($database->scoreql_to_sql("UPDATE images SET image = SCORE_BOOL_N WHERE ext IN ('swf','mp3','ani','flv','mp4','m4v','ogv','webm')"));
            $database->execute($database->scoreql_to_sql("UPDATE images SET image = SCORE_BOOL_Y WHERE ext IN ('jpg','jpeg''ico','cur','png')"));

            $config->set_int(MediaConfig::VERSION, 2);
            log_info("media", "extension at version 2");

            $database->beginTransaction();
        }
    }
}
