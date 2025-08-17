<?php

declare(strict_types=1);

namespace Shimmie2;

enum VideoContainer: string
{
    case ASF = MimeType::ASF;
    case AVI = MimeType::AVI;
    case FLV = MimeType::FLASH_VIDEO;
    case MKV = MimeType::MKV;
    case MP4 = MimeType::MP4_VIDEO;
    case OGG = MimeType::OGG_VIDEO;
    case QUICKTIME = MimeType::QUICKTIME;
    case WEBM = MimeType::WEBM;

    public const VIDEO_CODEC_SUPPORT = [
        VideoContainer::ASF->value => [
            VideoCodec::MPEG4,
        ],
        VideoContainer::AVI->value => [
            VideoCodec::MPEG4,
        ],
        VideoContainer::FLV->value => [
            VideoCodec::FLV1,
        ],
        VideoContainer::MKV->value => [
            VideoCodec::VP8,
            VideoCodec::VP9,
            VideoCodec::THEORA,
            VideoCodec::H264,
            VideoCodec::H265,
            VideoCodec::MPEG4,
        ],
        VideoContainer::MP4->value => [
            VideoCodec::H264,
            VideoCodec::H265,
            VideoCodec::MPEG4,
        ],
        VideoContainer::OGG->value => [
            VideoCodec::THEORA,
        ],
        VideoContainer::QUICKTIME->value => [
            VideoCodec::H264,
            VideoCodec::H265,
            VideoCodec::MPEG4,
        ],
        VideoContainer::WEBM->value => [
            VideoCodec::VP8,
            VideoCodec::VP9,
        ],
    ];

    public static function fromMimeType(MimeType $mime): VideoContainer
    {
        // FIXME: video container being a mime type is an implementation detail
        // and shouldn't be relied upon
        return VideoContainer::from($mime->base);
    }
    public static function is_video_codec_supported(VideoContainer $container, VideoCodec $codec): bool
    {
        return array_key_exists($container->value, self::VIDEO_CODEC_SUPPORT) &&
                in_array($codec, self::VIDEO_CODEC_SUPPORT[$container->value]);
    }
}
