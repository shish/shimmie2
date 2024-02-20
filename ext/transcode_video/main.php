<?php

declare(strict_types=1);

namespace Shimmie2;

require_once "config.php";
/*
* This is used by the image transcoding code when there is an error while transcoding
*/
class VideoTranscodeException extends SCoreException
{
}


class TranscodeVideo extends Extension
{
    /** @var TranscodeVideoTheme */
    protected Themelet $theme;

    public const ACTION_BULK_TRANSCODE = "bulk_transcode_video";

    public const FORMAT_NAMES = [
      VideoContainers::MKV => "matroska",
      VideoContainers::WEBM => "webm",
        VideoContainers::OGG => "ogg",
        VideoContainers::MP4 => "mp4",
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
        $config->set_default_bool(TranscodeVideoConfig::ENABLED, true);
        $config->set_default_bool(TranscodeVideoConfig::UPLOAD, false);
        $config->set_default_bool(TranscodeVideoConfig::UPLOAD_TO_NATIVE_CONTAINER, false);
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        global $user;

        if ($event->image->video === true && $user->can(Permissions::EDIT_FILES)) {
            $options = self::get_output_options($event->image->get_mime(), $event->image->video_codec);
            if (!empty($options) && sizeof($options) > 1) {
                $event->add_part($this->theme->get_transcode_html($event->image, $options));
            }
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Video Transcode");
        $sb->start_table();
        $sb->add_bool_option(TranscodeVideoConfig::ENABLED, "Allow transcoding images: ", true);
        $sb->add_bool_option(TranscodeVideoConfig::UPLOAD_TO_NATIVE_CONTAINER, "Convert videos using MPEG-4 or WEBM to their native containers:", true);
        $sb->end_table();
    }

    /*
        public function onDataUpload(DataUploadEvent $event): void
        {
            global $config;

            if ($config->get_bool(TranscodeVideoConfig::UPLOAD) == true) {
                $ext = strtolower($event->type);

                $ext = Media::normalize_format($ext);

                if ($event->type=="gif"&&Media::is_animated_gif($event->tmpname)) {
                    return;
                }

                if (in_array($ext, array_values(self::INPUT_FORMATS))) {
                    $target_format = $config->get_string(TranscodeVideoConfig::UPLOAD_PREFIX.$ext);
                    if (empty($target_format)) {
                        return;
                    }
                    try {
                        $new_image = $this->transcode_image($event->tmpname, $ext, $target_format);
                        $event->set_tmpname($new_image, Media::determine_ext($target_format));
                    } catch (Exception $e) {
                        log_error("transcode_video", "Error while performing upload transcode: ".$e->getMessage());
                        // We don't want to interfere with the upload process,
                        // so if something goes wrong the untranscoded image jsut continues
                    }
                }
            }
        }
    */

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user;

        if ($event->page_matches("transcode_video/{image_id}", method: "POST", permission: Permissions::EDIT_FILES)) {
            $image_id = $event->get_iarg('image_id');
            $image_obj = Image::by_id_ex($image_id);
            try {
                $this->transcode_and_replace_video($image_obj, $event->req_POST('transcode_format'));
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link("post/view/".$image_id));
            } catch (VideoTranscodeException $e) {
                $this->theme->display_transcode_error($page, "Error Transcoding", $e->getMessage());
            }
        }
    }

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event): void
    {
        global $user;

        if ($user->can(Permissions::EDIT_FILES)) {
            $event->add_action(
                self::ACTION_BULK_TRANSCODE,
                "Transcode Video",
                null,
                "",
                $this->theme->get_transcode_picker_html(self::get_output_options())
            );
        }
    }

    public function onBulkAction(BulkActionEvent $event): void
    {
        global $user, $database, $page;

        switch ($event->action) {
            case self::ACTION_BULK_TRANSCODE:
                if (!isset($event->params['transcode_format'])) {
                    return;
                }
                if ($user->can(Permissions::EDIT_FILES)) {
                    $format = $event->params['transcode_format'];
                    $total = 0;
                    foreach ($event->items as $image) {
                        try {
                            // If a subsequent transcode fails, the database needs to have everything about the previous
                            // transcodes recorded already, otherwise the image entries will be stuck pointing to
                            // missing image files
                            $transcoded = $database->with_savepoint(function () use ($image, $format) {
                                return $this->transcode_and_replace_video($image, $format);
                            });
                            if ($transcoded) {
                                $total++;
                            }
                        } catch (\Exception $e) {
                            log_error("transcode_video", "Error while bulk transcode on item {$image->id} to $format: ".$e->getMessage());
                        }
                    }
                    $page->flash("Transcoded $total items");
                }
                break;
        }
    }

    /**
     * @return array<string, string>
     */
    private static function get_output_options(?string $starting_container = null, ?string $starting_codec = null): array
    {
        $output = ["" => ""];

        foreach (VideoContainers::ALL as $container) {
            if ($starting_container == $container) {
                continue;
            }
            if (!empty($starting_codec) &&
                !VideoContainers::is_video_codec_supported($container, $starting_codec)) {
                continue;
            }
            $description = MimeMap::get_name_for_mime($container);
            $output[$description] = $container;
        }
        return $output;
    }

    private function transcode_and_replace_video(Image $image, string $target_mime): bool
    {
        if ($image->get_mime() == $target_mime) {
            return false;
        }

        if ($image->video == null || ($image->video === true && empty($image->video_codec))) {
            // If image predates the media system, or the video codec support, run a media check
            send_event(new MediaCheckPropertiesEvent($image));
            $image->save_to_db();
        }
        if (empty($image->video_codec)) {
            throw new VideoTranscodeException("Cannot transcode item $image->id because its video codec is not known");
        }

        $original_file = warehouse_path(Image::IMAGE_DIR, $image->hash);
        $tmp_filename = shm_tempnam("transcode_video");
        $tmp_filename = $this->transcode_video($original_file, $image->video_codec, $target_mime, $tmp_filename);
        send_event(new ImageReplaceEvent($image, $tmp_filename));
        return true;
    }


    private function transcode_video(string $source_file, string $source_video_codec, string $target_mime, string $target_file): string
    {
        global $config;

        if (empty($source_video_codec)) {
            throw new VideoTranscodeException("Cannot transcode item because it's video codec is not known");
        }

        $ffmpeg = $config->get_string(MediaConfig::FFMPEG_PATH);

        if (empty($ffmpeg)) {
            throw new VideoTranscodeException("ffmpeg path not configured");
        }

        $command = new CommandBuilder($ffmpeg);
        $command->add_flag("-y"); // Bypass y/n prompts
        $command->add_flag("-i");
        $command->add_escaped_arg($source_file);

        if (!VideoContainers::is_video_codec_supported($target_mime, $source_video_codec)) {
            throw new VideoTranscodeException("Cannot transcode item to $target_mime because it does not support the video codec $source_video_codec");
        }

        // TODO: Implement transcoding the codec as well. This will be much more advanced than just picking a container.
        $command->add_flag("-c");
        $command->add_flag("copy");

        $command->add_flag("-map"); // Copies all streams
        $command->add_flag("0");

        $command->add_flag("-f");
        $format = self::FORMAT_NAMES[$target_mime];
        $command->add_flag($format);
        $command->add_escaped_arg($target_file);

        $command->execute(true);

        return $target_file;
    }
}
