<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Things which should be in the core API                                    *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * Remove an item from an array
 */
function array_remove(array $array, $to_remove): array
{
    $array = array_unique($array);
    $a2 = [];
    foreach ($array as $existing) {
        if ($existing != $to_remove) {
            $a2[] = $existing;
        }
    }
    return $a2;
}

/**
 * Adds an item to an array.
 *
 * Also removes duplicate values from the array.
 */
function array_add(array $array, $element): array
{
    // Could we just use array_push() ?
    //  http://www.php.net/manual/en/function.array-push.php
    $array[] = $element;
    $array = array_unique($array);
    return $array;
}

/**
 * Return the unique elements of an array, case insensitively
 */
function array_iunique(array $array): array
{
    $ok = [];
    foreach ($array as $element) {
        $found = false;
        foreach ($ok as $existing) {
            if (strtolower($element) == strtolower($existing)) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $ok[] = $element;
        }
    }
    return $ok;
}

/**
 * Figure out if an IP is in a specified range
 *
 * from http://uk.php.net/network
 */
function ip_in_range(string $IP, string $CIDR): bool
{
    list($net, $mask) = explode("/", $CIDR);

    $ip_net = ip2long($net);
    $ip_mask = ~((1 << (32 - $mask)) - 1);

    $ip_ip = ip2long($IP);

    $ip_ip_net = $ip_ip & $ip_mask;

    return ($ip_ip_net == $ip_net);
}

/**
 * Delete an entire file heirachy
 *
 * from a patch by Christian Walde; only intended for use in the
 * "extension manager" extension, but it seems to fit better here
 */
function deltree(string $f): void
{
    //Because Windows (I know, bad excuse)
    if (PHP_OS === 'WINNT') {
        $real = realpath($f);
        $path = realpath('./').'\\'.str_replace('/', '\\', $f);
        if ($path != $real) {
            rmdir($path);
        } else {
            foreach (glob($f.'/*') as $sf) {
                if (is_dir($sf) && !is_link($sf)) {
                    deltree($sf);
                } else {
                    unlink($sf);
                }
            }
            rmdir($f);
        }
    } else {
        if (is_link($f)) {
            unlink($f);
        } elseif (is_dir($f)) {
            foreach (glob($f.'/*') as $sf) {
                if (is_dir($sf) && !is_link($sf)) {
                    deltree($sf);
                } else {
                    unlink($sf);
                }
            }
            rmdir($f);
        }
    }
}

/**
 * Copy an entire file hierarchy
 *
 * from a comment on http://uk.php.net/copy
 */
function full_copy(string $source, string $target): void
{
    if (is_dir($source)) {
        @mkdir($target);

        $d = dir($source);

        while (false !== ($entry = $d->read())) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            $Entry = $source . '/' . $entry;
            if (is_dir($Entry)) {
                full_copy($Entry, $target . '/' . $entry);
                continue;
            }
            copy($Entry, $target . '/' . $entry);
        }
        $d->close();
    } else {
        copy($source, $target);
    }
}

/**
 * Return a list of all the regular files in a directory and subdirectories
 */
function list_files(string $base, string $_sub_dir=""): array
{
    assert(is_dir($base));

    $file_list = [];

    $files = [];
    $dir = opendir("$base/$_sub_dir");
    while ($f = readdir($dir)) {
        $files[] = $f;
    }
    closedir($dir);
    sort($files);

    foreach ($files as $filename) {
        $full_path = "$base/$_sub_dir/$filename";

        if (is_link($full_path)) {
            // ignore
        } elseif (is_dir($full_path)) {
            if (!($filename == "." || $filename == "..")) {
                //subdirectory found
                $file_list = array_merge(
                    $file_list,
                    list_files($base, "$_sub_dir/$filename")
                );
            }
        } else {
            $full_path = str_replace("//", "/", $full_path);
            $file_list[] = $full_path;
        }
    }

    return $file_list;
}

if (!function_exists('http_parse_headers')) { #http://www.php.net/manual/en/function.http-parse-headers.php#112917

    /**
     * #return string[]
     */
    function http_parse_headers(string $raw_headers): array
    {
        $headers = []; // $headers = [];

        foreach (explode("\n", $raw_headers) as $i => $h) {
            $h = explode(':', $h, 2);

            if (isset($h[1])) {
                if (!isset($headers[$h[0]])) {
                    $headers[$h[0]] = trim($h[1]);
                } elseif (is_array($headers[$h[0]])) {
                    $tmp = array_merge($headers[$h[0]], [trim($h[1])]);
                    $headers[$h[0]] = $tmp;
                } else {
                    $tmp = array_merge([$headers[$h[0]]], [trim($h[1])]);
                    $headers[$h[0]] = $tmp;
                }
            }
        }
        return $headers;
    }
}

