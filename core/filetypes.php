<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* MIME types and extension information and resolvers                        *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

const EXTENSION_ANI = 'ani';
const EXTENSION_ASC = 'asc';
const EXTENSION_ASF = 'asf';
const EXTENSION_AVI = 'avi';
const EXTENSION_BMP = 'bmp';
const EXTENSION_BZIP = 'bz';
const EXTENSION_BZIP2 = 'bz2';
const EXTENSION_CBR = 'cbr';
const EXTENSION_CBZ = 'cbz';
const EXTENSION_CBT = 'cbt';
const EXTENSION_CBA = 'cbA';
const EXTENSION_CB7 = 'cb7';
const EXTENSION_CSS = 'css';
const EXTENSION_CSV = 'csv';
const EXTENSION_CUR = 'cur';
const EXTENSION_FLASH = 'swf';
const EXTENSION_FLASH_VIDEO = 'flv';
const EXTENSION_GIF = 'gif';
const EXTENSION_GZIP = 'gz';
const EXTENSION_HTML = 'html';
const EXTENSION_HTM = 'htm';
const EXTENSION_ICO = 'ico';
const EXTENSION_JFIF = 'jfif';
const EXTENSION_JFI = 'jfi';
const EXTENSION_JPEG = 'jpeg';
const EXTENSION_JPG = 'jpg';
const EXTENSION_JS = 'js';
const EXTENSION_JSON = 'json';
const EXTENSION_MKV = 'mkv';
const EXTENSION_MP3 = 'mp3';
const EXTENSION_MP4 = 'mp4';
const EXTENSION_M4V = 'm4v';
const EXTENSION_M4A = 'm4a';
const EXTENSION_MPEG = 'mpeg';
const EXTENSION_MPG = 'mpg';
const EXTENSION_OGG = 'ogg';
const EXTENSION_OGG_VIDEO = 'ogv';
const EXTENSION_OGG_AUDIO = 'oga';
const EXTENSION_PDF = 'pdf';
const EXTENSION_PHP = 'php';
const EXTENSION_PHP5 = 'php5';
const EXTENSION_PNG = 'png';
const EXTENSION_PSD = 'psd';
const EXTENSION_MOV = 'mov';
const EXTENSION_RSS = 'rss';
const EXTENSION_SVG = 'svg';
const EXTENSION_TAR = 'tar';
const EXTENSION_TEXT = 'txt';
const EXTENSION_TIFF = 'tiff';
const EXTENSION_TIF = 'tif';
const EXTENSION_WAV = 'wav';
const EXTENSION_WEBM = 'webm';
const EXTENSION_WEBP = 'webp';
const EXTENSION_WMA = 'wma';
const EXTENSION_WMV = 'wmv';
const EXTENSION_XML = 'xml';
const EXTENSION_XSL = 'xsl';
const EXTENSION_ZIP = 'zip';


// Couldn't find a mimetype for ani, so made one up based on it being a riff container
const MIME_TYPE_ANI = 'application/riff+ani';
const MIME_TYPE_ASF = 'video/x-ms-asf';
const MIME_TYPE_AVI = 'video/x-msvideo';
// Went with mime types from http://fileformats.archiveteam.org/wiki/Comic_Book_Archive
const MIME_TYPE_COMIC_ZIP = 'application/vnd.comicbook+zip';
const MIME_TYPE_COMIC_RAR = 'application/vnd.comicbook-rar';
const MIME_TYPE_BMP = 'image/x-ms-bmp';
const MIME_TYPE_BZIP = 'application/x-bzip';
const MIME_TYPE_BZIP2 = 'application/x-bzip2';
const MIME_TYPE_CSS = 'text/css';
const MIME_TYPE_CSV = 'text/csv';
const MIME_TYPE_FLASH = 'application/x-shockwave-flash';
const MIME_TYPE_FLASH_VIDEO = 'video/x-flv';
const MIME_TYPE_GIF = 'image/gif';
const MIME_TYPE_GZIP = 'application/x-gzip';
const MIME_TYPE_HTML = 'text/html';
const MIME_TYPE_ICO = 'image/x-icon';
const MIME_TYPE_JPEG = 'image/jpeg';
const MIME_TYPE_JS = 'text/javascript';
const MIME_TYPE_JSON = 'application/json';
const MIME_TYPE_MKV = 'video/x-matroska';
const MIME_TYPE_MP3 = 'audio/mpeg';
const MIME_TYPE_MP4_AUDIO = 'audio/mp4';
const MIME_TYPE_MP4_VIDEO = 'video/mp4';
const MIME_TYPE_MPEG = 'video/mpeg';
const MIME_TYPE_OCTET_STREAM = 'application/octet-stream';
const MIME_TYPE_OGG = 'application/ogg';
const MIME_TYPE_OGG_VIDEO = 'video/ogg';
const MIME_TYPE_OGG_AUDIO = 'audio/ogg';
const MIME_TYPE_PDF = 'application/pdf';
const MIME_TYPE_PHP = 'text/x-php';
const MIME_TYPE_PNG = 'image/png';
const MIME_TYPE_PSD = 'image/vnd.adobe.photoshop';
const MIME_TYPE_QUICKTIME = 'video/quicktime';
const MIME_TYPE_RSS = 'application/rss+xml';
const MIME_TYPE_SVG = 'image/svg+xml';
const MIME_TYPE_TAR = 'application/x-tar';
const MIME_TYPE_TEXT = 'text/plain';
const MIME_TYPE_TIFF = 'image/tiff';
const MIME_TYPE_WAV = 'audio/x-wav';
const MIME_TYPE_WEBM = 'video/webm';
const MIME_TYPE_WEBP = 'image/webp';
const MIME_TYPE_WIN_BITMAP = 'image/x-win-bitmap';
const MIME_TYPE_XML = 'text/xml';
const MIME_TYPE_XML_APPLICATION = 'application/xml';
const MIME_TYPE_XSL = 'application/xsl+xml';
const MIME_TYPE_ZIP = 'application/zip';

