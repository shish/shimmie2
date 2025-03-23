<?php

declare(strict_types=1);

namespace Shimmie2;

enum MediaEngine: string
{
    case GD = "gd";
    case IMAGICK = "convert";
    case FFMPEG = "ffmpeg";
    case STATIC = "static";

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
        MediaEngine::GD->value => [
            MimeType::GIF,
            MimeType::JPEG,
            MimeType::PNG,
            MimeType::WEBP,
            MimeType::AVIF,
        ],
        MediaEngine::IMAGICK->value => [
            MimeType::GIF,
            MimeType::JPEG,
            MimeType::PNG,
            MimeType::WEBP,
            MimeType::WEBP_LOSSLESS,
            MimeType::AVIF,
        ],
        MediaEngine::FFMPEG->value => [
            MimeType::JPEG,
            MimeType::WEBP,
            MimeType::PNG,
        ],
        MediaEngine::STATIC->value => [
            MimeType::JPEG,
        ],
    ];
    private const INPUT_SUPPORT = [
        MediaEngine::GD->value => [
            MimeType::BMP,
            MimeType::GIF,
            MimeType::JPEG,
            MimeType::PNG,
            MimeType::TGA,
            MimeType::WEBP,
            MimeType::WEBP_LOSSLESS,
            MimeType::AVIF,
        ],
        MediaEngine::IMAGICK->value => [
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
            MimeType::AVIF,
        ],
        MediaEngine::FFMPEG->value => [
            MimeType::AVI,
            MimeType::MKV,
            MimeType::WEBM,
            MimeType::MP4_VIDEO,
            MimeType::QUICKTIME,
            MimeType::FLASH_VIDEO,
        ],
        MediaEngine::STATIC->value => [
            MimeType::JPEG,
            MimeType::GIF,
            MimeType::PNG,
            MimeType::AVIF,
        ],
    ];
    public const RESIZE_TYPE_SUPPORT = [
        MediaEngine::GD->value => [
            Media::RESIZE_TYPE_FIT,
            Media::RESIZE_TYPE_STRETCH
        ],
        MediaEngine::IMAGICK->value => [
            Media::RESIZE_TYPE_FIT,
            Media::RESIZE_TYPE_FIT_BLUR,
            Media::RESIZE_TYPE_FIT_BLUR_PORTRAIT,
            Media::RESIZE_TYPE_FILL,
            Media::RESIZE_TYPE_STRETCH,
        ],
        MediaEngine::FFMPEG->value => [
            Media::RESIZE_TYPE_FIT
        ],
        MediaEngine::STATIC->value => [
            Media::RESIZE_TYPE_FIT
        ]
    ];

    public static function is_output_supported(MediaEngine $engine, MimeType $mime): bool
    {
        return MimeType::matches_array(
            $mime,
            MediaEngine::OUTPUT_SUPPORT[$engine->value],
            true
        );
    }

    public static function is_input_supported(MediaEngine $engine, MimeType $mime): bool
    {
        return MimeType::matches_array(
            $mime,
            MediaEngine::INPUT_SUPPORT[$engine->value]
        );
    }
}