/**
 * HTTP Headers can sometimes be lowercase which will cause issues.
 * In cases like these, we need to make sure to check for them if the camelcase version does not exist.
 */
function findHeader(array $headers, string $name): ?string
{
    if (!is_array($headers)) {
        return null;
    }

    $header = null;

    if (array_key_exists($name, $headers)) {
        $header = $headers[$name];
    } else {
        $headers = array_change_key_case($headers); // convert all to lower case.
        $lc_name = strtolower($name);

        if (array_key_exists($lc_name, $headers)) {
            $header = $headers[$lc_name];
        }
    }

    return $header;
}

if (!function_exists('mb_strlen')) {
    // TODO: we should warn the admin that they are missing multibyte support
    function mb_strlen($str, $encoding)
    {
        return strlen($str);
    }
    function mb_internal_encoding($encoding)
    {
    }
    function mb_strtolower($str)
    {
        return strtolower($str);
    }
}

const MIME_TYPE_MAP = [
    'jpg' => 'image/jpeg',
    'gif' => 'image/gif',
    'png' => 'image/png',
    'tif' => 'image/tiff',
    'tiff' => 'image/tiff',
    'ico' => 'image/x-icon',
    'swf' => 'application/x-shockwave-flash',
    'flv' => 'video/x-flv',
    'svg' => 'image/svg+xml',
    'pdf' => 'application/pdf',
    'zip' => 'application/zip',
    'gz' => 'application/x-gzip',
    'tar' => 'application/x-tar',
    'bz' => 'application/x-bzip',
    'bz2' => 'application/x-bzip2',
    'txt' => 'text/plain',
    'asc' => 'text/plain',
    'htm' => 'text/html',
    'html' => 'text/html',
    'css' => 'text/css',
    'js' => 'text/javascript',
    'xml' => 'text/xml',
    'xsl' => 'application/xsl+xml',
    'ogg' => 'application/ogg',
    'mp3' => 'audio/mpeg',
    'wav' => 'audio/x-wav',
    'avi' => 'video/x-msvideo',
    'mpg' => 'video/mpeg',
    'mpeg' => 'video/mpeg',
    'mov' => 'video/quicktime',
    'flv' => 'video/x-flv',
    'php' => 'text/x-php',
    'mp4' => 'video/mp4',
    'ogv' => 'video/ogg',
    'webm' => 'video/webm',
    'webp' => 'image/webp',
    'bmp' =>'image/x-ms-bmp',
    'psd' => 'image/vnd.adobe.photoshop',
    'mkv' => 'video/x-matroska'
];

/**
 * Get MIME type for file
 *
 * The contents of this function are taken from the __getMimeType() function
 * from the "Amazon S3 PHP class" which is Copyright (c) 2008, Donovan Schönknecht
 * and released under the 'Simplified BSD License'.
 */
function getMimeType(string $file, string $ext=""): string
{
    // Static extension lookup
    $ext = strtolower($ext);

    if (array_key_exists($ext, MIME_TYPE_MAP)) {
        return MIME_TYPE_MAP[$ext];
    }

    $type = false;
    // Fileinfo documentation says fileinfo_open() will use the
    // MAGIC env var for the magic file
    if (extension_loaded('fileinfo') && isset($_ENV['MAGIC']) &&
        ($finfo = finfo_open(FILEINFO_MIME, $_ENV['MAGIC'])) !== false) {
        if (($type = finfo_file($finfo, $file)) !== false) {
            // Remove the charset and grab the last content-type
            $type = explode(' ', str_replace('; charset=', ';charset=', $type));
            $type = array_pop($type);
            $type = explode(';', $type);
            $type = trim(array_shift($type));
        }
        finfo_close($finfo);

    // If anyone is still using mime_content_type()
    } elseif (function_exists('mime_content_type')) {
        $type = trim(mime_content_type($file));
    }

    if ($type !== false && strlen($type) > 0) {
        return $type;
    }

    return 'application/octet-stream';
}

function get_extension(?string $mime_type): ?string
{
    if (empty($mime_type)) {
        return null;
    }

    $ext = array_search($mime_type, MIME_TYPE_MAP);
    return ($ext ? $ext : null);
}

