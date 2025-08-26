<?php

declare(strict_types=1);

namespace Shimmie2;

final class VideoFileHandler extends DataHandlerExtension
{
    public const KEY = "handle_video";
    public const SUPPORTED_MIME = [
        MimeType::MP4_VIDEO,
        MimeType::WEBM,
    ];
    private const VIDEO_CODEC_SUPPORT = [
        MimeType::MP4_VIDEO => [
            VideoCodec::H264,
            VideoCodec::H265,
        ],
        MimeType::WEBM => [
            VideoCodec::VP8,
            VideoCodec::VP9,
            VideoCodec::AV1,
        ],
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

        if (is_null($video_codec)) {
            throw new MediaException("Could not determine video codec");
        }

        $supported_codecs = self::VIDEO_CODEC_SUPPORT[$image->get_mime()->base];
        if (!in_array($video_codec, $supported_codecs)) {
            throw new MediaException("Unsupported video codec '{$video_codec->name}' for '{$image->get_mime()->base}' container. Supported codecs are " . implode(", ", array_map(fn ($c) => $c->name, $supported_codecs)) . ".");
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

    protected function create_thumb(Image $image): bool
    {
        $inname = $image->get_image_filename();
        $outname = $image->get_thumb_filename();

        $ok = false;
        $tmpname = shm_tempnam("ffmpeg_thumb");
        try {
            $scaled_size = ThumbnailUtil::get_thumbnail_size($image->width, $image->height, true);

            $command = new CommandBuilder(Ctx::$config->get(VideoFileHandlerConfig::FFMPEG_PATH));
            $command->add_args("-y", "-hide_banner", "-loglevel", "quiet");
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
        return MimeType::matches_array(
            MimeType::get_for_file($tmpname),
            self::SUPPORTED_MIME
        );
    }
}
