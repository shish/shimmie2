<?php

declare(strict_types=1);

namespace Shimmie2;

final class ThumbnailUtil
{
    /**
     * Given a full size pair of dimensions, return a pair scaled down to fit
     * into the configured thumbnail square, with ratio intact.
     * Optionally uses the High-DPI scaling setting to adjust the final resolution.
     *
     * @param 0|positive-int $orig_width
     * @param 0|positive-int $orig_height
     * @param bool $use_dpi_scaling Enables the High-DPI scaling.
     * @return array{0: positive-int, 1: positive-int}
     */
    public static function get_thumbnail_size(int $orig_width, int $orig_height, bool $use_dpi_scaling = false): array
    {
        $fit = ResizeType::from(Ctx::$config->get(ThumbnailConfig::FIT));
        $conf_width = Ctx::$config->get(ThumbnailConfig::WIDTH);
        $conf_height = Ctx::$config->get(ThumbnailConfig::HEIGHT);
        assert($conf_width > 0 && $conf_height > 0);

        if (in_array($fit, [
                ResizeType::FILL,
                ResizeType::STRETCH,
                ResizeType::FIT_BLUR,
                ResizeType::FIT_BLUR_PORTRAIT
            ])) {
            return [$conf_width, $conf_height];
        }

        if ($orig_width === 0) {
            $orig_width = 192;
        }
        if ($orig_height === 0) {
            $orig_height = 192;
        }

        if ($orig_width > $orig_height * 5) {
            $orig_width = $orig_height * 5;
        }
        if ($orig_height > $orig_width * 5) {
            $orig_height = $orig_width * 5;
        }


        if ($use_dpi_scaling) {
            list($max_width, $max_height) = self::get_thumbnail_max_size_scaled();
        } else {
            $max_width = $conf_width;
            $max_height = $conf_height;
        }

        list($width, $height, $scale) = self::get_scaled_by_aspect_ratio($orig_width, $orig_height, $max_width, $max_height);

        if ($scale > 1 && Ctx::$config->get(ThumbnailConfig::UPSCALE)) {
            return [$orig_width, $orig_height];
        } else {
            return [$width, $height];
        }
    }

    /**
     * @param positive-int $original_width
     * @param positive-int $original_height
     * @param positive-int $max_width
     * @param positive-int $max_height
     * @return array{0: positive-int, 1: positive-int, 2: float}
     */
    public static function get_scaled_by_aspect_ratio(int $original_width, int $original_height, int $max_width, int $max_height): array
    {
        $xscale = ($max_width / $original_width);
        $yscale = ($max_height / $original_height);
        $scale = ($yscale < $xscale) ? $yscale : $xscale;
        assert($scale > 0);

        $new_width = (int)($original_width * $scale);
        $new_height = (int)($original_height * $scale);
        assert($new_width > 0);
        assert($new_height > 0);

        return [$new_width, $new_height, $scale];
    }

    /**
     * Fetches the thumbnails height and width settings and applies the High-DPI scaling setting before returning the dimensions.
     *
     * @return array{0: positive-int, 1: positive-int}
     */
    public static function get_thumbnail_max_size_scaled(): array
    {
        $scaling = Ctx::$config->get(ThumbnailConfig::SCALING);
        $max_width  = Ctx::$config->get(ThumbnailConfig::WIDTH) * ($scaling / 100);
        $max_height = Ctx::$config->get(ThumbnailConfig::HEIGHT) * ($scaling / 100);
        assert($max_width > 0);
        assert($max_height > 0);
        return [$max_width, $max_height];
    }

    public static function create_image_thumb(Image $image, ?MediaEngine $engine = null): void
    {
        self::create_scaled_image(
            $image->get_image_filename(),
            $image->get_thumb_filename(),
            self::get_thumbnail_max_size_scaled(),
            $image->get_mime(),
            $engine,
            ResizeType::from(Ctx::$config->get(ThumbnailConfig::FIT))
        );
    }

    /**
     * @param array{0: positive-int, 1: positive-int} $tsize
     */
    public static function create_scaled_image(
        Path $inname,
        Path $outname,
        array $tsize,
        MimeType $mime,
        ?MediaEngine $engine = null,
        ?ResizeType $resize_type = null
    ): void {
        $engine ??= MediaEngine::from(Ctx::$config->get(ThumbnailConfig::ENGINE));
        $resize_type ??= ResizeType::from(Ctx::$config->get(ThumbnailConfig::FIT));
        $output_mime = new MimeType(Ctx::$config->get(ThumbnailConfig::MIME));

        send_event(new MediaResizeEvent(
            $engine,
            $inname,
            $mime,
            $outname,
            $tsize[0],
            $tsize[1],
            $resize_type,
            $output_mime,
            Ctx::$config->get(ThumbnailConfig::ALPHA_COLOR),
            Ctx::$config->get(ThumbnailConfig::QUALITY),
            true,
            true
        ));
    }
}
