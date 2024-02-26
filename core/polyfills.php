<?php

declare(strict_types=1);

namespace Shimmie2;

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Things which should be in the core API                                    *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * Return the unique elements of an array, case insensitively
 *
 * @param array<string> $array
 * @return list<string>
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
 * from https://uk.php.net/network
 */
function ip_in_range(string $IP, string $CIDR): bool
{
    $parts = explode("/", $CIDR);
    if(count($parts) == 1) {
        $parts[1] = "32";
    }
    list($net, $mask) = $parts;

    $ip_net = ip2long($net);
    $ip_mask = ~((1 << (32 - (int)$mask)) - 1);

    $ip_ip = ip2long($IP);

    $ip_ip_net = $ip_ip & $ip_mask;

    return ($ip_ip_net == $ip_net);
}

/**
 * Delete an entire file heirachy
 */
function deltree(string $dir): void
{
    $di = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::KEY_AS_PATHNAME);
    $ri = new \RecursiveIteratorIterator($di, \RecursiveIteratorIterator::CHILD_FIRST);
    /** @var \SplFileInfo $file */
    foreach ($ri as $filename => $file) {
        $file->isDir() ? rmdir($filename) : unlink($filename);
    }
    rmdir($dir);
}

/**
 * Copy an entire file hierarchy
 *
 * from a comment on https://uk.php.net/copy
 */
