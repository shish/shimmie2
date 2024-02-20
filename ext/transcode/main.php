<?php

declare(strict_types=1);

namespace Shimmie2;

require_once "config.php";
/*
* This is used by the image transcoding code when there is an error while transcoding
*/
class ImageTranscodeException extends SCoreException
{
}


class TranscodeImage extends Extension
{
    /** @var TranscodeImageTheme */
    protected Themelet $theme;

    public const ACTION_BULK_TRANSCODE = "bulk_transcode";

    public const INPUT_MIMES = [
        "BMP" => MimeType::BMP,
        "GIF" => MimeType::GIF,
        "ICO" => MimeType::ICO,
        "JPG" => MimeType::JPEG,
        "PNG" => MimeType::PNG,
        "PPM" => MimeType::PPM,
        "PSD" => MimeType::PSD,
        "TIFF" => MimeType::TIFF,
        "WEBP" => MimeType::WEBP,
        "TGA" => MimeType::TGA
    ];

    public const OUTPUT_MIMES = [
        "" => "",
        "JPEG (lossy)" => MimeType::JPEG,
        "PNG (lossless)" => MimeType::PNG,
        "WEBP (lossy)" => MimeType::WEBP,
        "WEBP (lossless)" => MimeType::WEBP_LOSSLESS,
    ];

    /**
     * Needs to be after upload, but before the processing extensions
     */
    public function get_priority(): int
    {
        return 45;
    }


    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_bool(TranscodeConfig::ENABLED, true);
        $config->set_default_bool(TranscodeConfig::GET_ENABLED, false);
        $config->set_default_bool(TranscodeConfig::UPLOAD, false);
        $config->set_default_string(TranscodeConfig::ENGINE, MediaEngine::GD);
        $config->set_default_int(TranscodeConfig::QUALITY, 80);
        $config->set_default_string(TranscodeConfig::ALPHA_COLOR, Media::DEFAULT_ALPHA_CONVERSION_COLOR);