/**
 * Like glob, with support for matching very long patterns with braces.
 */
function zglob(string $pattern): array
{
    $results = [];
    if (preg_match('/(.*)\{(.*)\}(.*)/', $pattern, $matches)) {
        $braced = explode(",", $matches[2]);
        foreach ($braced as $b) {
            $sub_pattern = $matches[1].$b.$matches[3];
            $results = array_merge($results, zglob($sub_pattern));
        }
        return $results;
    } else {
        $r = glob($pattern);
        if ($r) {
            return $r;
        } else {
            return [];
        }
    }
}

/**
 * Figure out the path to the shimmie install directory.
 *
 * eg if shimmie is visible at http://foo.com/gallery, this
 * function should return /gallery
 *
 * PHP really, really sucks.
 */
function get_base_href(): string
{
    if (defined("BASE_HREF")) {
        return BASE_HREF;
    }
    $possible_vars = ['SCRIPT_NAME', 'PHP_SELF', 'PATH_INFO', 'ORIG_PATH_INFO'];
    $ok_var = null;
    foreach ($possible_vars as $var) {
        if (isset($_SERVER[$var]) && substr($_SERVER[$var], -4) === '.php') {
            $ok_var = $_SERVER[$var];
            break;
        }
    }
    assert(!empty($ok_var));
    $dir = dirname($ok_var);
    $dir = str_replace("\\", "/", $dir);
    $dir = str_replace("//", "/", $dir);
    $dir = rtrim($dir, "/");
    return $dir;
}

function startsWith(string $haystack, string $needle): bool
{
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}

