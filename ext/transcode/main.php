<?php

declare(strict_types=1);

namespace Shimmie2;

/*
* This is used by the image transcoding code when there is an error while transcoding
*/
final class ImageTranscodeException extends SCoreException
{
}


final class TranscodeImage extends Extension
{
    public const KEY = "transcode";
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

    public static function get_mapping_name(string $mime): string
    {
        $mime = str_replace(".", "_", $mime);
        $mime = str_replace("/", "_", $mime);
        return "transcode_upload_".$mime;
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
        if ($this->get_version() < 1) {
            $old_extensions = [];
            foreach (array_values(self::INPUT_MIMES) as $mime) {
                $old_extensions = array_merge($old_extensions, FileExtension::get_all_for_mime($mime));
            }

            foreach ($old_extensions as $old_extension) {
                $oldValue = self::get_mapping($old_extension);
                if (!empty($oldValue)) {
                    $from_mime = MimeType::get_for_extension($old_extension);
                    if (empty($from_mime)) {
                        continue;
                    }

                    $to_mime = MimeType::get_for_extension($oldValue);
                    if (empty($to_mime)) {
                        continue;
                    }

                    self::set_mapping($from_mime, $to_mime);
                    self::set_mapping($old_extension, null);
                }
            }

            $this->set_version(1);
        }
    }


    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        global $user, $config;