        foreach (array_values(self::INPUT_MIMES) as $mime) {
            $config->set_default_string(self::get_mapping_name($mime), "");
        }
    }

    private static function get_mapping_name(string $mime): string
    {
        $mime = str_replace(".", "_", $mime);
        $mime = str_replace("/", "_", $mime);
        return TranscodeConfig::UPLOAD_PREFIX.$mime;
    }

    private static function get_mapping(string $mime): ?string
    {
        global $config;
        return $config->get_string(self::get_mapping_name($mime));
    }
    private static function set_mapping(string $from_mime, ?string $to_mime): void
    {
        global $config;
        $config->set_string(self::get_mapping_name($from_mime), $to_mime);
    }

    /**
     * @return string[]
     */
    public static function get_enabled_mimes(): array
    {
        $output = [];
        foreach (array_values(self::INPUT_MIMES) as $mime) {
            $value = self::get_mapping($mime);
            if (!empty($value)) {
                $output[] = $mime;
            }
        }
        return $output;
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        if ($this->get_version(TranscodeConfig::VERSION) < 1) {
            $old_extensions = [];
            foreach (array_values(self::INPUT_MIMES) as $mime) {
                $old_extensions = array_merge($old_extensions, FileExtension::get_all_for_mime($mime));
            }

            foreach ($old_extensions as $old_extension) {
                $oldValue = $this->get_mapping($old_extension);
                if (!empty($oldValue)) {
                    $from_mime = MimeType::get_for_extension($old_extension);
                    if (empty($from_mime)) {
                        continue;
                    }

                    $to_mime = MimeType::get_for_extension($oldValue);
                    if (empty($to_mime)) {
                        continue;
                    }

                    $this->set_mapping($from_mime, $to_mime);
                    $this->set_mapping($old_extension, null);
                }
            }

            $this->set_version(TranscodeConfig::VERSION, 1);
        }
    }


    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        global $user, $config;

        if ($user->can(Permissions::EDIT_FILES) && $event->context != "report") {
            $engine = $config->get_string(TranscodeConfig::ENGINE);
            if ($this->can_convert_mime($engine, $event->image->get_mime())) {
                $options = $this->get_supported_output_mimes($engine, $event->image->get_mime());
                $event->add_part($this->theme->get_transcode_html($event->image, $options));
            }
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        global $config;

        $engine = $config->get_string(TranscodeConfig::ENGINE);


        $sb = $event->panel->create_new_block("Image Transcode");
        $sb->start_table();
        $sb->add_bool_option(TranscodeConfig::ENABLED, "Allow transcoding images", true);
        $sb->add_bool_option(TranscodeConfig::GET_ENABLED, "Enable GET args", true);
        $sb->add_bool_option(TranscodeConfig::UPLOAD, "Transcode on upload", true);
        $sb->add_choice_option(TranscodeConfig::ENGINE, MediaEngine::IMAGE_ENGINES, "Engine", true);
        foreach (self::INPUT_MIMES as $display => $mime) {
            if (MediaEngine::is_input_supported($engine, $mime)) {
                $outputs = $this->get_supported_output_mimes($engine, $mime);
                $sb->add_choice_option(self::get_mapping_name($mime), $outputs, "$display", true);
            }
        }
        $sb->add_int_option(TranscodeConfig::QUALITY, "Lossy Format Quality", true);
        $sb->add_color_option(TranscodeConfig::ALPHA_COLOR, "Alpha Conversion Color", true);
        $sb->end_table();
    }

    public function onDataUpload(DataUploadEvent $event): void
    {
        global $config;

        // this onDataUpload happens earlier (or could happen earlier) than handle_pixel.onDataUpload
        // it mutates the image such that the incorrect mime type is not checked (checking against
        // the post-transcode mime type instead). This is to  give user feedback on what the mime type
        // was before potential transcoding (the original) at the time of upload, and that it failed if not allowed.
        // does it break bulk image importing? ZIP? SVG? there are a few flows that are untested!
        if ($config->get_bool(UploadConfig::MIME_CHECK_ENABLED) == true) {
            $allowed_mimes = $config->get_array(UploadConfig::ALLOWED_MIME_STRINGS);
            if (!MimeType::matches_array($event->mime, $allowed_mimes)) {
                throw new UploadException("MIME type not supported: " . $event->mime);
            }
        }

        if ($config->get_bool(TranscodeConfig::UPLOAD) == true) {
            if ($event->mime === MimeType::GIF && MimeType::is_animated_gif($event->tmpname)) {
                return;
            }

            if (in_array($event->mime, array_values(self::INPUT_MIMES))) {
                $target_mime = self::get_mapping($event->mime);
                if (empty($target_mime)) {
                    return;
                }
                try {
                    $new_image = $this->transcode_image($event->tmpname, $event->mime, $target_mime);
                    $event->set_tmpname($new_image, $target_mime);
                } catch (\Exception $e) {
                    log_error("transcode", "Error while performing upload transcode: ".$e->getMessage());
                    // We don't want to interfere with the upload process,
                    // so if something goes wrong the untranscoded image jsut continues
                }
            }
        }
    }
    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user;

        if ($event->page_matches("transcode/{image_id}", method: "POST", permission: Permissions::EDIT_FILES)) {
            $image_id = $event->get_iarg('image_id');
            $image_obj = Image::by_id_ex($image_id);
            try {
                $this->transcode_and_replace_image($image_obj, $event->req_POST('transcode_mime'));
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link("post/view/".$image_id));
            } catch (ImageTranscodeException $e) {
                $this->theme->display_transcode_error($page, "Error Transcoding", $e->getMessage());
            }
        }
    }

    public function onImageDownloading(ImageDownloadingEvent $event): void
    {
        global $config, $user;

        if ($config->get_bool(TranscodeConfig::GET_ENABLED) &&
            isset($event->params['transcode']) &&
            $user->can(Permissions::EDIT_FILES) &&
            $this->can_convert_mime($config->get_string(TranscodeConfig::ENGINE), $event->image->get_mime())) {
            $target_mime = $event->params['transcode'];

            if (!MimeType::is_mime($target_mime)) {
                $target_mime = MimeType::get_for_extension($target_mime);
            }
            if (empty($target_mime)) {
                throw new ImageTranscodeException("Unable to determine output MIME for ".$event->params['transcode']);
            }

            MediaEngine::is_output_supported($config->get_string(TranscodeConfig::ENGINE), $target_mime);

            $source_mime = $event->image->get_mime();

            if ($source_mime != $target_mime) {
                $tmp_filename = $this->transcode_image($event->path, $source_mime, $target_mime);

                if ($event->file_modified === true && $event->path != $event->image->get_image_filename()) {
                    // This means that we're dealing with a temp file that will need cleaned up
                    unlink($event->path);
                }

                $event->path = $tmp_filename;
                $event->mime = $target_mime;
                $event->file_modified = true;
            }
        }
    }

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event): void
    {
        global $user, $config;

        $engine = $config->get_string(TranscodeConfig::ENGINE);

        if ($user->can(Permissions::EDIT_FILES)) {
            $event->add_action(self::ACTION_BULK_TRANSCODE, "Transcode Image", null, "", $this->theme->get_transcode_picker_html($this->get_supported_output_mimes($engine)));
        }
    }

    public function onBulkAction(BulkActionEvent $event): void
    {
        global $user, $database, $page;

        switch ($event->action) {
            case self::ACTION_BULK_TRANSCODE:
                if (!isset($event->params['transcode_mime'])) {
                    return;
                }
                if ($user->can(Permissions::EDIT_FILES)) {
                    $mime = $event->params['transcode_mime'];
                    $total = 0;
                    $size_difference = 0;
                    foreach ($event->items as $image) {
                        try {
                            $before_size = $image->filesize;
                            $database->with_savepoint(function () use ($image, $mime) {
                                $this->transcode_and_replace_image($image, $mime);
                            });
                            // If a subsequent transcode fails, the database needs to have everything about the previous
                            // transcodes recorded already, otherwise the image entries will be stuck pointing to
                            // missing image files
                            $total++;
                            $size_difference += ($before_size - $image->filesize);
                        } catch (\Exception $e) {
                            log_error("transcode", "Error while bulk transcode on item {$image->id} to $mime: ".$e->getMessage());
                        }
                    }
                    if ($size_difference > 0) {
                        $page->flash("Transcoded $total items, reduced size by ".human_filesize($size_difference));
                    } elseif ($size_difference < 0) {
                        $page->flash("Transcoded $total items, increased size by ".human_filesize(-1 * $size_difference));
                    } else {
                        $page->flash("Transcoded $total items, no size difference");
                    }
                }
                break;
        }
    }


    private function can_convert_mime(string $engine, string $mime): bool
    {
        return MediaEngine::is_input_supported($engine, $mime);
    }

    /**
     * @return array<string, string>
     */
    private function get_supported_output_mimes(string $engine, ?string $omit_mime = null): array
    {
        $output = [];

        foreach (self::OUTPUT_MIMES as $key => $value) {
            if ($value == "") {
                $output[$key] = $value;
                continue;
            }
            if (MediaEngine::is_output_supported($engine, $value)
                && (empty($omit_mime) || $omit_mime != $value)) {
                $output[$key] = $value;
            }
        }
        return $output;
    }



    private function transcode_and_replace_image(Image $image, string $target_mime): void
    {
        $original_file = warehouse_path(Image::IMAGE_DIR, $image->hash);
        $tmp_filename = $this->transcode_image($original_file, $image->get_mime(), $target_mime);
        send_event(new ImageReplaceEvent($image, $tmp_filename));
    }


    private function transcode_image(string $source_name, string $source_mime, string $target_mime): string
    {
        global $config;

        if ($source_mime == $target_mime) {
            throw new ImageTranscodeException("Source and target MIMEs are the same: ".$source_mime);
        }

        $engine = $config->get_string(TranscodeConfig::ENGINE);

        if (!$this->can_convert_mime($engine, $source_mime)) {
            throw new ImageTranscodeException("Engine $engine does not support input MIME $source_mime");
        }
        if (!MediaEngine::is_output_supported($engine, $target_mime)) {
            throw new ImageTranscodeException("Engine $engine does not support output MIME $target_mime");
        }

        switch ($engine) {
            case "gd":
                return $this->transcode_image_gd($source_name, $source_mime, $target_mime);
            case "convert":
                return $this->transcode_image_convert($source_name, $source_mime, $target_mime);
            default:
                throw new ImageTranscodeException("No engine specified");
        }
    }

    private function transcode_image_gd(string $source_name, string $source_mime, string $target_mime): string
    {
        global $config;

        $q = $config->get_int(TranscodeConfig::QUALITY);

        $tmp_name = shm_tempnam("transcode");

        $image = false_throws(imagecreatefromstring(\Safe\file_get_contents($source_name)));
        try {
            $result = false;
            switch ($target_mime) {
                case MimeType::WEBP:
                    $result = imagewebp($image, $tmp_name, $q);
                    break;
                case MimeType::PNG:
                    $result = imagepng($image, $tmp_name, 9);
                    break;
                case MimeType::JPEG:
                    // In case of alpha channels
                    $width = imagesx($image);
                    $height = imagesy($image);
                    $new_image = imagecreatetruecolor($width, $height);
                    if ($new_image === false) {
                        throw new ImageTranscodeException("Could not create image with dimensions $width x $height");
                    }
                    try {
                        $background_color = Media::hex_color_allocate($new_image, $config->get_string(TranscodeConfig::ALPHA_COLOR));
                        if (imagefilledrectangle($new_image, 0, 0, $width, $height, $background_color) === false) {
                            throw new ImageTranscodeException("Could not fill background color");
                        }
                        if (imagecopy($new_image, $image, 0, 0, 0, 0, $width, $height) === false) {
                            throw new ImageTranscodeException("Could not copy source image to new image");
                        }
                        $result = imagejpeg($new_image, $tmp_name, $q);
                    } finally {
                        imagedestroy($new_image);
                    }
                    break;
            }
        } finally {
            imagedestroy($image);
        }
        if ($result === false) {
            throw new ImageTranscodeException("Error while transcoding ".$source_name." to ".$target_mime);
        }
        return $tmp_name;
    }

    private function transcode_image_convert(string $source_name, string $source_mime, string $target_mime): string
    {
        global $config;

        $q = $config->get_int(TranscodeConfig::QUALITY);
        $convert = $config->get_string(MediaConfig::CONVERT_PATH);

        if (empty($convert)) {
            throw new ImageTranscodeException("ImageMagick path not configured");
        }
        $ext = Media::determine_ext($target_mime);

        $args = " -background ";

        if (Media::supports_alpha($target_mime)) {
            $args .= "none ";
        } else {
            $args .= "\"".$config->get_string(TranscodeConfig::ALPHA_COLOR)."\" ";
        }
        $args .= " -flatten ";

        switch ($target_mime) {
            case MimeType::PNG:
                $args .= ' -define png:compression-level=9';
                break;
            case MimeType::WEBP_LOSSLESS:
                $args .= ' -define webp:lossless=true -quality 100 ';
                break;
            default:
                $args .= ' -quality '.$q;
                break;
        }

        $tmp_name = shm_tempnam("transcode");

        $source_type = FileExtension::get_for_mime($source_mime);

        $format = '"%s" %s:"%s" %s %s:"%s" 2>&1';
        $cmd = sprintf($format, $convert, $source_type, $source_name, $args, $ext, $tmp_name);

        $cmd = str_replace("\"convert\"", "convert", $cmd); // quotes are only needed if the path to convert contains a space; some other times, quotes break things, see github bug #27
        exec($cmd, $output, $ret);

        log_debug('transcode', "Transcoding with command `$cmd`, returns $ret");

        if ($ret !== 0) {
            throw new ImageTranscodeException("Transcoding failed with command ".$cmd.", returning ".implode("\r\n", $output));
        }

        return $tmp_name;
    }
}