function full_copy(string $source, string $target): void
{
    if (is_dir($source)) {
        @mkdir($target);

        $d = dir_ex($source);

        while (true) {
            $entry = $d->read();
            if ($entry === false) {
                break;
            }
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
 *
 * @return string[]
 */
function list_files(string $base, string $_sub_dir = ""): array
{
    assert(is_dir($base));

    $file_list = [];

    $files = [];
    $dir = opendir("$base/$_sub_dir");
    if ($dir === false) {
        throw new UserError("Unable to open directory $base/$_sub_dir");
    }
    try {
        while ($f = readdir($dir)) {
            $files[] = $f;
        }
    } finally {
        closedir($dir);
    }
    sort($files);

    foreach ($files as $filename) {
        $full_path = "$base/$_sub_dir/$filename";

        if (!is_link($full_path) && is_dir($full_path)) {
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

function flush_output(): void
{
    if (!defined("UNITTEST")) {
        @ob_flush();
    }
    flush();
}

function stream_file(string $file, int $start, int $end): void
{
    $fp = fopen($file, 'r');
    if(!$fp) {
        throw new \Exception("Failed to open $file");
    }
    try {
        fseek($fp, $start);
        $buffer = 1024 * 1024;
        while (!feof($fp) && ($p = ftell($fp)) <= $end) {
            if ($p + $buffer > $end) {
                $buffer = $end - $p + 1;
                assert($buffer >= 0);
            }
            echo fread($fp, $buffer);
            flush_output();

            // After flush, we can tell if the client browser has disconnected.
            // This means we can start sending a large file, and if we detect they disappeared
            // then we can just stop and not waste any more resources or bandwidth.
            if (connection_status() != 0) {
                break;
            }
        }
    } finally {
        fclose($fp);
    }
}

# http://www.php.net/manual/en/function.http-parse-headers.php#112917
if (!function_exists('http_parse_headers')) {
    /**
     * @return array<string, string|string[]>
     */
    function http_parse_headers(string $raw_headers): array
    {
        $headers = [];

        foreach (explode("\n", $raw_headers) as $h) {
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
 *
 * @param array<string, mixed> $headers
 */
function find_header(array $headers, string $name): ?string
{
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

/**
 * @return class-string[]
 */
function get_subclasses_of(string $parent): array
{
    $result = [];
    foreach (get_declared_classes() as $class) {
        $rclass = new \ReflectionClass($class);
        if (!$rclass->isAbstract() && is_subclass_of($class, $parent)) {
            $result[] = $class;
        }
    }
    return $result;
}

/**
 * Like glob, with support for matching very long patterns with braces.
 *
 * @return string[]
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
    if (is_null($input)) {
        return "";
    }
    $input = rawurlencode($input);
    return $input;
}

/**
 * Turn all manner of HTML / INI / JS / DB booleans into a PHP one
 */
function bool_escape(mixed $input): bool
{
    /*
     Sometimes, I don't like PHP -- this, is one of those times...
      "a boolean FALSE is not considered a valid boolean value by this function."
     Yay for Got'chas!
     https://php.net/manual/en/filter.filters.validate.php
    */
    if (is_bool($input)) {
        return $input;
    } elseif (is_int($input)) {
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

/**
 * Given a 1-indexed numeric-ish thing, return a zero-indexed
 * number between 0 and $max
 */
function page_number(string $input, ?int $max = null): int
{
    if (!is_numeric($input)) {
        $pageNumber = 0;
    } elseif ($input <= 0) {
        $pageNumber = 0;
    } elseif (!is_null($max) && $input >= $max) {
        $pageNumber = $max - 1;
    } else {
        $pageNumber = $input - 1;
    }
    return (int)$pageNumber;
}

function is_numberish(string $s): bool
{
    return is_numeric($s);
}

/**
 * Because apparently phpstan thinks that if $i is an int, type(-$i) == int|float
 */
function negative_int(int $i): int
{
    return -$i;
}

function clamp(int $val, ?int $min = null, ?int $max = null): int
{
    if (!is_null($min) && $val < $min) {
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

function truncate(string $string, int $limit, string $break = " ", string $pad = "..."): string
{
    $e = "UTF-8";
    $strlen = mb_strlen($string, $e);
    $padlen = mb_strlen($pad, $e);
    assert($limit > $padlen, "Can't truncate to a length less than the padding length");

    // if string is shorter or equal to limit, leave it alone
    if($strlen <= $limit) {
        return $string;
    }

    // if there is a break point between 0 and $limit, truncate to that
    $breakpoint = mb_strrpos($string, $break, -($strlen - $limit + $padlen), $e);
    if ($breakpoint !== false) {
        return mb_substr($string, 0, $breakpoint, $e) . $pad;
    }

    // if there is no break point, cut mid-word
    return mb_substr($string, 0, $limit - $padlen, $e) . $pad;
}

/**
 * Turn a human readable filesize into an integer, eg 1KB -> 1024
 */
function parse_shorthand_int(string $limit): int
{
    if (preg_match('/^([\d\.]+)([tgmk])?b?$/i', (string)$limit, $m)) {
        $value = (float)$m[1];
        if (isset($m[2])) {
            switch (strtolower($m[2])) {
                /** @noinspection PhpMissingBreakStatementInspection */
                case 't':
                    $value *= 1024;  // fall through
                    /** @noinspection PhpMissingBreakStatementInspection */
                    // no break
                case 'g':
                    $value *= 1024;  // fall through
                    /** @noinspection PhpMissingBreakStatementInspection */
                    // no break
                case 'm':
                    $value *= 1024;  // fall through
                    // no break
                case 'k':
                    $value *= 1024;
                    break;
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
function to_shorthand_int(int|float $int): string
{
    assert($int >= 0);

    return match (true) {
        $int >= pow(1024, 4) * 10 => sprintf("%.0fTB", $int / pow(1024, 4)),
        $int >= pow(1024, 4) => sprintf("%.1fTB", $int / pow(1024, 4)),
        $int >= pow(1024, 3) * 10 => sprintf("%.0fGB", $int / pow(1024, 3)),
        $int >= pow(1024, 3) => sprintf("%.1fGB", $int / pow(1024, 3)),
        $int >= pow(1024, 2) * 10 => sprintf("%.0fMB", $int / pow(1024, 2)),
        $int >= pow(1024, 2) => sprintf("%.1fMB", $int / pow(1024, 2)),
        $int >= pow(1024, 1) * 10 => sprintf("%.0fKB", $int / pow(1024, 1)),
        $int >= pow(1024, 1) => sprintf("%.1fKB", $int / pow(1024, 1)),
        default => (string)$int,
    };
}
abstract class TIME_UNITS
{
    public const MILLISECONDS = "ms";
    public const SECONDS = "s";
    public const MINUTES = "m";
    public const HOURS = "h";
    public const DAYS = "d";
    public const YEARS = "y";
    public const CONVERSION = [
        self::MILLISECONDS => 1000,
        self::SECONDS => 60,
        self::MINUTES => 60,
        self::HOURS => 24,
        self::DAYS => 365,
        self::YEARS => PHP_INT_MAX
    ];
}
function format_milliseconds(int $input, string $min_unit = TIME_UNITS::SECONDS): string
{
    $output = "";

    $remainder = $input;

    $found = false;

    foreach (TIME_UNITS::CONVERSION as $unit => $conversion) {
        $count = $remainder % $conversion;
        $remainder = floor($remainder / $conversion);

        if ($found || $unit == $min_unit) {
            $found = true;
        } else {
            continue;
        }

        if ($count == 0 && $remainder < 1) {
            break;
        }
        $output = "$count".$unit." ".$output;
    }

    return trim($output);
}
function parse_to_milliseconds(string $input): int
{
    $output = 0;
    $current_multiplier = 1;

    if (preg_match('/^([0-9]+)$/i', $input, $match)) {
        // If just a number, then we treat it as milliseconds
        $length = $match[0];
        if (is_numeric($length)) {
            $length = floatval($length);
            $output += $length;
        }
    } else {
        foreach (TIME_UNITS::CONVERSION as $unit => $conversion) {
            if (preg_match('/([0-9]+)'.$unit.'/i', $input, $match)) {
                $length = $match[1];
                if (is_numeric($length)) {
                    $length = floatval($length);
                    $output += $length * $current_multiplier;
                }
            }
            $current_multiplier *= $conversion;
        }
    }
    return intval($output);
}

/**
 * Turn a date into a time, a date, an "X minutes ago...", etc
 */
function autodate(string $date, bool $html = true): string
{
    $cpu = date('c', \Safe\strtotime($date));
    $hum = date('F j, Y; H:i', \Safe\strtotime($date));
    return ($html ? "<time datetime='$cpu'>$hum</time>" : $hum);
}

/**
 * Check if a given string is a valid date-time. ( Format: yyyy-mm-dd hh:mm:ss )
 */
function isValidDateTime(string $dateTime): bool
{
    if (preg_match("/^(\d{4})-(\d{2})-(\d{2}) ([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/", $dateTime, $matches)) {
        if (checkdate((int)$matches[2], (int)$matches[3], (int)$matches[1])) {
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
        if (checkdate((int)$matches[2], (int)$matches[3], (int)$matches[1])) {
            return true;
        }
    }

    return false;
}

/**
 * @param array<string, string> $inputs
 * @return array<string, mixed>
 */
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
            // @phpstan-ignore-next-line - phpstan thinks $value can never be empty?
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
            if (!array_key_exists($value, UserClass::$known_classes)) {
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
        } elseif (in_array('date', $flags)) {
            $outputs[$key] = date("Y-m-d H:i:s", \Safe\strtotime(trim($value)));
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
 *
 * @template T
 * @template U
 * @param callable(U):T $callback
 * @param \iterator<U> $iter
 * @return \Generator<T>
 */
function iterator_map(callable $callback, \iterator $iter): \Generator
{
    foreach ($iter as $i) {
        yield call_user_func($callback, $i);
    }
}

/**
 * Perform callback on each item returned by an iterator and combine the result into an array.
 *
 * @template T
 * @template U
 * @param callable(U):T $callback
 * @param \iterator<U> $iter
 * @return array<T>
 */
function iterator_map_to_array(callable $callback, \iterator $iter): array
{
    return iterator_to_array(iterator_map($callback, $iter));
}

function stringer(mixed $s): string
{
    if (is_array($s)) {
        if (isset($s[0])) {
            return "[" . implode(", ", array_map("Shimmie2\stringer", $s)) . "]";
        } else {
            $pairs = [];
            foreach ($s as $k => $v) {
                $pairs[] = "\"$k\"=>" . stringer($v);
            }
            return "[" . implode(", ", $pairs) . "]";
        }
    }
    if (is_null($s)) {
        return "null";
    }
    if (is_string($s)) {
        return "\"$s\"";  // FIXME: handle escaping quotes
    }
    if (is_numeric($s)) {
        return "$s";
    }
    if (is_bool($s)) {
        return $s ? "true" : "false";
    }
    if (method_exists($s, "__toString")) {
        return $s->__toString();
    }
    return "<Unstringable>";
}

/**
 * If a value is in the cache, return it; otherwise, call the callback
 * to generate it and store it in the cache.
 *
 * @template T
 * @param string $key
 * @param callable():T $callback
 * @param int|null $ttl
 * @return T
 */
function cache_get_or_set(string $key, callable $callback, ?int $ttl = null): mixed
{
    global $cache;
    $value = $cache->get($key);
    if ($value === null) {
        $value = $callback();
        $cache->set($key, $value, $ttl);
    }
    return $value;
}
