<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * Generic parent class for all events.
 *
 * An event is anything that can be passed around via send_event($blah)
 */
abstract class Event
{
    public bool $stop_processing = false;

    public function __construct()
    {
    }

    public function __toString(): string
    {
        return var_export($this, true);
    }
}


/**
 * A wake-up call for extensions. Upon recieving an InitExtEvent an extension
 * should check that it's database tables are there and install them if not,
 * and set any defaults with Config::set_default_int() and such.
 *
 * This event is sent before $user is set to anything
 */
class InitExtEvent extends Event
{
}


/**
 * A signal that a page has been requested.
 *
 * User requests /view/42 -> an event is generated with $args = array("view",
 * "42"); when an event handler asks $event->page_matches("view"), it returns
 * true and ignores the matched part, such that $event->count_args() = 1 and
 * $event->get_arg(0) = "42"
 */
class PageRequestEvent extends Event
{
    public string $method;
    public string $path;
    /** @var array<string, string|string[]> */
    public array $GET;
    /** @var array<string, string|string[]> */
    public array $POST;

    /**
     * @var string[]
     */
    public array $args;
    public int $arg_count;
    public int $part_count;
    public bool $is_authed;

    /**
     * @param string $method The HTTP method used to make the request
     * @param string $path The path of the request
     * @param array<string, string|string[]> $get The GET parameters
     * @param array<string, string|string[]> $post The POST parameters
     */
    public function __construct(string $method, string $path, array $get, array $post)
    {
        global $user;
        parent::__construct();
        global $config;

        $this->method = $method;

        // if we're looking at the root of the install,
        // use the default front page
        if ($path == "") {
            $path = $config->get_string(SetupConfig::FRONT_PAGE);
        }
        $this->path = $path;
        $this->GET = $get;
        $this->POST = $post;
        $this->is_authed = $user->check_auth_token();

        // break the path into parts
        $args = explode('/', $path);

        $this->args = $args;
        $this->arg_count = count($args);
    }

    public function get_GET(string $key): ?string
    {
        if(array_key_exists($key, $this->GET)) {
            if(is_array($this->GET[$key])) {
                throw new SCoreException("GET parameter {$key} is an array, expected single value");
            }
            return $this->GET[$key];
        } else {
            return null;
        }
    }

    public function req_GET(string $key): string
    {
        $value = $this->get_GET($key);
        if($value === null) {
            throw new UserErrorException("Missing GET parameter {$key}");
        }
        return $value;
    }

    public function get_POST(string $key): ?string
    {
        if(array_key_exists($key, $this->POST)) {
            if(is_array($this->POST[$key])) {
                throw new SCoreException("POST parameter {$key} is an array, expected single value");
            }
            return $this->POST[$key];
        } else {
            return null;
        }
    }

    public function req_POST(string $key): string
    {
        $value = $this->get_POST($key);
        if($value === null) {
            throw new UserErrorException("Missing POST parameter {$key}");
        }
        return $value;
    }

    /**
     * @return string[]|null
     */
    public function get_POST_array(string $key): ?array
    {
        if(array_key_exists($key, $this->POST)) {
            if(!is_array($this->POST[$key])) {
                throw new SCoreException("POST parameter {$key} is a single value, expected array");
            }
            return $this->POST[$key];
        } else {
            return null;
        }
    }

    /**
     * @return string[]
     */
    public function req_POST_array(string $key): array
    {
        $value = $this->get_POST_array($key);
        if($value === null) {
            throw new UserErrorException("Missing POST parameter {$key}");
        }
        return $value;
    }

    /**
     * Test if the requested path matches a given pattern.
     *
     * If it matches, store the remaining path elements in $args
     */
    public function page_matches(string $name, ?string $method = null, ?bool $authed = null, ?string $permission = null): bool
    {
        global $user;

        assert($method === null || in_array($method, ["GET", "POST", "OPTIONS"]));
        $authed = $authed ?? $method == "POST";

        // method check is fast so do that first
        if($method !== null && $this->method !== $method) {
            return false;
        }

        // check if the path matches
        $parts = explode("/", $name);
        $this->part_count = count($parts);
        if ($this->part_count > $this->arg_count) {
            return false;
        }
        for ($i = 0; $i < $this->part_count; $i++) {
            if ($parts[$i] != $this->args[$i]) {
                return false;
            }
        }

        // if we matched the method and the path, but the page requires
        // authentication and the user is not authenticated, then complain
        if($authed && $this->is_authed === false) {
            throw new PermissionDeniedException("Permission Denied");
        }
        if($permission !== null && !$user->can($permission)) {
            throw new PermissionDeniedException("Permission Denied");
        }

        return true;
    }

