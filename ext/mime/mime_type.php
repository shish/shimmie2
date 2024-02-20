<?php

declare(strict_types=1);

namespace Shimmie2;

require_once "file_extension.php";

class MimeType
{
    // Couldn't find a mimetype for ani, so made one up based on it being a riff container
    public const ANI = 'application/riff+ani';
    public const ASF = 'video/x-ms-asf';
    public const AVI = 'video/x-msvideo';
    // Went with mime types from http://fileformats.archiveteam.org/wiki/Comic_Book_Archive
    public const COMIC_ZIP = 'application/vnd.comicbook+zip';
    public const COMIC_RAR = 'application/vnd.comicbook-rar';
    public const BMP = 'image/x-ms-bmp';
    public const BZIP = 'application/x-bzip';
    public const BZIP2 = 'application/x-bzip2';
    public const CSS = 'text/css';
    public const CSV = 'text/csv';
    public const FLASH = 'application/x-shockwave-flash';
    public const FLASH_VIDEO = 'video/x-flv';
    public const GIF = 'image/gif';
    public const GZIP = 'application/x-gzip';
    public const HTML = 'text/html';
    public const ICO = 'image/x-icon';
    public const ICO_OSX = 'image/vnd.microsoft.icon';
    public const JPEG = 'image/jpeg';
    public const JS = 'text/javascript';
    public const JSON = 'application/json';
    public const MKV = 'video/x-matroska';
    public const MP3 = 'audio/mpeg';
    public const MP4_AUDIO = 'audio/mp4';
    public const MP4_VIDEO = 'video/mp4';
    public const MPEG = 'video/mpeg';
    public const OCTET_STREAM = 'application/octet-stream';
    public const OGG = 'application/ogg';
    public const OGG_VIDEO = 'video/ogg';
    public const OGG_AUDIO = 'audio/ogg';
    public const PDF = 'application/pdf';
    public const PHP = 'text/x-php';
    public const PNG = 'image/png';
    public const PPM = 'image/x-portable-pixmap';
    public const PSD = 'image/vnd.adobe.photoshop';
    public const QUICKTIME = 'video/quicktime';
    public const RSS = 'application/rss+xml';
    public const SVG = 'image/svg+xml';
    public const TAR = 'application/x-tar';
    public const TGA = 'image/x-tga';
    public const TEXT = 'text/plain';
    public const TIFF = 'image/tiff';
    public const WAV = 'audio/x-wav';
    public const WEBM = 'video/webm';
    public const WEBP = 'image/webp';
    public const WEBP_LOSSLESS = self::WEBP."; ".self::LOSSLESS_PARAMETER;
    public const WIN_BITMAP = 'image/x-win-bitmap';
    public const WMA = 'audio/x-ms-wma';
    public const WMV = 'video/x-ms-wmv';
    public const XML = 'text/xml';
    public const XML_APPLICATION = 'application/xml';
    public const XSL = 'application/xsl+xml';
    public const ZIP = 'application/zip';

    public const LOSSLESS_PARAMETER = "lossless=true";

    public const CHARSET_UTF8 = "charset=utf-8";

    //RIFF####WEBPVP8?..............ANIM
    private const WEBP_ANIMATION_HEADER =
        [0x52, 0x49, 0x46, 0x46, null, null, null, null, 0x57, 0x45, 0x42, 0x50, 0x56, 0x50, 0x38, null,
            null, null, null, null, null, null, null, null, null, null, null, null, null, null, 0x41, 0x4E, 0x49, 0x4D];

    //RIFF####WEBPVP8L
    private const WEBP_LOSSLESS_HEADER =
        [0x52, 0x49, 0x46, 0x46, null, null, null, null, 0x57, 0x45, 0x42, 0x50, 0x56, 0x50, 0x38, 0x4C];

    private const REGEX_MIME_TYPE = "/^([-\w.]+)\/([-\w.]+)(;.+)?$/";

    public static function is_mime(string $value): bool
    {
        return preg_match(self::REGEX_MIME_TYPE, $value) === 1;
    }

    public static function add_parameters(string $mime, string ...$parameters): string
    {
        if (empty($parameters)) {
            return $mime;
        }
        return $mime."; ".join("; ", $parameters);
    }

    public static function remove_parameters(string $mime): string
    {
        $i = strpos($mime, ";");
        if ($i !== false) {
            return substr($mime, 0, $i);
        }
        return $mime;
    }