        if ($user->can(ImagePermission::EDIT_FILES) && $event->context !== "report") {
            $engine = $config->get_string(TranscodeImageConfig::ENGINE);
            if ($this->can_convert_mime($engine, $event->image->get_mime())) {
                $options = self::get_supported_output_mimes($engine, $event->image->get_mime());
                $event->add_part($this->theme->get_transcode_html($event->image, $options));
            }
        }
    }

    public function onDataUpload(DataUploadEvent $event): void
    {
        global $config;

        // this onDataUpload happens earlier (or could happen earlier) than handle_pixel.onDataUpload
        // it mutates the image such that the incorrect mime type is not checked (checking against
        // the post-transcode mime type instead). This is to  give user feedback on what the mime type
        // was before potential transcoding (the original) at the time of upload, and that it failed if not allowed.
        // does it break bulk image importing? ZIP? SVG? there are a few flows that are untested!
        if ($config->get_bool(TranscodeImageConfig::MIME_CHECK_ENABLED) == true) {
            $allowed_mimes = $config->get_array(TranscodeImageConfig::ALLOWED_MIME_STRINGS);
            if (!MimeType::matches_array($event->mime, $allowed_mimes)) {
                throw new UploadException("MIME type not supported: " . $event->mime);
            }
        }

        if ($config->get_bool(TranscodeImageConfig::UPLOAD) == true) {
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
                    Log::error("transcode", "Error while performing upload transcode: ".$e->getMessage());
                    // We don't want to interfere with the upload process,
                    // so if something goes wrong the untranscoded image jsut continues
                }
            }
        }
    }
    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user;

        if ($event->page_matches("transcode/{image_id}", method: "POST", permission: ImagePermission::EDIT_FILES)) {
            $image_id = $event->get_iarg('image_id');
            $image_obj = Image::by_id_ex($image_id);
            $this->transcode_and_replace_image($image_obj, $event->req_POST('transcode_mime'));
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/".$image_id));
        }
    }

    public function onImageDownloading(ImageDownloadingEvent $event): void
    {
        global $config, $user;

        if ($config->get_bool(TranscodeImageConfig::GET_ENABLED) &&
            isset($event->params['transcode']) &&
            $user->can(ImagePermission::EDIT_FILES) &&
            $this->can_convert_mime($config->get_string(TranscodeImageConfig::ENGINE), $event->image->get_mime())) {
            $target_mime = $event->params['transcode'];

            if (!MimeType::is_mime($target_mime)) {
                $target_mime = MimeType::get_for_extension($target_mime);
            }
            if (empty($target_mime)) {
                throw new ImageTranscodeException("Unable to determine output MIME for ".$event->params['transcode']);
            }

            MediaEngine::is_output_supported($config->get_string(TranscodeImageConfig::ENGINE), $target_mime);

            $source_mime = $event->image->get_mime();

            if ($source_mime !== $target_mime) {
                $tmp_filename = $this->transcode_image($event->path, $source_mime, $target_mime);

                if ($event->file_modified === true && $event->path !== $event->image->get_image_filename()) {
                    // This means that we're dealing with a temp file that will need cleaned up
                    $event->path->unlink();
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

        if ($user->can(ImagePermission::EDIT_FILES)) {
            $engine = $config->get_string(TranscodeImageConfig::ENGINE);
            $event->add_action(self::ACTION_BULK_TRANSCODE, "Transcode Image", null, "", $this->theme->get_transcode_picker_html(self::get_supported_output_mimes($engine)));
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
                if ($user->can(ImagePermission::EDIT_FILES)) {
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
                            Log::error("transcode", "Error while bulk transcode on item {$image->id} to $mime: ".$e->getMessage());
                        }
                    }
                    if ($size_difference > 0) {
                        $page->flash("Transcoded $total items, reduced size by ".human_filesize($size_difference));
                    } elseif ($size_difference < 0) {
                        $page->flash("Transcoded $total items, increased size by ".human_filesize(negative_int($size_difference)));
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
    public static function get_supported_output_mimes(string $engine, ?string $omit_mime = null): array
    {
        $output = [];

        foreach (self::OUTPUT_MIMES as $key => $value) {
            if ($value == "") {
                $output[$key] = $value;
                continue;
            }
            if (MediaEngine::is_output_supported($engine, $value)
                && (empty($omit_mime) || $omit_mime !== $value)) {
                $output[$key] = $value;
            }
        }
        return $output;
    }

    private function transcode_and_replace_image(Image $image, string $target_mime): void
    {
        $original_file = Filesystem::warehouse_path(Image::IMAGE_DIR, $image->hash);
        $tmp_filename = $this->transcode_image($original_file, $image->get_mime(), $target_mime);
        send_event(new ImageReplaceEvent($image, $tmp_filename));
    }

    private function transcode_image(Path $source_name, string $source_mime, string $target_mime): Path
    {
        global $config;

        if ($source_mime == $target_mime) {
            throw new ImageTranscodeException("Source and target MIMEs are the same: ".$source_mime);
        }

        $engine = $config->get_string(TranscodeImageConfig::ENGINE);

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

    private function transcode_image_gd(Path $source_name, string $source_mime, string $target_mime): Path
    {
        global $config;

        $q = $config->get_int(TranscodeImageConfig::QUALITY);

        $tmp_name = shm_tempnam("transcode");

        $image = \Safe\imagecreatefromstring($source_name->get_contents());
        try {
            $result = false;
            switch ($target_mime) {
                case MimeType::WEBP:
                    $result = imagewebp($image, $tmp_name->str(), $q);
                    break;
                case MimeType::PNG:
                    $result = imagepng($image, $tmp_name->str(), 9);
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
                        $background_color = Media::hex_color_allocate($new_image, $config->get_string(TranscodeImageConfig::ALPHA_COLOR));
                        if (imagefilledrectangle($new_image, 0, 0, $width, $height, $background_color) === false) {
                            throw new ImageTranscodeException("Could not fill background color");
                        }
                        if (imagecopy($new_image, $image, 0, 0, 0, 0, $width, $height) === false) {
                            throw new ImageTranscodeException("Could not copy source image to new image");
                        }
                        $result = imagejpeg($new_image, $tmp_name->str(), $q);
                    } finally {
                        imagedestroy($new_image);
                    }
                    break;
            }
        } finally {
            imagedestroy($image);
        }
        if ($result === false) {
            throw new ImageTranscodeException("Error while transcoding ".$source_name->str()." to ".$target_mime);
        }
        return $tmp_name;
    }

    private function transcode_image_convert(Path $source_name, string $source_mime, string $target_mime): Path
    {
        global $config;

        $q = $config->get_int(TranscodeImageConfig::QUALITY);
        $convert = $config->get_string(MediaConfig::CONVERT_PATH);

        if (empty($convert)) {
            throw new ImageTranscodeException("ImageMagick path not configured");
        }
        $ext = Media::determine_ext($target_mime);

        $args = " -background ";

        if (Media::supports_alpha($target_mime)) {
            $args .= "none ";
        } else {
            $args .= "\"".$config->get_string(TranscodeImageConfig::ALPHA_COLOR)."\" ";
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

        $command = new CommandBuilder(executable: $convert);
        $command->add_escaped_arg("$source_type:{$source_name->str()}");
        $command->add_flag($args);
        $command->add_escaped_arg("$ext:{$tmp_name->str()}");
        $command->execute();

        return $tmp_name;
    }
}