function endsWith(string $haystack, string $needle): bool
{
    $length = strlen($needle);
    $start  = $length * -1; //negative
    return (substr($haystack, $start) === $needle);
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Input / Output Sanitising                                                 *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * Make some data safe for printing into HTML
 */
function html_escape(?string $input): string
{
    if (is_null($input)) {
        return "";
    }
    return htmlentities($input, ENT_QUOTES, "UTF-8");
}

/**
 * Unescape data that was made safe for printing into HTML
 */
function html_unescape(string $input): string
{
    return html_entity_decode($input, ENT_QUOTES, "UTF-8");
}

/**
 * Make sure some data is safe to be used in integer context
 */
function int_escape(?string $input): int
{
    /*
     Side note, Casting to an integer is FASTER than using intval.
     http://hakre.wordpress.com/2010/05/13/php-casting-vs-intval/
    */
    if (is_null($input)) {
        return 0;
    }
    return (int)$input;
}

/**
 * Make sure some data is safe to be used in URL context
 */
function url_escape(?string $input): string
{
    /*
        Shish: I have a feeling that these three lines are important, possibly for searching for tags with slashes in them like fate/stay_night
        green-ponies: indeed~

    $input = str_replace('^', '^^', $input);
    $input = str_replace('/', '^s', $input);
    $input = str_replace('\\', '^b', $input);

    /* The function idn_to_ascii is used to support Unicode domains / URLs as well.
       See here for more:  http://php.net/manual/en/function.filter-var.php
       However, it is only supported by PHP version 5.3 and up

    if (function_exists('idn_to_ascii')) {
            return filter_var(idn_to_ascii($input), FILTER_SANITIZE_URL);
    } else {
            return filter_var($input, FILTER_SANITIZE_URL);
    }
    */
    if (is_null($input)) {
        return "";
    }
    $input = str_replace('^', '^^', $input);
    $input = str_replace('/', '^s', $input);
    $input = str_replace('\\', '^b', $input);
    $input = rawurlencode($input);
    return $input;
}

/**
 * Make sure some data is safe to be used in SQL context
 */
function sql_escape(string $input): string
{
    global $database;
    return $database->escape($input);
}


/**
 * Turn all manner of HTML / INI / JS / DB booleans into a PHP one
 */
function bool_escape($input): bool
{
    /*
     Sometimes, I don't like PHP -- this, is one of those times...
      "a boolean FALSE is not considered a valid boolean value by this function."
     Yay for Got'chas!
     http://php.net/manual/en/filter.filters.validate.php
    */
    if (is_bool($input)) {
        return $input;
    } elseif (is_numeric($input)) {
        return ($input === 1);
    } else {
        $value = filter_var($input, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if (!is_null($value)) {
            return $value;
        } else {
            $input = strtolower(trim($input));
            return (
                $input === "y" ||
                $input === "yes" ||
                $input === "t" ||
                $input === "true" ||
                $input === "on" ||
                $input === "1"
            );
        }
    }
}

/**
 * Some functions require a callback function for escaping,
 * but we might not want to alter the data
 */
function no_escape(string $input): string
{
    return $input;
}

function clamp(?int $val, ?int $min=null, ?int $max=null): int
{
    if (!is_numeric($val) || (!is_null($min) && $val < $min)) {
        $val = $min;
    }
    if (!is_null($max) && $val > $max) {
        $val = $max;
    }
    if (!is_null($min) && !is_null($max)) {
        assert($val >= $min && $val <= $max, "$min <= $val <= $max");
    }
    return $val;
}

function xml_tag(string $name, array $attrs=[], array $children=[]): string
{
    $xml = "<$name ";
    foreach ($attrs as $k => $v) {
        $xv = str_replace('&#039;', '&apos;', htmlspecialchars($v, ENT_QUOTES));
        $xml .= "$k=\"$xv\" ";
    }
    if (count($children) > 0) {
        $xml .= ">\n";
        foreach ($children as $child) {
            $xml .= xml_tag($child);
        }
        $xml .= "</$name>\n";
    } else {
        $xml .= "/>\n";
    }
    return $xml;
}

/**
 * Original PHP code by Chirp Internet: www.chirp.com.au
 * Please acknowledge use of this code by including this header.
 */
function truncate(string $string, int $limit, string $break=" ", string $pad="..."): string
{
    // return with no change if string is shorter than $limit
    if (strlen($string) <= $limit) {
        return $string;
    }

    // is $break present between $limit and the end of the string?
    if (false !== ($breakpoint = strpos($string, $break, $limit))) {
        if ($breakpoint < strlen($string) - 1) {
            $string = substr($string, 0, $breakpoint) . $pad;
        }
    }

    return $string;
}

/**
 * Turn a human readable filesize into an integer, eg 1KB -> 1024
 */
function parse_shorthand_int(string $limit): int
{
    if (preg_match('/^([\d\.]+)([gmk])?b?$/i', (string)$limit, $m)) {
        $value = $m[1];
        if (isset($m[2])) {
            switch (strtolower($m[2])) {
                /** @noinspection PhpMissingBreakStatementInspection */
                case 'g': $value *= 1024;  // fall through
                /** @noinspection PhpMissingBreakStatementInspection */
                // no break
                case 'm': $value *= 1024;  // fall through
                /** @noinspection PhpMissingBreakStatementInspection */
                // no break
                case 'k': $value *= 1024; break;
                default: $value = -1;
            }
        }
        return (int)$value;
    } else {
        return -1;
    }
}

/**
 * Turn an integer into a human readable filesize, eg 1024 -> 1KB
 */
function to_shorthand_int(int $int): string
{
    assert($int >= 0);

    if ($int >= pow(1024, 3)) {
        return sprintf("%.1fGB", $int / pow(1024, 3));
    } elseif ($int >= pow(1024, 2)) {
        return sprintf("%.1fMB", $int / pow(1024, 2));
    } elseif ($int >= 1024) {
        return sprintf("%.1fKB", $int / 1024);
    } else {
        return (string)$int;
    }
}


/**
 * Turn a date into a time, a date, an "X minutes ago...", etc
 */
function autodate(string $date, bool $html=true): string
{
    $cpu = date('c', strtotime($date));
    $hum = date('F j, Y; H:i', strtotime($date));
    return ($html ? "<time datetime='$cpu'>$hum</time>" : $hum);
}

/**
 * Check if a given string is a valid date-time. ( Format: yyyy-mm-dd hh:mm:ss )
 */
function isValidDateTime(string $dateTime): bool
{
    if (preg_match("/^(\d{4})-(\d{2})-(\d{2}) ([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/", $dateTime, $matches)) {
        if (checkdate($matches[2], $matches[3], $matches[1])) {
            return true;
        }
    }

    return false;
}

/**
 * Check if a given string is a valid date. ( Format: yyyy-mm-dd )
 */
function isValidDate(string $date): bool
{
    if (preg_match("/^(\d{4})-(\d{2})-(\d{2})$/", $date, $matches)) {
        // checkdate wants (month, day, year)
        if (checkdate($matches[2], $matches[3], $matches[1])) {
            return true;
        }
    }

    return false;
}

function validate_input(array $inputs): array
{
    $outputs = [];

    foreach ($inputs as $key => $validations) {
        $flags = explode(',', $validations);

        if (in_array('bool', $flags) && !isset($_POST[$key])) {
            $_POST[$key] = 'off';
        }

        if (in_array('optional', $flags)) {
            if (!isset($_POST[$key]) || trim($_POST[$key]) == "") {
                $outputs[$key] = null;
                continue;
            }
        }
        if (!isset($_POST[$key]) || trim($_POST[$key]) == "") {
            throw new InvalidInput("Input '$key' not set");
        }

        $value = trim($_POST[$key]);

        if (in_array('user_id', $flags)) {
            $id = int_escape($value);
            if (in_array('exists', $flags)) {
                if (is_null(User::by_id($id))) {
                    throw new InvalidInput("User #$id does not exist");
                }
            }
            $outputs[$key] = $id;
        } elseif (in_array('user_name', $flags)) {
            if (strlen($value) < 1) {
                throw new InvalidInput("Username must be at least 1 character");
            } elseif (!preg_match('/^[a-zA-Z0-9-_]+$/', $value)) {
                throw new InvalidInput(
                    "Username contains invalid characters. Allowed characters are ".
                    "letters, numbers, dash, and underscore"
                );
            }
            $outputs[$key] = $value;
        } elseif (in_array('user_class', $flags)) {
            global $_shm_user_classes;
            if (!array_key_exists($value, $_shm_user_classes)) {
                throw new InvalidInput("Invalid user class: ".html_escape($value));
            }
            $outputs[$key] = $value;
        } elseif (in_array('email', $flags)) {
            $outputs[$key] = trim($value);
        } elseif (in_array('password', $flags)) {
            $outputs[$key] = $value;
        } elseif (in_array('int', $flags)) {
            $value = trim($value);
            if (empty($value) || !is_numeric($value)) {
                throw new InvalidInput("Invalid int: ".html_escape($value));
            }
            $outputs[$key] = (int)$value;
        } elseif (in_array('bool', $flags)) {
            $outputs[$key] = bool_escape($value);
        } elseif (in_array('string', $flags)) {
            if (in_array('trim', $flags)) {
                $value = trim($value);
            }
            if (in_array('lower', $flags)) {
                $value = strtolower($value);
            }
            if (in_array('not-empty', $flags)) {
                throw new InvalidInput("$key must not be blank");
            }
            if (in_array('nullify', $flags)) {
                if (empty($value)) {
                    $value = null;
                }
            }
            $outputs[$key] = $value;
        } else {
            throw new InvalidInput("Unknown validation '$validations'");
        }
    }

    return $outputs;
}

/**
 * Translates all possible directory separators to the appropriate one for the current system,
 * and removes any duplicate separators.
 */
function sanitize_path(string $path): string
{
    return preg_replace('|[\\\\/]+|S', DIRECTORY_SEPARATOR, $path);
}

/**
 * Combines all path segments specified, ensuring no duplicate separators occur,
 * as well as converting all possible separators to the one appropriate for the current system.
 */
function join_path(string ...$paths): string
{
    $output = "";
    foreach ($paths as $path) {
        if (empty($path)) {
            continue;
        }
        $path = sanitize_path($path);
        if (empty($output)) {
            $output = $path;
        } else {
            $output = rtrim($output, DIRECTORY_SEPARATOR);
            $path = ltrim($path, DIRECTORY_SEPARATOR);
            $output .= DIRECTORY_SEPARATOR . $path;
        }
    }
    return $output;
}

/**
 * Perform callback on each item returned by an iterator.
 */
function iterator_map(callable $callback, iterator $iter): Generator
{
    foreach ($iter as $i) {
        yield call_user_func($callback, $i);
    }
}

/**
 * Perform callback on each item returned by an iterator and combine the result into an array.
 */
function iterator_map_to_array(callable $callback, iterator $iter): array
{
    return iterator_to_array(iterator_map($callback, $iter));
}

function get_class_from_file(string $file): string
{
    $fp = fopen($file, 'r');
    $class = $buffer = '';
    $i = 0;
    while (!$class) {
        if (feof($fp)) {
            break;
        }

        $buffer .= fread($fp, 512);
        $tokens = token_get_all($buffer);

        if (strpos($buffer, '{') === false) {
            continue;
        }

        for (;$i<count($tokens);$i++) {
            if ($tokens[$i][0] === T_CLASS) {
                for ($j=$i+1;$j<count($tokens);$j++) {
                    if ($tokens[$j] === '{') {
                        $class = $tokens[$i+2][1];
                    }
                }
            }
        }
    }
    return $class;
}
