<?php

declare(strict_types=1);

namespace Shimmie2;

final class MimeType
{
    public const JSON = 'application/json';
    public const OCTET_STREAM = 'application/octet-stream';
    public const OGG = 'application/ogg';
    public const PDF = 'application/pdf';
    public const ANI = 'application/x-navi-animation';
    public const RSS = 'application/rss+xml';
    public const COMIC_RAR = 'application/vnd.comicbook-rar';
    public const COMIC_ZIP = 'application/vnd.comicbook+zip';
    public const BZIP = 'application/x-bzip';
    public const BZIP2 = 'application/x-bzip2';
    public const GZIP = 'application/gzip';
    public const XML_APPLICATION = 'application/xml';
    public const FLASH = 'application/x-shockwave-flash';
    public const XSL = 'application/xsl+xml';
    public const TAR = 'application/x-tar';
    public const ZIP = 'application/zip';

    public const FLAC = 'audio/flac';
    public const MP4_AUDIO = 'audio/mp4';
    public const MP3 = 'audio/mpeg';
    public const OGG_AUDIO = 'audio/ogg';
    public const WMA = 'audio/x-ms-wma';
    public const WAV = 'audio/x-wav';

    public const AVIF = 'image/avif';
    public const BMP = 'image/bmp';
    public const GIF = 'image/gif';
    public const GIF_ANIMATED = self::GIF."; animated=true";
    public const JPEG = 'image/jpeg';
    public const PNG = 'image/png';
    public const SVG = 'image/svg+xml';
    public const TIFF = 'image/tiff';
    public const PSD = 'image/vnd.adobe.photoshop';
    public const ICO = 'image/vnd.microsoft.icon';
    public const WEBP = 'image/webp';
    public const WEBP_LOSSLESS = self::WEBP."; lossless=true";
    public const PPM = 'image/x-portable-pixmap';
    public const TGA = 'image/x-tga';

    public const CSS = 'text/css';
    public const CSV = 'text/csv';
    public const HEIC = 'text/heic';
    public const HTML = 'text/html';
    public const JS = 'text/javascript';
    public const TEXT = 'text/plain';
    public const XML = 'text/xml';
    public const PHP = 'text/x-php';

    public const MP4_VIDEO = 'video/mp4';
    public const MPEG = 'video/mpeg';
    public const OGG_VIDEO = 'video/ogg';
    public const QUICKTIME = 'video/quicktime';
    public const WEBM = 'video/webm';
    public const FLASH_VIDEO = 'video/x-flv';
    public const MKV = 'video/x-matroska';
    public const ASF = 'video/x-ms-asf';
    public const AVI = 'video/x-msvideo';
    public const WMV = 'video/x-ms-wmv';

    public string $base;
    /** @var array<string,string> */
    public array $parameters;

    public function __construct(
        string $input
    ) {
        if (\Safe\preg_match("/^([-\w.]+)\/([-\w.\+]+)(;.+)?$/", $input) !== 1) {
            throw new \InvalidArgumentException("Invalid MIME type: $input");
        }
        $parts = explode(';', $input);
        $this->base = trim(strtolower(array_shift($parts)));
        $this->parameters = [];
        foreach ($parts as $part) {
            if (strpos($part, '=') !== false) {
                [$k, $v] = explode('=', $part, 2);
                $this->parameters[strtolower(trim($k))] = trim($v);
            } else {
                $this->parameters[strtolower(trim($part))] = '';
            }
        }
    }

    public function __toString(): string
    {
        if (empty($this->parameters)) {
            return $this->base;
        }
        $ps = [];
        foreach ($this->parameters as $k => $v) {
            if ($v === '') {
                $ps[] = $k;
            } else {
                $ps[] = $k . '=' . $v;
            }
        }
        return $this->base . '; ' . implode('; ', $ps);
    }

    /**
     * @param array<string> $mime_array
     */
    public static function matches_array(MimeType $mime, array $mime_array, bool $exact = false): bool
    {
        // If there's an exact match, find it and that's it
        if (in_array((string)$mime, $mime_array)) {
            return true;
        }
        if ($exact) {
            return false;
        }

        // Check for aliases
        $data = MimeMap::get_for_mime($mime);
        if ($data !== null) {
            foreach ($data[MimeMap::MAP_MIME] as $alias) {
                if (in_array($alias, $mime_array)) {
                    return true;
                }
            }
        }

        // Check for match without parameters
        return in_array($mime->base, $mime_array);
    }

    public static function matches(MimeType $mime1, MimeType $mime2, bool $exact = false): bool
    {
        if ($exact) {
            return $mime1->base === $mime2->base && $mime1->parameters === $mime2->parameters;
        } else {
            return $mime1->base === $mime2->base;
        }
    }

    /**
     * Returns the mimetype that matches the provided extension.
     */
    public static function get_for_extension(string $ext): ?MimeType
    {
        $data = MimeMap::get_for_extension($ext);
        if ($data !== null) {
            return new MimeType($data[MimeMap::MAP_MIME][0]);
        }
        // This was an old solution for differentiating lossless webps
        if ($ext === "webp-lossless") {
            return new MimeType(MimeType::WEBP_LOSSLESS);
        }
        return null;
    }

    /**
     * Returns the mimetype for the specified file via file inspection
     * @return MimeType The mimetype that was found. Returns generic octet binary mimetype if not found.
     */
    public static function get_for_file(Path $file, ?string $ext = null): MimeType
    {
        if (!$file->exists()) {
            throw new UserError("File not found: ".$file->str());
        }

        $output = self::OCTET_STREAM;

        $finfo = \Safe\finfo_open(FILEINFO_MIME_TYPE);
        $type = finfo_file($finfo, $file->str());

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

        return new MimeType($output);
    }
}