    /**
     * @param array<string> $mime_array
     */
    public static function matches_array(string $mime, array $mime_array, bool $exact = false): bool
    {
        // If there's an exact match, find it and that's it
        if (in_array($mime, $mime_array)) {
            return true;
        }
        if ($exact) {
            return false;
        }

        $mime = self::remove_parameters($mime);

        return in_array($mime, $mime_array);
    }

    public static function matches(string $mime1, string $mime2, bool $exact = false): bool
    {
        if (!$exact) {
            $mime1 = self::remove_parameters($mime1);
            $mime2 = self::remove_parameters($mime2);
        }
        return strtolower($mime1) === strtolower($mime2);
    }


    /**
     * Determines if a file is an animated gif.
     *
     * @param string $image_filename The path of the file to check.
     * @return bool true if the file is an animated gif, false if it is not.
     */
    public static function is_animated_gif(string $image_filename): bool
    {
        $is_anim_gif = 0;
        if (($fh = @fopen($image_filename, 'rb'))) {
            try {
                //check if gif is animated (via https://www.php.net/manual/en/function.imagecreatefromgif.php#104473)
                $chunk = false;

                while (!feof($fh) && $is_anim_gif < 2) {
                    $chunk =  ($chunk ? substr($chunk, -20) : "") . fread($fh, 1024 * 100); //read 100kb at a time
                    $is_anim_gif += preg_match_all('#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $chunk, $matches);
                }
            } finally {
                @fclose($fh);
            }
        }
        return ($is_anim_gif >= 2);
    }


    /**
     * @param array<int|null> $comparison
     */
    private static function compare_file_bytes(string $file_name, array $comparison): bool
    {
        $size = \Safe\filesize($file_name);
        $cc = count($comparison);
        if ($size < $cc) {
            // Can't match because it's too small
            return false;
        }

        if (($fh = @fopen($file_name, 'rb'))) {
            try {
                $chunk = \Safe\unpack("C*", \Safe\fread($fh, $cc));

                for ($i = 0; $i < $cc; $i++) {
                    $byte = $comparison[$i];
                    if ($byte === null) {
                        continue;
                    } else {
                        $fileByte = $chunk[$i + 1];
                        if ($fileByte != $byte) {
                            return false;
                        }
                    }
                }
                return true;
            } finally {
                @fclose($fh);
            }
        } else {
            throw new MediaException("Unable to open file for byte check: $file_name");
        }
    }

    public static function is_animated_webp(string $image_filename): bool
    {
        return self::compare_file_bytes($image_filename, self::WEBP_ANIMATION_HEADER);
    }

    public static function is_lossless_webp(string $image_filename): bool
    {
        return self::compare_file_bytes($image_filename, self::WEBP_LOSSLESS_HEADER);
    }



    /**
     * Returns the mimetype that matches the provided extension.
     */
    public static function get_for_extension(string $ext): ?string
    {
        $data = MimeMap::get_for_extension($ext);
        if ($data != null) {
            return $data[MimeMap::MAP_MIME][0];
        }
        // This was an old solution for differentiating lossless webps
        if ($ext === "webp-lossless") {
            return MimeType::WEBP_LOSSLESS;
        }
        return null;
    }

    /**
     * Returns the mimetype for the specified file via file inspection
     * @param string $file
     * @return string The mimetype that was found. Returns generic octet binary mimetype if not found.
     */
    public static function get_for_file(string $file, ?string $ext = null): string
    {
        if (!file_exists($file)) {
            throw new UserError("File not found: ".$file);
        }

        $output = self::OCTET_STREAM;

        $finfo = false_throws(finfo_open(FILEINFO_MIME_TYPE));
        $type = finfo_file($finfo, $file);
        finfo_close($finfo);

        if ($type !== false && !empty($type)) {
            $output = $type;
        }

        if (!empty($ext)) {
            // Here we handle the few file types that need extension-based handling
            $ext = strtolower($ext);
            if ($type === MimeType::ZIP && $ext === FileExtension::CBZ) {
                $output = MimeType::COMIC_ZIP;
            }
            if ($type === MimeType::OCTET_STREAM) {
                switch ($ext) {
                    case FileExtension::ANI:
                        $output = MimeType::ANI;
                        break;
                    case FileExtension::PPM:
                        $output = MimeType::PPM;
                        break;
                        // TODO: There is no uniquely defined Mime type for the cursor format. Need to figure this out.
                        /*
                        case FileExtension::CUR:
                            $output = MimeType::CUR;
                            break;
                        */
                }
            }
        }

        // TODO: Implement manual byte inspections for supported esoteric formats, like ANI

        return $output;
    }
}
