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

    protected function media_check_properties(Image $image): MediaProperties
    {
        $video = false;
        $audio = false;
        $width = 0;
        $height = 0;
        $video_codec = null;

        $command = new CommandBuilder(Ctx::$config->get(VideoFileHandlerConfig::FFPROBE_PATH));
        $command->add_args("-print_format", "json");
        $command->add_args("-v", "quiet");
        $command->add_args("-show_format");
        $command->add_args("-show_streams");
        $command->add_args($image->get_image_filename()->str());
        $output = $command->execute();
        $data = json_decode($output, true);

        foreach ($data["streams"] as $stream) {
            switch ($stream["codec_type"]) {
                case "audio":
                    $audio = true;
                    break;
                case "video":
                    $video = true;
                    $video_codec = VideoCodec::from_or_unknown($stream["codec_name"]);
                    $width = max($image->width, $stream["width"]);
                    $height = max($image->height, $stream["height"]);
                    break;
            }
        }
        $length = (int)floor(floatval($data["format"]["duration"]) * 1000);
        assert($length >= 0);

        if (
            $image->get_mime()->base === MimeType::MKV &&
            $video_codec !== null &&
            VideoContainer::is_video_codec_supported(VideoContainer::WEBM, $video_codec)
        ) {
            // WEBMs are MKVs with the VP9 or VP8 codec
            // For browser-friendliness, we'll just change the mime type
            $image->set_mime(MimeType::WEBM);
        }

        return new MediaProperties(
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
        $inname = $image->get_image_filename();
        $outname = $image->get_thumb_filename();

        $ok = false;
        $tmpname = shm_tempnam("ffmpeg_thumb");
        try {
            $scaled_size = ThumbnailUtil::get_thumbnail_size($image->width, $image->height, true);

            $command = new CommandBuilder(Ctx::$config->get(VideoFileHandlerConfig::FFMPEG_PATH));
            $command->add_args("-y");
            $command->add_args("-i", $inname->str());
            $command->add_args("-vf", "scale=$scaled_size[0]:$scaled_size[1],thumbnail");
            $command->add_args("-f", "image2");
            $command->add_args("-vframes", "1");
            $command->add_args("-c:v", "png");
            $command->add_args($tmpname->str());
            $command->execute();

            ThumbnailUtil::create_scaled_image($tmpname, $outname, $scaled_size, new MimeType(MimeType::PNG));
            $ok = true;
        } finally {
            @$tmpname->unlink();
        }
        return $ok;
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
