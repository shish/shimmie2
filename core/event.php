<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

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
 * "42"); when an event handler asks $event->page_matches("view/{id}"), it returns
 * true and sets $event->get_arg('id') = "42"
 */
class PageRequestEvent extends Event
{
    private string $method;
    public string $path;
    /** @var array<string, string|string[]> */
    public array $GET;
    /** @var array<string, string|string[]> */
    public array $POST;

    /**
     * @var string[]
     */
    public array $args;
    /**
     * @var array<string, string>
     */
    private array $named_args = [];
    public int $page_num;
    private bool $is_authed;

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
        $this->is_authed = (
            defined("UNITTEST")
            || (isset($_POST["auth_token"]) && $_POST["auth_token"] == $user->get_auth_token())
        );

        // break the path into parts
        $this->args = explode('/', $path);
    }

    public function get_GET(string $key): ?string
    {
        if(array_key_exists($key, $this->GET)) {
            if(is_array($this->GET[$key])) {
                throw new UserError("GET parameter {$key} is an array, expected single value");
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
            throw new UserError("Missing GET parameter {$key}");
        }
        return $value;
    }

    public function get_POST(string $key): ?string
    {
        if(array_key_exists($key, $this->POST)) {
            if(is_array($this->POST[$key])) {
                throw new UserError("POST parameter {$key} is an array, expected single value");
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
            throw new UserError("Missing POST parameter {$key}");
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
                throw new UserError("POST parameter {$key} is a single value, expected array");
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
            throw new UserError("Missing POST parameter {$key}");
        }
        return $value;
    }

    public function page_starts_with(string $name): bool
    {
        return str_starts_with($this->path, $name);
    }

    /**
     * Test if the requested path matches a given pattern.
     *
     * If it matches, store the remaining path elements in $args
     */
    public function page_matches(
        string $name,
        ?string $method = null,
        ?bool $authed = null,
        ?string $permission = null,
        bool $paged = false,
    ): bool {
        global $user;

        if($paged) {
            if($this->page_matches("$name/{page_num}", $method, $authed, $permission, false)) {
                $pn = $this->get_arg("page_num");
                if(is_numberish($pn)) {
                    return true;
                }
            }
        }

        assert($method === null || in_array($method, ["GET", "POST", "OPTIONS"]));
        $authed = $authed ?? $method == "POST";

        // method check is fast so do that first
        if($method !== null && $this->method !== $method) {
            return false;
        }

        // check if the path matches
        $parts = explode("/", $name);
        $part_count = count($parts);
        if ($part_count != count($this->args)) {
            return false;
        }
        $this->named_args = [];
        for ($i = 0; $i < $part_count; $i++) {
            if (str_starts_with($parts[$i], "{")) {
                $this->named_args[substr($parts[$i], 1, -1)] = $this->args[$i];
            } elseif ($parts[$i] != $this->args[$i]) {
                return false;
            }
        }

        // if we matched the method and the path, but the page requires
        // authentication and the user is not authenticated, then complain
        if($authed && $this->is_authed === false) {
            throw new PermissionDenied("Permission Denied: Missing CSRF Token");
        }
        if($permission !== null && !$user->can($permission)) {
            throw new PermissionDenied("Permission Denied: {$user->name} lacks permission {$permission}");
        }

        return true;
    }

    /**
     * Get the n th argument of the page request (if it exists.)
     */
    public function get_arg(string $n, ?string $default = null): string
    {
        if(array_key_exists($n, $this->named_args)) {
            return rawurldecode($this->named_args[$n]);
        } elseif($default !== null) {
            return $default;
        } else {
            throw new UserError("Page argument {$n} is missing");
        }
    }

    public function get_iarg(string $n, ?int $default = null): int
    {
        if(array_key_exists($n, $this->named_args)) {
            if(is_numberish($this->named_args[$n]) === false) {
                throw new UserError("Page argument {$n} exists but is not numeric");
            }
            return int_escape($this->named_args[$n]);
        } elseif($default !== null) {
            return $default;
        } else {
            throw new UserError("Page argument {$n} is missing");
        }
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

/**
 * @template T
 */
abstract class PartListBuildingEvent extends Event
{
    /** @var T[] */
    private array $parts = [];

    /**
     * @param T $html
     */
    public function add_part(mixed $html, int $position = 50): void
    {
        while (isset($this->parts[$position])) {
            $position++;
        }
        $this->parts[$position] = $html;
    }

    /**
     * @return array<T>
     */
    public function get_parts(): array
    {
        ksort($this->parts);
        return $this->parts;
    }
}
