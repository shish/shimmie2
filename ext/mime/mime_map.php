<?php

declare(strict_types=1);

namespace Shimmie2;

class MimeMap
{
    public const MAP_NAME = 'name';
    public const MAP_EXT = 'ext';
    public const MAP_MIME = 'mime';

    // Mime type map. Each entry in the self::ARRAY represents a kind of file, identified by the "correct" mimetype as the key.
    // The value for each entry is a map of three keys, ext, mime, and name.
    // ext's value is an array of all of the extensions that the file type can use, with the "correct" one being first.
    // mime's value is an array of all mime types that the file type is known to use, with the current "correct" one being first.
    // name's value is a human-readable name for the file format.


    private const MAP = [
        MimeType::ANI => [
            self::MAP_NAME => "ANI Cursor",
            self::MAP_EXT => [FileExtension::ANI],
            self::MAP_MIME => [MimeType::ANI],
        ],
        MimeType::AVI => [
            self::MAP_NAME => "AVI",
            self::MAP_EXT => [FileExtension::AVI],
            self::MAP_MIME => [MimeType::AVI, 'video/avi', 'video/msvideo'],
        ],
        MimeType::ASF => [
            self::MAP_NAME => "ASF/WMV",
            self::MAP_EXT => [FileExtension::ASF, FileExtension::ASX, FileExtension::WMA, FileExtension::WMV],
            self::MAP_MIME => [MimeType::ASF, MimeType::WMA, MimeType::WMV],
        ],
        MimeType::BMP => [
            self::MAP_NAME => "BMP",
            self::MAP_EXT => [FileExtension::BMP],
            self::MAP_MIME => [MimeType::BMP],
        ],
        MimeType::BZIP => [
            self::MAP_NAME => "BZIP",
            self::MAP_EXT => [FileExtension::BZIP],
            self::MAP_MIME => [MimeType::BZIP],
        ],
        MimeType::BZIP2 => [
            self::MAP_NAME => "BZIP2",
            self::MAP_EXT => [FileExtension::BZIP2],
            self::MAP_MIME => [MimeType::BZIP2],
        ],
        MimeType::COMIC_ZIP => [
            self::MAP_NAME => "CBZ",
            self::MAP_EXT => [FileExtension::CBZ],
            self::MAP_MIME => [MimeType::COMIC_ZIP],
        ],
        MimeType::CSS => [
            self::MAP_NAME => "Cascading Style Sheet",
            self::MAP_EXT => [FileExtension::CSS],
            self::MAP_MIME => [MimeType::CSS],
        ],
        MimeType::CSV => [
            self::MAP_NAME => "CSV",
            self::MAP_EXT => [FileExtension::CSV],
            self::MAP_MIME => [MimeType::CSV],
        ],
        MimeType::FLASH => [
            self::MAP_NAME => "Flash",
            self::MAP_EXT => [FileExtension::FLASH],
            self::MAP_MIME => [MimeType::FLASH],
        ],
        MimeType::FLASH_VIDEO => [
            self::MAP_NAME => "Flash Video",
            self::MAP_EXT => [FileExtension::FLASH_VIDEO],
            self::MAP_MIME => [MimeType::FLASH_VIDEO, 'video/flv'],
        ],
        MimeType::GIF => [
            self::MAP_NAME => "GIF",
            self::MAP_EXT => [FileExtension::GIF],
            self::MAP_MIME => [MimeType::GIF],
        ],
        MimeType::GZIP => [
            self::MAP_NAME => "GZIP",
            self::MAP_EXT => [FileExtension::GZIP],
            self::MAP_MIME => [MimeType::TAR],
        ],
        MimeType::HTML => [
            self::MAP_NAME => "HTML",
            self::MAP_EXT => [FileExtension::HTM, FileExtension::HTML],
            self::MAP_MIME => [MimeType::HTML],
        ],
        MimeType::ICO => [
            self::MAP_NAME => "Icon",
            self::MAP_EXT => [FileExtension::ICO, FileExtension::CUR],
            self::MAP_MIME => [MimeType::ICO, MimeType::ICO_OSX, MimeType::WIN_BITMAP],
        ],
        MimeType::JPEG => [
            self::MAP_NAME => "JPEG",
            self::MAP_EXT => [FileExtension::JPG, FileExtension::JPEG, FileExtension::JFIF, FileExtension::JFI],
            self::MAP_MIME => [MimeType::JPEG],
        ],
        MimeType::JS => [
            self::MAP_NAME => "JavaScript",
            self::MAP_EXT => [FileExtension::JS],
            self::MAP_MIME => [MimeType::JS],
        ],
        MimeType::JSON => [
            self::MAP_NAME => "JSON",
            self::MAP_EXT => [FileExtension::JSON],
            self::MAP_MIME => [MimeType::JSON],
        ],
        MimeType::MKV => [
            self::MAP_NAME => "Matroska",
            self::MAP_EXT => [FileExtension::MKV],
            self::MAP_MIME => [MimeType::MKV],
        ],
        MimeType::MP3 => [
            self::MAP_NAME => "MP3",
            self::MAP_EXT => [FileExtension::MP3],
            self::MAP_MIME => [MimeType::MP3],
        ],
        MimeType::MP4_AUDIO => [
            self::MAP_NAME => "MP4 Audio",
            self::MAP_EXT => [FileExtension::M4A],
            self::MAP_MIME => [MimeType::MP4_AUDIO, "audio/m4a"],
        ],
        MimeType::MP4_VIDEO => [
            self::MAP_NAME => "MP4 Video",
            self::MAP_EXT => [FileExtension::MP4, FileExtension::M4V],
            self::MAP_MIME => [MimeType::MP4_VIDEO, 'video/x-m4v'],
        ],
        MimeType::MPEG => [
            self::MAP_NAME => "MPEG",
            self::MAP_EXT => [FileExtension::MPG, FileExtension::MPEG],
            self::MAP_MIME => [MimeType::MPEG],
        ],
        MimeType::PDF => [
            self::MAP_NAME => "PDF",
            self::MAP_EXT => [FileExtension::PDF],
            self::MAP_MIME => [MimeType::PDF],
        ],
        MimeType::PHP => [
            self::MAP_NAME => "PHP",
            self::MAP_EXT => [FileExtension::PHP, FileExtension::PHP5],
            self::MAP_MIME => [MimeType::PHP],
        ],
        MimeType::PNG => [
            self::MAP_NAME => "PNG",
            self::MAP_EXT => [FileExtension::PNG],
            self::MAP_MIME => [MimeType::PNG],
        ],
        MimeType::PPM => [
            self::MAP_NAME => "Portable Pixel Map",
            self::MAP_EXT => [FileExtension::PPM],
            self::MAP_MIME => [MimeType::PPM],
        ],
        MimeType::PSD => [
            self::MAP_NAME => "PSD",
            self::MAP_EXT => [FileExtension::PSD],
            self::MAP_MIME => [MimeType::PSD],
        ],
        MimeType::OGG_AUDIO => [
            self::MAP_NAME => "Ogg Vorbis",
            self::MAP_EXT => [FileExtension::OGG_AUDIO, FileExtension::OGG],
            self::MAP_MIME => [MimeType::OGG_AUDIO, MimeType::OGG],
        ],
        MimeType::OGG_VIDEO => [
            self::MAP_NAME => "Ogg Theora",
            self::MAP_EXT => [FileExtension::OGG_VIDEO],
            self::MAP_MIME => [MimeType::OGG_VIDEO],
        ],
        MimeType::QUICKTIME => [
            self::MAP_NAME => "Quicktime",
            self::MAP_EXT => [FileExtension::MOV],
            self::MAP_MIME => [MimeType::QUICKTIME],
        ],
        MimeType::RSS => [
            self::MAP_NAME => "RSS",
            self::MAP_EXT => [FileExtension::RSS],
            self::MAP_MIME => [MimeType::RSS],
        ],
        MimeType::SVG => [
            self::MAP_NAME => "SVG",
            self::MAP_EXT => [FileExtension::SVG],
            self::MAP_MIME => [MimeType::SVG],
        ],
        MimeType::TAR => [
            self::MAP_NAME => "TAR",
            self::MAP_EXT => [FileExtension::TAR],
            self::MAP_MIME => [MimeType::TAR],
        ],
        MimeType::TGA => [
            self::MAP_NAME => "TGA",
            self::MAP_EXT => [FileExtension::TGA],
            self::MAP_MIME => [MimeType::TGA, 'image/x-targa'],
        ],
        MimeType::TEXT => [
            self::MAP_NAME => "Text",
            self::MAP_EXT => [FileExtension::TEXT, FileExtension::ASC],
            self::MAP_MIME => [MimeType::TEXT],
        ],
        MimeType::TIFF => [
            self::MAP_NAME => "TIFF",
            self::MAP_EXT => [FileExtension::TIF, FileExtension::TIFF],
            self::MAP_MIME => [MimeType::TIFF],
        ],
        MimeType::WAV => [
            self::MAP_NAME => "Wave",
            self::MAP_EXT => [FileExtension::WAV],
            self::MAP_MIME => [MimeType::WAV],
        ],
        MimeType::WEBM => [
            self::MAP_NAME => "WebM",
            self::MAP_EXT => [FileExtension::WEBM],
            self::MAP_MIME => [MimeType::WEBM],
        ],
        MimeType::WEBP => [
            self::MAP_NAME => "WebP",
            self::MAP_EXT => [FileExtension::WEBP],
            self::MAP_MIME => [MimeType::WEBP, MimeType::WEBP_LOSSLESS],
        ],
        MimeType::XML => [
            self::MAP_NAME => "XML",
            self::MAP_EXT => [FileExtension::XML],
            self::MAP_MIME => [MimeType::XML, MimeType::XML_APPLICATION],
        ],
        MimeType::XSL => [
            self::MAP_NAME => "XSL",
            self::MAP_EXT => [FileExtension::XSL],
            self::MAP_MIME => [MimeType::XSL],
        ],
        MimeType::ZIP => [
            self::MAP_NAME => "ZIP",
            self::MAP_EXT => [FileExtension::ZIP],
            self::MAP_MIME => [MimeType::ZIP],
        ],
    ];

    /**
     * @return array{name: string, ext: string[], mime: string[]}
     */
    public static function get_for_extension(string $ext): ?array
    {
        $ext = strtolower($ext);

        foreach (self::MAP as $key => $value) {
            if (in_array($ext, $value[self::MAP_EXT])) {
                return $value;
            }
        }
        return null;
    }

    /**
     * @return array{name: string, ext: string[], mime: string[]}
     */
    public static function get_for_mime(string $mime): ?array
    {
        $mime = strtolower(MimeType::remove_parameters($mime));

        foreach (self::MAP as $key => $value) {
            if (in_array($mime, $value[self::MAP_MIME])) {
                return $value;
            }
        }
        return null;
    }

    public static function get_name_for_mime(string $mime): ?string
    {
        $data = self::get_for_mime($mime);
        if ($data !== null) {
            return $data[self::MAP_NAME];
        }
        return null;
    }
}
