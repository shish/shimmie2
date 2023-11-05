<?php

declare(strict_types=1);

namespace Shimmie2;

abstract class VideoContainers
{
    public const WEBM = MimeType::WEBM;
    public const MP4 = MimeType::MP4_VIDEO;
    public const OGG = MimeType::OGG_VIDEO;
    public const MKV = MimeType::MKV;

    public const ALL = [
        VideoContainers::WEBM,
        VideoContainers::MP4,
        VideoContainers::OGG,
        VideoContainers::MKV,
    ];

    public const VIDEO_CODEC_SUPPORT = [
        VideoContainers::WEBM => [
            VideoCodecs::VP8,
            VideoCodecs::VP9,
        ],
        VideoContainers::OGG => [
            VideoCodecs::THEORA,
        ],
        VideoContainers::MP4 => [
            VideoCodecs::H264,
            VideoCodecs::H265,
            VideoCodecs::MPEG4,
        ],
        VideoContainers::MKV => VideoCodecs::ALL // The one container to rule them all
    ];


    public static function is_video_codec_supported(string $container, string $codec): bool
    {
        return array_key_exists($container, self::VIDEO_CODEC_SUPPORT) &&
                in_array($codec, self::VIDEO_CODEC_SUPPORT[$container]);
    }
}

abstract class VideoCodecs
{
    public const VP9 = "vp9";
    public const VP8 = "vp8";
    public const H264 = "h264";
    public const H265 = "h265";
    public const MPEG4 = "mpeg4";
    public const THEORA = "theora";

    public const ALL = [
        VideoCodecs::VP9,
        VideoCodecs::VP8,
        VideoCodecs::H264,
        VideoCodecs::H265,
        VideoCodecs::MPEG4,
        VideoCodecs::THEORA,
    ];




    //
    //    public static function is_input_supported(string $engine, string $mime): bool
    //    {
    //        return MimeType::matches_array(
    //            $mime,
    //            MediaEngine::INPUT_SUPPORT[$engine]
    //        );
    //    }
}
