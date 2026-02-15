<?php

declare(strict_types=1);

namespace Shimmie2;

use Psr\SimpleCache\CacheInterface;

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Things which should be in the core API                                    *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

///////////////////////////////////////////////////////////////////////
// array things

/**
 * Return the unique elements of an array, case insensitively
 *
 * @template T of string
 * @param array<T> $array
 * @return list<T>
 */
function array_iunique(array $array): array
{
    $ok = [];
    foreach ($array as $element) {
        $found = false;
        foreach ($ok as $existing) {
            if (strtolower($element) === strtolower($existing)) {
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
 * override \in_array with \Shimmie2\in_array which always works in strict mode
 * @param array<int|string, mixed> $haystack
 */
function in_array(mixed $needle, array $haystack): bool
{
    return \in_array($needle, $haystack, true);
}

/**
 * Perform callback on each item returned by an iterator.
 *
 * @template T
 * @template U
 * @param callable(U):T $callback
 * @param \Iterator<U> $iter
 * @return \Generator<T>
 */
function iterator_map(callable $callback, \Iterator $iter): \Generator
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
 * @param \Iterator<U> $iter
 * @return array<T>
 */
function iterator_map_to_array(callable $callback, \Iterator $iter): array
{
    return iterator_to_array(iterator_map($callback, $iter));
}

///////////////////////////////////////////////////////////////////////
// Input / Output Sanitising

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
function bool_escape(string|bool|int $input): bool
{
    if (is_bool($input)) {
        return $input;
    } elseif (is_int($input)) {
        return $input === 1;
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

/**
 * If X is empty, return null, else return X
 * @template T
 * @param T $x
 * @return T|null
 */
function nullify(mixed $x): mixed
{
    if (empty($x)) {
        return null;
    }
    return $x;
}

function truncate(string $string, int $limit, string $break = " ", string $pad = "..."): string
{
    $e = "UTF-8";
    $padlen = mb_strlen($pad, $e);
    assert($limit > $padlen, "Can't truncate to a length less than the padding length");

    /*
     * Truncate tentatively, and then check if the lengths stayed the same.
     *
     * This approach is faster than calling mb_strlen and checking against the limit, as mb_strlen
     * has O(n) cost which will slow down significantly for long texts. mb_substr also has O(n)
     * cost, but bounded to $limit, which is usually small.
     *
     * strlen has O(1) cost so it's the fastest way to check if anything happened.
     */
    $truncated = mb_substr($string, 0, $limit, $e);
    if (strlen($truncated) === strlen($string)) {
        return $string;
    }

    // We've already determined it is too long. Now truncate again to add space for the pad text.
    $truncated = mb_substr($truncated, 0, $limit - $padlen, $e);

    /*
     * If there is a break point, truncate to that.
     *
     * We do not need to use the slower mb_* functions for this - if $break is a well-formed UTF-8
     * sequence, this will always result in properly formed UTF-8.
     */
    $breakpoint = strrpos($truncated, $break);
    if ($breakpoint !== false) {
        $truncated = substr($truncated, 0, $breakpoint);
    }

    return $truncated . $pad;
}

///////////////////////////////////////////////////////////////////////
// Math things

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

/**
 * Turn a human readable filesize into an integer, eg 1KB -> 1024
 */
function parse_shorthand_int(string $limit): ?int
{
    if (\Safe\preg_match('/^(-?[\d\.]+)([tgmk])?b?$/i', $limit, $m)) {
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
        return null;
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

///////////////////////////////////////////////////////////////////////
// Date / time things

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

        if ($found || $unit === $min_unit) {
            $found = true;
        } else {
            continue;
        }

        if ($count === 0 && $remainder < 1) {
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

    if (\Safe\preg_match('/^([0-9]+)$/i', $input, $match)) {
        // If just a number, then we treat it as milliseconds
        $length = $match[0];
        if (is_numeric($length)) {
            $length = floatval($length);
            $output += $length;
        }
    } else {
        foreach (TIME_UNITS::CONVERSION as $unit => $conversion) {
            if (\Safe\preg_match('/([0-9]+)'.$unit.'/i', $input, $match)) {
                $length = (float)$match[1];
                $output += $length * $current_multiplier;
            }
            $current_multiplier *= $conversion;
        }
    }
    return intval($output);
}

/**
 * Check if a given string is a valid date. ( Format: yyyy-mm-dd )
 */
function is_valid_date(string $date): bool
{
    if (\Safe\preg_match("/^(\d{4})-(\d{2})-(\d{2})$/", $date, $matches)) {
        // checkdate wants (month, day, year)
        if (checkdate((int)$matches[2], (int)$matches[3], (int)$matches[1])) {
            return true;
        }
    }

    return false;
}

///////////////////////////////////////////////////////////////////////
// Misc things

function version_check(string $min_php): void
{
    if (version_compare(phpversion(), $min_php, ">=") === false) {
        die(
            "PHP " . phpversion(). " Not Supported: " .
            "Shimmie does not support versions of PHP lower than $min_php."
        );
    }
}

/*
 * A small number of PHP-sanity things (eg don't silently ignore errors) to
 * be included right at the very start of index.php and tests/bootstrap.php
 */
function sanitize_php(): void
{
    # ini_set('zend.assertions', '1');  // generate assertions
    ini_set('assert.exception', '1');  // throw exceptions when failed
    set_error_handler(function ($errNo, $errStr) {
        // Should we turn ALL notices into errors? PHP allows a lot of
        // terrible things to happen by default...
        if (str_starts_with($errStr, 'Use of undefined constant ')) {
            throw new \Exception("PHP Error#$errNo: $errStr");
        } else {
            return false;
        }
    });

    ob_start();

    if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
        if (isset($_SERVER['REMOTE_ADDR'])) {
            die("CLI with remote addr? Confused, not taking the risk.");
        }
        $_SERVER['REMOTE_ADDR'] = "0.0.0.0";
        $_SERVER['HTTP_HOST'] = "cli-command";
    }
}

function flush_output(): void
{
    if (!defined("UNITTEST")) {
        @ob_flush();
    }
    flush();
}

function stringer(mixed $s): string
{
    if (is_array($s)) {
        if (isset($s[0])) {
            return "[" . implode(", ", array_map(stringer(...), $s)) . "]";
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
    $value = Ctx::$cache->get($key);
    if ($value === null) {
        $span = Ctx::$tracer->startSpan("Cache Populate", ["key" => $key]);
        $value = $callback();
        $span->end();
        Ctx::$cache->set($key, $value, $ttl);
    }
    return $value;
}

/**
 * Load a PSR-7 compatible cache by URL, eg "redis://127.0.0.1""
 */
function load_cache(?string $dsn): CacheInterface
{
    $c = null;
    if ($dsn && !isset($_GET['DISABLE_CACHE'])) {
        $url = parse_url($dsn);
        if ($url) {
            if ($url['scheme'] === "memcached" || $url['scheme'] === "memcache") {
                $memcache = new \Memcached();
                $memcache->addServer($url['host'], $url['port']);
                $c = new \Sabre\Cache\Memcached($memcache);
            } elseif ($url['scheme'] === "apc") {
                $c = new \Sabre\Cache\Apcu();
            } elseif ($url['scheme'] === "redis") {
                $redis = new \Predis\Client([
                    'scheme' => 'tcp',
                    'host' => $url['host'] ?? "127.0.0.1",
                    'port' => $url['port'] ?? 6379,
                    'username' => $url['user'] ?? null,
                    'password' => $url['pass'] ?? null,
                ], ['prefix' => 'shm:']);
                $c = new \Naroga\RedisCache\Redis($redis);
            }
        }
    }
    if (is_null($c)) {
        $c = new \Sabre\Cache\Memory();
    }
    return new TracingCache($c, Ctx::$tracer);
}

/**
 * @template T
 * @param T|false $x
 * @return T
 */
function false_throws(mixed $x, ?callable $errorgen = null): mixed
{
    if ($x === false) {
        $msg = "Unexpected false";
        if ($errorgen) {
            $msg = $errorgen();
        }
        throw new \Exception($msg);
    }
    return $x;
}