const MIME_TYPE_MAP_NAME = 'name';
const MIME_TYPE_MAP_EXT = 'ext';
const MIME_TYPE_MAP_MIME = 'mime';

// Mime type map. Each entry in the MIME_TYPE_ARRAY represents a kind of file, identified by the "correct" mimetype as the key.
// The value for each entry is a map of twokeys, ext and mime.
// ext's value is an array of all of the extensions that the file type can use, with the "correct" one being first.
// mime's value is an array of all mime types that the file type is known to use, with the current "correct" one being first.

const MIME_TYPE_MAP = [
    MIME_TYPE_ANI => [
        MIME_TYPE_MAP_NAME => "ANI Cursor",
        MIME_TYPE_MAP_EXT => [EXTENSION_ANI],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_ANI],
    ],
    MIME_TYPE_AVI => [
        MIME_TYPE_MAP_NAME => "AVI",
        MIME_TYPE_MAP_EXT => [EXTENSION_AVI],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_AVI,'video/avi','video/msvideo'],
    ],
    MIME_TYPE_ASF => [
        MIME_TYPE_MAP_NAME => "ASF/WMV",
        MIME_TYPE_MAP_EXT => [EXTENSION_ASF,EXTENSION_WMA,EXTENSION_WMV],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_ASF,'audio/x-ms-wma','video/x-ms-wmv'],
    ],
    MIME_TYPE_BMP => [
        MIME_TYPE_MAP_NAME => "BMP",
        MIME_TYPE_MAP_EXT => [EXTENSION_BMP],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_BMP],
    ],
    MIME_TYPE_BZIP => [
        MIME_TYPE_MAP_NAME => "BZIP",
        MIME_TYPE_MAP_EXT => [EXTENSION_BZIP],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_BZIP],
    ],
    MIME_TYPE_BZIP2 => [
        MIME_TYPE_MAP_NAME => "BZIP2",
        MIME_TYPE_MAP_EXT => [EXTENSION_BZIP2],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_BZIP2],
    ],
    MIME_TYPE_COMIC_ZIP => [
        MIME_TYPE_MAP_NAME => "CBZ",
        MIME_TYPE_MAP_EXT => [EXTENSION_CBZ],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_COMIC_ZIP],
    ],
    MIME_TYPE_CSS => [
        MIME_TYPE_MAP_NAME => "Cascading Style Sheet",
        MIME_TYPE_MAP_EXT => [EXTENSION_CSS],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_CSS],
    ],
    MIME_TYPE_CSV => [
        MIME_TYPE_MAP_NAME => "CSV",
        MIME_TYPE_MAP_EXT => [EXTENSION_CSV],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_CSV],
    ],
    MIME_TYPE_FLASH => [
        MIME_TYPE_MAP_NAME => "Flash",
        MIME_TYPE_MAP_EXT => [EXTENSION_FLASH],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_FLASH],
    ],
    MIME_TYPE_FLASH_VIDEO => [
        MIME_TYPE_MAP_NAME => "Flash Video",
        MIME_TYPE_MAP_EXT => [EXTENSION_FLASH_VIDEO],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_FLASH_VIDEO,'video/flv'],
    ],
    MIME_TYPE_GIF => [
        MIME_TYPE_MAP_NAME => "GIF",
        MIME_TYPE_MAP_EXT => [EXTENSION_GIF],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_GIF],
    ],
    MIME_TYPE_GZIP => [
        MIME_TYPE_MAP_NAME => "GZIP",
        MIME_TYPE_MAP_EXT => [EXTENSION_GZIP],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_TAR],
    ],
    MIME_TYPE_HTML => [
        MIME_TYPE_MAP_NAME => "HTML",
        MIME_TYPE_MAP_EXT => [EXTENSION_HTM, EXTENSION_HTML],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_HTML],
    ],
    MIME_TYPE_ICO => [
        MIME_TYPE_MAP_NAME => "Icon",
        MIME_TYPE_MAP_EXT => [EXTENSION_ICO, EXTENSION_CUR],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_ICO, MIME_TYPE_WIN_BITMAP],
    ],
    MIME_TYPE_JPEG => [
        MIME_TYPE_MAP_NAME => "JPEG",
        MIME_TYPE_MAP_EXT => [EXTENSION_JPG, EXTENSION_JPEG, EXTENSION_JFIF, EXTENSION_JFI],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_JPEG],
    ],
    MIME_TYPE_JS => [
        MIME_TYPE_MAP_NAME => "JavaScript",
        MIME_TYPE_MAP_EXT => [EXTENSION_JS],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_JS],
    ],
    MIME_TYPE_JSON => [
        MIME_TYPE_MAP_NAME => "JSON",
        MIME_TYPE_MAP_EXT => [EXTENSION_JSON],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_JSON],
    ],
    MIME_TYPE_MKV => [
        MIME_TYPE_MAP_NAME => "Matroska",
        MIME_TYPE_MAP_EXT => [EXTENSION_MKV],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_MKV],
    ],
    MIME_TYPE_MP3 => [
        MIME_TYPE_MAP_NAME => "MP3",
        MIME_TYPE_MAP_EXT => [EXTENSION_MP3],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_MP3],
    ],
    MIME_TYPE_MP4_AUDIO => [
        MIME_TYPE_MAP_NAME => "MP4 Audio",
        MIME_TYPE_MAP_EXT => [EXTENSION_M4A],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_MP4_AUDIO,"audio/m4a"],
    ],
    MIME_TYPE_MP4_VIDEO => [
        MIME_TYPE_MAP_NAME => "MP4 Video",
        MIME_TYPE_MAP_EXT => [EXTENSION_MP4,EXTENSION_M4V],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_MP4_VIDEO,'video/x-m4v'],
    ],
    MIME_TYPE_MPEG => [
        MIME_TYPE_MAP_NAME => "MPEG",
        MIME_TYPE_MAP_EXT => [EXTENSION_MPG,EXTENSION_MPEG],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_MPEG],
    ],
    MIME_TYPE_PDF => [
        MIME_TYPE_MAP_NAME => "PDF",
        MIME_TYPE_MAP_EXT => [EXTENSION_PDF],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_PDF],
    ],
    MIME_TYPE_PHP => [
        MIME_TYPE_MAP_NAME => "PHP",
        MIME_TYPE_MAP_EXT => [EXTENSION_PHP,EXTENSION_PHP5],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_PHP],
    ],
    MIME_TYPE_PNG => [
        MIME_TYPE_MAP_NAME => "PNG",
        MIME_TYPE_MAP_EXT => [EXTENSION_PNG],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_PNG],
    ],
    MIME_TYPE_PSD => [
        MIME_TYPE_MAP_NAME => "PSD",
        MIME_TYPE_MAP_EXT => [EXTENSION_PSD],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_PSD],
    ],
    MIME_TYPE_OGG_AUDIO => [
        MIME_TYPE_MAP_NAME => "Ogg Vorbis",
        MIME_TYPE_MAP_EXT => [EXTENSION_OGG_AUDIO,EXTENSION_OGG],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_OGG_AUDIO,MIME_TYPE_OGG],
    ],
    MIME_TYPE_OGG_VIDEO => [
        MIME_TYPE_MAP_NAME => "Ogg Theora",
        MIME_TYPE_MAP_EXT => [EXTENSION_OGG_VIDEO],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_OGG_VIDEO],
    ],
    MIME_TYPE_QUICKTIME => [
        MIME_TYPE_MAP_NAME => "Quicktime",
        MIME_TYPE_MAP_EXT => [EXTENSION_MOV],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_QUICKTIME],
    ],
    MIME_TYPE_RSS => [
        MIME_TYPE_MAP_NAME => "RSS",
        MIME_TYPE_MAP_EXT => [EXTENSION_RSS],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_RSS],
    ],
    MIME_TYPE_SVG => [
        MIME_TYPE_MAP_NAME => "SVG",
        MIME_TYPE_MAP_EXT => [EXTENSION_SVG],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_SVG],
    ],
    MIME_TYPE_TAR => [
        MIME_TYPE_MAP_NAME => "TAR",
        MIME_TYPE_MAP_EXT => [EXTENSION_TAR],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_TAR],
    ],
    MIME_TYPE_TEXT => [
        MIME_TYPE_MAP_NAME => "Text",
        MIME_TYPE_MAP_EXT => [EXTENSION_TEXT, EXTENSION_ASC],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_TEXT],
    ],
    MIME_TYPE_TIFF => [
        MIME_TYPE_MAP_NAME => "TIFF",
        MIME_TYPE_MAP_EXT => [EXTENSION_TIF,EXTENSION_TIFF],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_TIFF],
    ],
    MIME_TYPE_WAV => [
        MIME_TYPE_MAP_NAME => "Wave",
        MIME_TYPE_MAP_EXT => [EXTENSION_WAV],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_WAV],
    ],
    MIME_TYPE_WEBM => [
        MIME_TYPE_MAP_NAME => "WebM",
        MIME_TYPE_MAP_EXT => [EXTENSION_WEBM],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_WEBM],
    ],
    MIME_TYPE_WEBP => [
        MIME_TYPE_MAP_NAME => "WebP",
        MIME_TYPE_MAP_EXT => [EXTENSION_WEBP],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_WEBP],
    ],
    MIME_TYPE_XML => [
        MIME_TYPE_MAP_NAME => "XML",
        MIME_TYPE_MAP_EXT => [EXTENSION_XML],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_XML,MIME_TYPE_XML_APPLICATION],
    ],
    MIME_TYPE_XSL => [
        MIME_TYPE_MAP_NAME => "XSL",
        MIME_TYPE_MAP_EXT => [EXTENSION_XSL],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_XSL],
    ],
    MIME_TYPE_ZIP => [
        MIME_TYPE_MAP_NAME => "ZIP",
        MIME_TYPE_MAP_EXT => [EXTENSION_ZIP],
        MIME_TYPE_MAP_MIME => [MIME_TYPE_ZIP],
    ],
];

