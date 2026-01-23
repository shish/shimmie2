<?php

declare(strict_types=1);

namespace Shimmie2;

/*
* This is used by the image transcoding code when there is an error while transcoding
*/
final class VideoTranscodeException extends SCoreException
{
}


/** @extends Extension<TranscodeVideoTheme> */
final class TranscodeVideo extends Extension
{
    public const KEY = "transcode_video";

    public const ACTION_BULK_TRANSCODE = "bulk_transcode_video";

    public const FORMAT_NAMES = [
        VideoContainer::MKV->value => "matroska",
        VideoContainer::WEBM->value => "webm",
        VideoContainer::OGG->value => "ogg",
        VideoContainer::MP4->value => "mp4",
    ];

    #[EventListener]
    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        if ($event->image->video === true && $event->image->video_codec !== null && Ctx::$user->can(ImagePermission::EDIT_FILES)) {
            $options = self::get_output_options(VideoContainer::fromMimeType($event->image->get_mime()), $event->image->video_codec);
            if (!empty($options) && sizeof($options) > 1) {
                $event->add_part($this->theme->get_transcode_html($event->image, $options));
            }
        }
    }

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("transcode_video/{image_id}", method: "POST", permission: ImagePermission::EDIT_FILES)) {
            $image_id = $event->get_iarg('image_id');
            $image_obj = Image::by_id_ex($image_id);
            $this->transcode_and_replace_video($image_obj, $event->POST->req('transcode_format'));
            Ctx::$page->set_redirect(make_link("post/view/".$image_id));
        }
    }

    #[EventListener]
    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event): void
    {
        $event->add_action(
            "transcode-video",
            "Transcode Video",
            null,
            "",
            $this->theme->get_transcode_picker_html(self::get_output_options()),
            permission: ImagePermission::EDIT_FILES,
        );
    }

    #[EventListener]
    public function onBulkAction(BulkActionEvent $event): void
    {
        switch ($event->action) {
            case "transcode-video":
                if (!isset($event->params['transcode_format'])) {
                    return;
                }
                if (Ctx::$user->can(ImagePermission::EDIT_FILES)) {
                    $format = $event->params['transcode_format'];
                    $total = 0;
                    foreach ($event->items as $image) {
                        try {
                            // If a subsequent transcode fails, the database needs to have everything about the previous
                            // transcodes recorded already, otherwise the image entries will be stuck pointing to
                            // missing image files
                            $transcoded = Ctx::$database->with_savepoint(function () use ($image, $format) {
                                return $this->transcode_and_replace_video($image, $format);
                            });
                            if ($transcoded) {
                                $total++;
                            }
                        } catch (\Exception $e) {
                            Log::error("transcode_video", "Error while bulk transcode on item {$image->id} to $format: ".$e->getMessage());
                        }
                    }
                    $event->log_action("Transcoded $total items");
                }
                break;
        }
    }

    /**
     * @return array<string, ?VideoContainer>
     */
    private static function get_output_options(?VideoContainer $starting_container = null, ?VideoCodec $starting_codec = null): array
    {
        $output = ["" => null];

        foreach (VideoContainer::cases() as $container) {
            if ($starting_container === $container) {
                continue;
            }
            if (!empty($starting_codec) &&
                !VideoContainer::is_video_codec_supported($container, $starting_codec)) {
                continue;
            }
            // FIXME: VideoContainer happens to be a mime type when in string form,
            // but that's an implementation detail and might not be the case in the future.
            $description = MimeMap::get_name_for_mime(new MimeType($container->value));
            $output[$description] = $container;
        }
        return $output;
    }

    private function transcode_and_replace_video(Image $image, string $target_mime): bool
    {
        if ($image->get_mime()->base === $target_mime) {
            return false;
        }

        if ($image->video === null || ($image->video === true && empty($image->video_codec))) {
            // If image predates the media system, or the video codec support, run a media check
            send_event(new MediaCheckPropertiesEvent($image));
            $image->save_to_db();
        }
        if (empty($image->video_codec)) {
            throw new VideoTranscodeException("Cannot transcode item $image->id because its video codec is not known");
        }

        $original_file = Filesystem::warehouse_path(Image::IMAGE_DIR, $image->hash);
        $tmp_filename = shm_tempnam("transcode_video");
        $tmp_filename = $this->transcode_video($original_file, $image->video_codec, $target_mime, $tmp_filename);
        send_event(new ImageReplaceEvent($image, $tmp_filename));
        return true;
    }

    private function transcode_video(Path $source_file, ?VideoCodec $source_video_codec, string $target_mime, Path $target_file): Path
    {
        if (is_null($source_video_codec)) {
            throw new VideoTranscodeException("Cannot transcode item because it's video codec is not known");
        }

        if (!VideoContainer::is_video_codec_supported(VideoContainer::from($target_mime), $source_video_codec)) {
            throw new VideoTranscodeException("Cannot transcode item to $target_mime because it does not support the video codec {$source_video_codec->value}");
        }

        $command = new CommandBuilder(Ctx::$config->get(VideoFileHandlerConfig::FFMPEG_PATH));
        $command->add_args("-y", "-hide_banner", "-loglevel", "quiet");
        $command->add_args("-i", $source_file->str()); // input file

        // TODO: Implement transcoding the codec as well. This will be much more advanced than just picking a container.
        $command->add_args("-c", "copy");
        $command->add_args("-map", "0"); // Copies all streams

        $command->add_args("-f", self::FORMAT_NAMES[$target_mime]);
        $command->add_args($target_file->str());

        $command->execute();

        return $target_file;
    }
}
