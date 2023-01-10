<?php

declare(strict_types=1);

namespace Shimmie2;

abstract class MediaEngine
{
    public const GD = "gd";
    public const IMAGICK = "convert";
    public const FFMPEG = "ffmpeg";
    public const STATIC = "static";

    public const IMAGE_ENGINES = [
        "GD" => MediaEngine::GD,
        "ImageMagick" => MediaEngine::IMAGICK,
    ];

    public const ALL = [
        MediaEngine::GD,
        MediaEngine::FFMPEG,
        MediaEngine::IMAGICK,
        MediaEngine::STATIC,
    ];
    private const OUTPUT_SUPPORT = [
        MediaEngine::GD => [
            MimeType::GIF,
            MimeType::JPEG,
            MimeType::PNG,
            MimeType::WEBP
        ],
        MediaEngine::IMAGICK => [
            MimeType::GIF,
            MimeType::JPEG,
            MimeType::PNG,
            MimeType::WEBP,
            MimeType::WEBP_LOSSLESS,
        ],
        MediaEngine::FFMPEG => [
            MimeType::JPEG,
            MimeType::WEBP,
            MimeType::PNG,
        ],
        MediaEngine::STATIC => [
            MimeType::JPEG,
        ],
    ];
    private const INPUT_SUPPORT = [
        MediaEngine::GD => [
            MimeType::BMP,
            MimeType::GIF,
            MimeType::JPEG,
            MimeType::PNG,
            MimeType::TGA,
            MimeType::WEBP,
            MimeType::WEBP_LOSSLESS,
        ],
        MediaEngine::IMAGICK => [
            MimeType::BMP,
            MimeType::GIF,
            MimeType::JPEG,
            MimeType::PNG,
            MimeType::PPM,
            MimeType::PSD,
            MimeType::TGA,
            MimeType::TIFF,
            MimeType::WEBP,
            MimeType::WEBP_LOSSLESS,
            MimeType::ICO,
        ],
        MediaEngine::FFMPEG => [
            MimeType::AVI,
            MimeType::MKV,
            MimeType::WEBM,
            MimeType::MP4_VIDEO,
            MimeType::QUICKTIME,
            MimeType::FLASH_VIDEO,
        ],
        MediaEngine::STATIC => [
            MimeType::JPEG,
            MimeType::GIF,
            MimeType::PNG,
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
            Media::RESIZE_TYPE_FIT_BLUR_PORTRAIT,
            Media::RESIZE_TYPE_FILL,
            Media::RESIZE_TYPE_STRETCH,
        ],
        MediaEngine::FFMPEG => [
            Media::RESIZE_TYPE_FIT
        ],
        MediaEngine::STATIC => [
            Media::RESIZE_TYPE_FIT
        ]
    ];

    public static function is_output_supported(string $engine, string $mime): bool
    {
        return MimeType::matches_array(
            $mime,
            MediaEngine::OUTPUT_SUPPORT[$engine],
            true
        );
    }

    public static function is_input_supported(string $engine, string $mime): bool
    {
        return MimeType::matches_array(
            $mime,
            MediaEngine::INPUT_SUPPORT[$engine]
        );
    }
}