/**
 * Returns the mimetype that matches the provided extension.
 */
function get_mime_for_extension(string $ext): ?string
{
    $ext = strtolower($ext);

    foreach (MIME_TYPE_MAP as $key=>$value) {
        if (in_array($ext, $value[MIME_TYPE_MAP_EXT])) {
            return $key;
        }
    }
    return null;
}

/**
 * Returns the mimetype for the specified file, trying file inspection methods before falling back on extension-based detection.
 * @param String $file
 * @param String $ext The files extension, for if the current filename somehow lacks the extension
 * @return String The extension that was found.
 */
function get_mime(string $file, string $ext=""): string
{
    if (!file_exists($file)) {
        throw new SCoreException("File not found: ".$file);
    }

    $type = false;

    if (extension_loaded('fileinfo')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        try {
            $type = finfo_file($finfo, $file);
        } finally {
            finfo_close($finfo);
        }
    } elseif (function_exists('mime_content_type')) {
        // If anyone is still using mime_content_type()
        $type = trim(mime_content_type($file));
    }

    if ($type===false || empty($type)) {
        // Checking by extension is our last resort
        if ($ext==null||strlen($ext) == 0) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
        }

        $type = get_mime_for_extension($ext);
    }

    if ($type !== false && strlen($type) > 0) {
        return $type;
    }

    return MIME_TYPE_OCTET_STREAM;
}

