<?php

declare(strict_types=1);

namespace Shimmie2;

final class VideoFileHandler extends DataHandlerExtension
{
    public const KEY = "handle_video";
    public const SUPPORTED_MIME = [
        MimeType::ASF,
        MimeType::AVI,
        MimeType::FLASH_VIDEO,
        MimeType::MKV,
        MimeType::MP4_VIDEO,
        MimeType::OGG_VIDEO,
        MimeType::QUICKTIME,
        MimeType::WEBM,
    ];

    protected function media_check_properties(MediaCheckPropertiesEvent $event): void
    {
        $event->image->video = true;
        $event->image->image = false;
        try {
            $data = Media::get_ffprobe_data($event->image->get_image_filename());

            foreach ($data["streams"] as $stream) {
                switch ($stream["codec_type"]) {
                    case "audio":
                        $event->image->audio = true;
                        break;
                    case "video":
                        $event->image->video = true;
                        try {
                            $event->image->video_codec = VideoCodec::from($stream["codec_name"]);
                        } catch (\ValueError $e) {
                            throw new UserError("Unrecognised video codec: {$stream["codec_name"]}");
                        }
                        $event->image->width = max($event->image->width, $stream["width"]);
                        $event->image->height = max($event->image->height, $stream["height"]);
                        break;
                }
            }

            if ($event->image->get_mime()->base === MimeType::MKV &&
                $event->image->video_codec !== null &&
                VideoContainer::is_video_codec_supported(VideoContainer::WEBM, $event->image->video_codec)) {
                // WEBMs are MKVs with the VP9 or VP8 codec
                // For browser-friendliness, we'll just change the mime type
                $event->image->set_mime(MimeType::WEBM);
            }

            $event->image->length = (int)floor(floatval($data["format"]["duration"]) * 1000);
        } catch (MediaException $e) {
            // a post with no metadata is better than no post
        }
    }

    protected function supported_mime(MimeType $mime): bool
    {
        $enabled_formats = Ctx::$config->get(VideoFileHandlerConfig::ENABLED_FORMATS);
        return MimeType::matches_array($mime, $enabled_formats, true);
    }

    protected function create_thumb(Image $image): bool
    {
        return Media::create_thumbnail_ffmpeg($image);
    }

    protected function check_contents(Path $tmpname): bool
    {
        if ($tmpname->exists()) {
            $mime = MimeType::get_for_file($tmpname);

            $enabled_formats = Ctx::$config->get(VideoFileHandlerConfig::ENABLED_FORMATS);
            if (MimeType::matches_array($mime, $enabled_formats)) {
                return true;
            }
        }
        return false;
    }
}
