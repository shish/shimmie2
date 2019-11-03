<?php

abstract class MediaEngine
{
    public const GD = "gd";
    public const IMAGICK = "convert";
    public const FFMPEG = "ffmpeg";

    public const ALL = [
        MediaEngine::GD,
        MediaEngine::FFMPEG,
        MediaEngine::IMAGICK
    ];
    public const OUTPUT_SUPPORT = [
        MediaEngine::GD => [
            "gif",
            "jpg",
            "png",
            "webp",
            Media::WEBP_LOSSY,
        ],
        MediaEngine::IMAGICK => [
            "gif",
            "jpg",
            "png",
            "webp",
            Media::WEBP_LOSSY,
            Media::WEBP_LOSSLESS,
        ],
        MediaEngine::FFMPEG => [
            "jpg",
            "webp",
            "png"
        ]
    ];
    public const INPUT_SUPPORT = [
        MediaEngine::GD => [
            "bmp",
            "gif",
            "jpg",
            "png",
            "webp",
            Media::WEBP_LOSSY,
            Media::WEBP_LOSSLESS
        ],
        MediaEngine::IMAGICK => [
            "bmp",
            "gif",
            "jpg",
            "png",
            "psd",
            "tiff",
            "webp",
            Media::WEBP_LOSSY,
            Media::WEBP_LOSSLESS,
            "ico",
        ],
        MediaEngine::FFMPEG => [
            "avi",
            "mkv",
            "webm",
            "mp4",
            "mov",
            "flv"
        ]
    ];
}