    /**
     * Get the n th argument of the page request (if it exists.)
     */
    public function get_arg(int $n): string
    {
        $offset = $this->part_count + $n;
        if ($offset >= 0 && $offset < $this->arg_count) {
            return rawurldecode($this->args[$offset]);
        } else {
            $nm1 = $this->arg_count - 1;
            throw new UserErrorException("Requested an invalid page argument {$offset} / {$nm1}");
        }
    }

    /**
     * If page arg $n is set, then treat that as a 1-indexed page number
     * and return a 0-indexed page number less than $max; else return 0
     */
    public function try_page_num(int $n, ?int $max = null): int
    {
        if ($this->count_args() > $n) {
            $i = $this->get_arg($n);
            if (is_numberish($i) && int_escape($i) > 0) {
                return page_number($i, $max);
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    /**
     * Returns the number of arguments the page request has.
     */
    public function count_args(): int
    {
        return $this->arg_count - $this->part_count;
    }

    /*
     * Many things use these functions
     */

    /**
     * @return string[]
     */
    public function get_search_terms(): array
    {
        $search_terms = [];
        if ($this->count_args() === 2) {
            $str = $this->get_arg(0);

            // decode legacy caret-encoding just in case
            // somebody has bookmarked such a URL
            $from_caret = [
                "^" => "^",
                "s" => "/",
                "b" => "\\",
                "q" => "?",
                "a" => "&",
                "d" => ".",
            ];
            $out = "";
            $length = strlen($str);
            for ($i = 0; $i < $length; $i++) {
                if ($str[$i] == "^") {
                    $i++;
                    $out .= $from_caret[$str[$i]] ?? '';
                } else {
                    $out .= $str[$i];
                }
            }
            $str = $out;
            // end legacy

            $search_terms = Tag::explode($str);
        }
        return $search_terms;
    }

    public function get_page_number(): int
    {
        $page_number = 1;
        if ($this->count_args() === 1) {
            $page_number = int_escape($this->get_arg(0));
        } elseif ($this->count_args() === 2) {
            $page_number = int_escape($this->get_arg(1));
        }
        if ($page_number === 0) {
            $page_number = 1;
        } // invalid -> 0
        return $page_number;
    }

    public function get_page_size(): int
    {
        global $config;
        return $config->get_int(IndexConfig::IMAGES);
    }
}


class CliGenEvent extends Event
{
    public function __construct(
        public \Symfony\Component\Console\Application $app
    ) {
        parent::__construct();
    }
}


/**
 * A signal that some text needs formatting, the event carries
 * both the text and the result
 */
class TextFormattingEvent extends Event
{
    /**
     * For reference
     */
    public string $original;

    /**
     * with formatting applied
     */
    public string $formatted;

    /**
     * with formatting removed
     */
    public string $stripped;

    public function __construct(string $text)
    {
        parent::__construct();
        // We need to escape before formatting, instead of at display time,
        // because formatters will add their own HTML tags into the mix and
        // we don't want to escape those.
        $h_text = html_escape(trim($text));
        $this->original  = $h_text;
        $this->formatted = $h_text;
        $this->stripped  = $h_text;
    }
}


/**
 * A signal that something needs logging
 */
class LogEvent extends Event
{
    /**
     * a category, normally the extension name
     */
    public string $section;

    /**
     * See python...
     */
    public int $priority = 0;

    /**
     * Free text to be logged
     */
    public string $message;

    /**
     * The time that the event was created
     */
    public int $time;

    /**
     * Extra data to be held separate
     *
     * @var string[]
     */
    public array $args;

    public function __construct(string $section, int $priority, string $message)
    {
        parent::__construct();
        $this->section = $section;
        $this->priority = $priority;
        $this->message = $message;
        $this->time = time();
    }
}

class DatabaseUpgradeEvent extends Event
{
}