/**
 * Returns the file extension associated with the specified mimetype.
 */
function get_extension(?string $mime_type): ?string
{
    if (empty($mime_type)) {
        return null;
    }

    if ($mime_type==MIME_TYPE_OCTET_STREAM) {
        return null;
    }

    foreach (MIME_TYPE_MAP as $key=>$value) {
        if (in_array($mime_type, $value[MIME_TYPE_MAP_MIME])) {
            return $value[MIME_TYPE_MAP_EXT][0];
        }
    }
    return null;
}

/**
 * Returns all of the file extensions associated with the specified mimetype.
 */
function get_all_extension_for_mime(?string $mime_type): array
{
    $output = [];
    if (empty($mime_type)) {
        return $output;
    }

    foreach (MIME_TYPE_MAP as $key=>$value) {
        if (in_array($mime_type, $value[MIME_TYPE_MAP_MIME])) {
            $output = array_merge($output, $value[MIME_TYPE_MAP_EXT]);
        }
    }
    return $output;
}

/**
 * Gets an the extension defined in MIME_TYPE_MAP for a file.
 *
 * @param String $file_path
 * @return String The extension that was found, or null if one can not be found.
 */
function get_extension_for_file(String $file_path): ?String
{
    $mime = get_mime($file_path);
    if (!empty($mime)) {
        if ($mime==MIME_TYPE_OCTET_STREAM) {
            return null;
        } else {
            $ext = get_extension($mime);
        }
        if (!empty($ext)) {
            return $ext;
        }
    }
    return null;
}
