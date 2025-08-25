<?php

declare(strict_types=1);

namespace Shimmie2;

final class ImageFileHandler extends DataHandlerExtension
{
    public const KEY = "handle_image";
    public const SUPPORTED_MIME = [
        MimeType::JPEG,
        MimeType::GIF,
        MimeType::PNG,
        MimeType::WEBP,
        MimeType::AVIF,
    ];

    protected function media_check_properties(Image $image): MediaProperties
    {
        $filename = $image->get_image_filename();
        $mime = $image->get_mime();

        $lossless = self::is_lossless($filename, $mime);
        switch ($mime->base) {
            case MimeType::GIF:
                $video = self::is_animated_gif($filename);
                $length = null; // FIXME
                break;
            case MimeType::WEBP:
                $video = self::is_animated_webp($filename);
                $length = null; // FIXME
                break;
            default:
                $video = false;
                $length = null;
                break;
        }

        [$width, $height] = self::get_image_size($filename);

        return new MediaProperties(
            width: $width,
            height: $height,
            lossless: $lossless,
            video: $video,
            audio: false,
            image: !$video,
            video_codec: null,
            length: $length,
        );
    }

    protected function check_contents(Path $tmpname): bool
    {
        $valid = [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_WEBP, IMAGETYPE_AVIF];
        $info = getimagesize($tmpname->str());
        return $info && in_array($info[2], $valid);
    }

    protected function create_thumb(Image $image): bool
    {
        try {
            ThumbnailUtil::create_image_thumb($image);
            return true;
        } catch (\Exception $e) {
            throw new UploadException("Error while creating thumbnail: ".$e->getMessage());
        }
    }

    /**
     * Get the dimensions of an image file.
     *
     * @return array{0:int<0,max>, 1:int<0,max>} An array containing the width and height of the image.
     */
    private static function get_image_size(Path $filename): array
    {
        $info = getimagesize($filename->str());
        if (!$info) {
            throw new MediaException("Could not get image size");
        }
        $width = $info[0];
        $height = $info[1];

        if (function_exists('exif_read_data') && $info[2] === IMAGETYPE_JPEG) {
            $exif = exif_read_data($filename->str());
            if ($exif && isset($exif['Orientation'])) {
                $orientation = $exif['Orientation'];
                if ($orientation === 6 || $orientation === 8) {
                    [$width, $height] = [$height, $width];
                }
            }
        }

        return [$width, $height];
    }

    private const LOSSLESS_FORMATS = [
        MimeType::WEBP_LOSSLESS,
        MimeType::PNG,
        MimeType::PSD,
        MimeType::BMP,
        MimeType::ICO,
        MimeType::ANI,
        MimeType::GIF
    ];

    //RIFF####WEBPVP8L
    private const WEBP_LOSSLESS_HEADER =
        [0x52, 0x49, 0x46, 0x46, null, null, null, null, 0x57, 0x45, 0x42, 0x50, 0x56, 0x50, 0x38, 0x4C];

    private static function is_lossless(Path $filename, MimeType $mime): bool
    {
        if (in_array((string)$mime, self::LOSSLESS_FORMATS)) {
            return true;
        }
        if ($mime->base === MimeType::WEBP) {
            return compare_file_bytes($filename, self::WEBP_LOSSLESS_HEADER);
        }
        return false;
    }

    /**
     * Determines if a file is an animated gif.
     *
     * @param Path $image_filename The path of the file to check.
     * @return bool true if the file is an animated gif, false if it is not.
     */
    public static function is_animated_gif(Path $image_filename): bool
    {
        $is_anim_gif = 0;
        if (($fh = @fopen($image_filename->str(), 'rb'))) {
            try {
                //check if gif is animated (via https://www.php.net/manual/en/function.imagecreatefromgif.php#104473)
                $chunk = false;

                while (!feof($fh) && $is_anim_gif < 2) {
                    $chunk =  ($chunk ? substr($chunk, -20) : "") . fread($fh, 1024 * 100); //read 100kb at a time
                    $is_anim_gif += \Safe\preg_match_all('#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $chunk);
                }
            } finally {
                @fclose($fh);
            }
        }
        return ($is_anim_gif >= 2);
    }

    //RIFF####WEBPVP8?..............ANIM
    private const WEBP_ANIMATION_HEADER =
        [0x52, 0x49, 0x46, 0x46, null, null, null, null, 0x57, 0x45, 0x42, 0x50, 0x56, 0x50, 0x38, null,
            null, null, null, null, null, null, null, null, null, null, null, null, null, null, 0x41, 0x4E, 0x49, 0x4D];

    public static function is_animated_webp(Path $image_filename): bool
    {
        return compare_file_bytes($image_filename, self::WEBP_ANIMATION_HEADER);
    }
}
