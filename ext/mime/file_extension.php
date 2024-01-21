<?php

declare(strict_types=1);

namespace Shimmie2;

class FileExtension
{
    public const ANI = 'ani';
    public const ASC = 'asc';
    public const ASF = 'asf';
    public const ASX = 'asx';
    public const AVI = 'avi';
    public const BMP = 'bmp';
    public const BZIP = 'bz';
    public const BZIP2 = 'bz2';
    public const CBR = 'cbr';
    public const CBZ = 'cbz';
    public const CBT = 'cbt';
    public const CBA = 'cbA';
    public const CB7 = 'cb7';
    public const CSS = 'css';
    public const CSV = 'csv';
    public const CUR = 'cur';
    public const FLASH = 'swf';
    public const FLASH_VIDEO = 'flv';
    public const GIF = 'gif';
    public const GZIP = 'gz';
    public const HTML = 'html';
    public const HTM = 'htm';
    public const ICO = 'ico';
    public const JFIF = 'jfif';
    public const JFI = 'jfi';
    public const JPEG = 'jpeg';
    public const JPG = 'jpg';
    public const JS = 'js';
    public const JSON = 'json';
    public const MKV = 'mkv';
    public const MP3 = 'mp3';
    public const MP4 = 'mp4';
    public const M4V = 'm4v';
    public const M4A = 'm4a';
    public const MPEG = 'mpeg';
    public const MPG = 'mpg';
    public const OGG = 'ogg';
    public const OGG_VIDEO = 'ogv';
    public const OGG_AUDIO = 'oga';
    public const PDF = 'pdf';
    public const PHP = 'php';
    public const PHP5 = 'php5';
    public const PNG = 'png';
    public const PSD = 'psd';
    public const PPM = 'ppm';
    public const MOV = 'mov';
    public const RSS = 'rss';
    public const SVG = 'svg';
    public const TAR = 'tar';
    public const TGA = 'tga';
    public const TEXT = 'txt';
    public const TIFF = 'tiff';
    public const TIF = 'tif';
    public const WAV = 'wav';
    public const WEBM = 'webm';
    public const WEBP = 'webp';
    public const WMA = 'wma';
    public const WMV = 'wmv';
    public const XML = 'xml';
    public const XSL = 'xsl';
    public const ZIP = 'zip';

    /**
     * Returns the main file extension associated with the specified mimetype.
     */
    public static function get_for_mime(string $mime): ?string
    {
        if (empty($mime)) {
            return null;
        }

        if ($mime == MimeType::OCTET_STREAM) {
            return null;
        }

        $data = MimeMap::get_for_mime($mime);
        if ($data != null) {
            return $data[MimeMap::MAP_EXT][0];
        }
        return null;
    }

    /**
     * Returns all the file extension associated with the specified mimetype.
     *
     * @return string[]
     */
    public static function get_all_for_mime(string $mime): array
    {
        if (empty($mime)) {
            return [];
        }

        if ($mime == MimeType::OCTET_STREAM) {
            return [];
        }

        $data = MimeMap::get_for_mime($mime);
        if ($data != null) {
            return $data[MimeMap::MAP_EXT];
        }

        return [];
    }
}
