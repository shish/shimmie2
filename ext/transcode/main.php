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

    public static function get_mapping_name(MimeType $mime): string
    {
        $mime = $mime->base;
        $mime = str_replace(".", "_", $mime);
        $mime = str_replace("/", "_", $mime);
        return "transcode_upload_".$mime;
    }

    private static function get_mapping(MimeType $mime): ?MimeType
    {
        $val = Ctx::$config->get(self::get_mapping_name($mime));
        assert(is_string($val) || $val === null);
        return ($val === null || $val === "") ? null : new MimeType($val);
    }

    /**
     * @return MimeType[]
     */
    public static function get_enabled_mimes(): array
    {
        $output = [];
        foreach (array_values(self::INPUT_MIMES) as $mime) {
            $mime = new MimeType($mime);
            if (!is_null(self::get_mapping($mime))) {
                $output[] = $mime;
            }
        }
        return $output;
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        if ($this->get_version() < 1) {
            // Used to be a realllllly old migration to change the config from eg
            // transcode_upload_bmp=png to transcode_upload_image_bmp=image/png,
            // but it used a load of ancient APIs - if people are upgrading from
            // older versions of Shimmie2, they may need to manually migrate their
            // configuration settings.
            $this->set_version(1);
        }
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        if (Ctx::$user->can(ImagePermission::EDIT_FILES) && $event->context !== "report") {
            $engine = MediaEngine::from(Ctx::$config->get(TranscodeImageConfig::ENGINE));
            if ($this->can_convert_mime($engine, $event->image->get_mime())) {
                $options = self::get_supported_output_mimes($engine, $event->image->get_mime());
                $event->add_part($this->theme->get_transcode_html($event->image, $options));
            }
        }
    }

    public function onDataUpload(DataUploadEvent $event): void
    {
        // this onDataUpload happens earlier (or could happen earlier) than handle_pixel.onDataUpload
        // it mutates the image such that the incorrect mime type is not checked (checking against
        // the post-transcode mime type instead). This is to  give user feedback on what the mime type
        // was before potential transcoding (the original) at the time of upload, and that it failed if not allowed.
        // does it break bulk image importing? ZIP? SVG? there are a few flows that are untested!
        if (Ctx::$config->get(TranscodeImageConfig::MIME_CHECK_ENABLED)) {
            $allowed_mimes = Ctx::$config->get(TranscodeImageConfig::ALLOWED_MIME_STRINGS);
            if (!MimeType::matches_array($event->mime, $allowed_mimes)) {
                throw new UploadException("MIME type not supported: " . $event->mime);
            }
        }

        if (Ctx::$config->get(TranscodeImageConfig::UPLOAD)) {
            if ($event->mime->base === MimeType::GIF && MimeType::is_animated_gif($event->tmpname)) {
                return;
            }

            if (in_array($event->mime->base, array_values(self::INPUT_MIMES))) {
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
        if ($event->page_matches("transcode/{image_id}", method: "POST", permission: ImagePermission::EDIT_FILES)) {
            $image_id = $event->get_iarg('image_id');
            $image_obj = Image::by_id_ex($image_id);
            $this->transcode_and_replace_image($image_obj, new MimeType($event->POST->req('transcode_mime')));
            Ctx::$page->set_redirect(make_link("post/view/".$image_id));
        }
    }

    public function onImageDownloading(ImageDownloadingEvent $event): void
    {
        if (
            Ctx::$config->get(TranscodeImageConfig::GET_ENABLED) &&
            isset($event->params['transcode']) &&
            Ctx::$user->can(ImagePermission::EDIT_FILES) &&
            $this->can_convert_mime(MediaEngine::from(Ctx::$config->get(TranscodeImageConfig::ENGINE)), $event->image->get_mime())
        ) {

            try {
                $target_mime = new MimeType($event->params['transcode']);
            } catch (\InvalidArgumentException $e) {
                $target_mime = MimeType::get_for_extension($event->params['transcode']);
            }

            if (is_null($target_mime)) {
                throw new ImageTranscodeException("Unable to determine output MIME for ".$event->params['transcode']);
            }

            MediaEngine::is_output_supported(MediaEngine::from(Ctx::$config->get(TranscodeImageConfig::ENGINE)), $target_mime);

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
        $engine = MediaEngine::from(Ctx::$config->get(TranscodeImageConfig::ENGINE));
        $event->add_action(
            "transcode-image",
            "Transcode Image",
            null,
            "",
            $this->theme->get_transcode_picker_html(self::get_supported_output_mimes($engine)),
            permission: ImagePermission::EDIT_FILES,
        );
    }

    public function onBulkAction(BulkActionEvent $event): void
    {
        switch ($event->action) {
            case "transcode-image":
                if (!isset($event->params['transcode_mime'])) {
                    return;
                }
                if (Ctx::$user->can(ImagePermission::EDIT_FILES)) {
                    $mime = new MimeType($event->params['transcode_mime']);
                    $total = 0;
                    $size_difference = 0;
                    foreach ($event->items as $image) {
                        try {
                            $before_size = $image->filesize;
                            Ctx::$database->with_savepoint(function () use ($image, $mime) {
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
                        $event->log_action("Transcoded $total items, reduced size by ".human_filesize($size_difference));
                    } elseif ($size_difference < 0) {
                        $event->log_action("Transcoded $total items, increased size by ".human_filesize(negative_int($size_difference)));
                    } else {
                        $event->log_action("Transcoded $total items, no size difference");
                    }
                }
                break;
        }
    }


    private function can_convert_mime(MediaEngine $engine, MimeType $mime): bool
    {
        return MediaEngine::is_input_supported($engine, $mime);
    }

    /**
     * @return array<string, ?MimeType>
     */
    public static function get_supported_output_mimes(MediaEngine $engine, ?MimeType $omit_mime = null): array
    {
        $output = [];

        foreach (self::OUTPUT_MIMES as $name => $mime) {
            if ($mime === "") {
                $output[$name] = null;
                continue;
            }
            $mime = new MimeType($mime);
            if (MediaEngine::is_output_supported($engine, $mime)
                && (is_null($omit_mime) || $omit_mime->base !== $mime->base)) {
                $output[$name] = $mime;
            }
        }
        return $output;
    }

    private function transcode_and_replace_image(Image $image, MimeType $target_mime): void
    {
        $original_file = Filesystem::warehouse_path(Image::IMAGE_DIR, $image->hash);
        $tmp_filename = $this->transcode_image($original_file, $image->get_mime(), $target_mime);
        send_event(new ImageReplaceEvent($image, $tmp_filename));
    }

    private function transcode_image(Path $source_name, MimeType $source_mime, MimeType $target_mime): Path
    {
        if ($source_mime === $target_mime) {
            throw new ImageTranscodeException("Source and target MIMEs are the same: ".$source_mime);
        }

        $engine = MediaEngine::from(Ctx::$config->get(TranscodeImageConfig::ENGINE));

        if (!$this->can_convert_mime($engine, $source_mime)) {
            throw new ImageTranscodeException("Engine {$engine->value} does not support input MIME $source_mime");
        }
        if (!MediaEngine::is_output_supported($engine, $target_mime)) {
            throw new ImageTranscodeException("Engine {$engine->value} does not support output MIME $target_mime");
        }

        return match ($engine) {
            MediaEngine::GD => $this->transcode_image_gd($source_name, $source_mime, $target_mime),
            MediaEngine::IMAGICK => $this->transcode_image_convert($source_name, $source_mime, $target_mime),
            default => throw new ImageTranscodeException("No engine specified"),
        };
    }

    private function transcode_image_gd(Path $source_name, MimeType $source_mime, MimeType $target_mime): Path
    {
        $q = Ctx::$config->get(TranscodeImageConfig::QUALITY);

        $tmp_name = shm_tempnam("transcode");

        $image = \Safe\imagecreatefromstring($source_name->get_contents());
        try {
            $result = false;
            switch ($target_mime->base) {
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
                        $background_color = Media::hex_color_allocate($new_image, Ctx::$config->get(TranscodeImageConfig::ALPHA_COLOR));
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

    private function transcode_image_convert(Path $source_name, MimeType $source_mime, MimeType $target_mime): Path
    {
        $command = new CommandBuilder(Ctx::$config->get(MediaConfig::CONVERT_PATH));

        // load file
        $source_type = FileExtension::get_for_mime($source_mime);
        $command->add_args("$source_type:{$source_name->str()}");

        // flatten with optional solid background color
        $command->add_args(
            "-background",
            Media::supports_alpha($target_mime)
                ? "none"
                : Ctx::$config->get(TranscodeImageConfig::ALPHA_COLOR)
        );
        $command->add_args("-flatten");

        // format-specific compression options
        if ($target_mime->base === MimeType::PNG) {
            $command->add_args("-define", "png:compression-level=9");
        } elseif ($target_mime->base === MimeType::WEBP && $target_mime->parameters === MimeType::LOSSLESS_PARAMETER) {
            $command->add_args("-define", "webp:lossless=true");
            $command->add_args("-quality", "100");
        } else {
            $command->add_args("-quality", (string)Ctx::$config->get(TranscodeImageConfig::QUALITY));
        }

        // write file
        $tmp_name = shm_tempnam("transcode");
        $ext = Media::determine_ext($target_mime);
        $command->add_args("$ext:{$tmp_name->str()}");

        // go
        $command->execute();

        return $tmp_name;
    }
}
