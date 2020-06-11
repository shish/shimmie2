<?php declare(strict_types=1);

abstract class MediaEngine
{
    public const GD = "gd";
    public const IMAGICK = "convert";
    public const FFMPEG = "ffmpeg";
    public const STATIC = "static";

    public const ALL = [
        MediaEngine::GD,
        MediaEngine::FFMPEG,
        MediaEngine::IMAGICK,
        MediaEngine::STATIC,
    ];
    public const OUTPUT_SUPPORT = [
        MediaEngine::GD => [
            EXTENSION_GIF,
            EXTENSION_JPG,
            EXTENSION_PNG,
            EXTENSION_WEBP,
            Media::WEBP_LOSSY,
        ],
        MediaEngine::IMAGICK => [
            EXTENSION_GIF,
            EXTENSION_JPG,
            EXTENSION_PNG,
            EXTENSION_WEBP,
            Media::WEBP_LOSSY,
            Media::WEBP_LOSSLESS,
        ],
        MediaEngine::FFMPEG => [
            EXTENSION_JPG,
            EXTENSION_WEBP,
            EXTENSION_PNG,
        ],
        MediaEngine::STATIC => [
            EXTENSION_JPG,
        ],
    ];
    public const INPUT_SUPPORT = [
        MediaEngine::GD => [
            EXTENSION_BMP,
            EXTENSION_GIF,
            EXTENSION_JPG,
            EXTENSION_PNG,
            EXTENSION_WEBP,
            Media::WEBP_LOSSY,
            Media::WEBP_LOSSLESS,
        ],
        MediaEngine::IMAGICK => [
            EXTENSION_BMP,
            EXTENSION_GIF,
            EXTENSION_JPG,
            EXTENSION_PNG,
            EXTENSION_PSD,
            EXTENSION_TIFF,
            EXTENSION_WEBP,
            Media::WEBP_LOSSY,
            Media::WEBP_LOSSLESS,
            EXTENSION_ICO,
        ],
        MediaEngine::FFMPEG => [
            EXTENSION_AVI,
            EXTENSION_MKV,
            EXTENSION_WEBM,
            EXTENSION_MP4,
            EXTENSION_MOV,
            EXTENSION_FLASH_VIDEO,
        ],
        MediaEngine::STATIC => [
            EXTENSION_JPG,
            EXTENSION_GIF,
            EXTENSION_PNG,
        ],
    ];
    public const RESIZE_TYPE_SUPPORT = [
        MediaEngine::GD => [
            Media::RESIZE_TYPE_FIT,
            Media::RESIZE_TYPE_STRETCH
        ],
        MediaEngine::IMAGICK => [
            Media::RESIZE_TYPE_FIT,
            Media::RESIZE_TYPE_FIT_BLUR,
            Media::RESIZE_TYPE_FILL,
            Media::RESIZE_TYPE_STRETCH,
        ],
        MediaEngine::FFMPEG => [
            Media::RESIZE_TYPE_FIT
        ]
    ];
}
