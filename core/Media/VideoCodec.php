<?php

declare(strict_types=1);

namespace Shimmie2;

enum VideoCodec: string
{
    case VP8 = "vp8";
    case VP9 = "vp9";
    case AV1 = "av1";
    case THEORA = "theora";
    case MPEG4 = "mpeg4";
    case H264 = "h264";
    case AVC = "avc";
    case H265 = "h265";
    case HEVC = "hevc";
    case FLV1 = "flv1";
    case UNKNOWN = "unknown";

    public static function from_or_unknown(string $codec): VideoCodec
    {
        try {
            return VideoCodec::from($codec);
        } catch (\ValueError) {
            return VideoCodec::UNKNOWN;
        }
    }
}
