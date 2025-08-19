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
        $video = false;
        $audio = false;
        $width = 0;
        $height = 0;
        $video_codec = null;

        $data = Media::get_ffprobe_data($event->image->get_image_filename());
        foreach ($data["streams"] as $stream) {
            switch ($stream["codec_type"]) {
                case "audio":
                    $audio = true;
                    break;
                case "video":
                    $video = true;
                    $video_codec = VideoCodec::from_or_unknown($stream["codec_name"]);
                    $width = max($event->image->width, $stream["width"]);
                    $height = max($event->image->height, $stream["height"]);
                    break;
            }
        }
        $length = (int)floor(floatval($data["format"]["duration"]) * 1000);

        if (
            $event->image->get_mime()->base === MimeType::MKV &&
            $video_codec !== null &&
            VideoContainer::is_video_codec_supported(VideoContainer::WEBM, $video_codec)
        ) {
            // WEBMs are MKVs with the VP9 or VP8 codec
            // For browser-friendliness, we'll just change the mime type
            $event->image->set_mime(MimeType::WEBM);
        }

        $event->image->set_media_properties(
            width: $width,
            height: $height,
            lossless: false,
            video: $video,
            audio: $audio,
            image: false,
            video_codec: $video_codec,
            length: $length,
        );
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
