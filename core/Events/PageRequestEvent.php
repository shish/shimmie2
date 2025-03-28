<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * A signal that a page has been requested.
 *
 * User requests /view/42 -> an event is generated with $args = array("view",
 * "42"); when an event handler asks $event->page_matches("view/{id}"), it returns
 * true and sets $event->get_arg('id') = "42"
 */
final class PageRequestEvent extends Event
{
    private string $method;
    public string $path;
    /** @var query-array */
    public array $GET;
    /** @var query-array */
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

    /**
     * @param string $method The HTTP method used to make the request
     * @param string $path The path of the request
     * @param array<string, string|string[]> $get The GET parameters
     * @param array<string, string|string[]> $post The POST parameters
     */
    public function __construct(string $method, string $path, array $get, array $post)
    {
        parent::__construct();

        $this->method = $method;

        // if we're looking at the root of the install,
        // use the default front page
        if ($path === "") {
            $path = Ctx::$config->req(SetupConfig::FRONT_PAGE);
        }
        $this->path = $path;
        $this->GET = $get;
        $this->POST = $post;

        // break the path into parts
        $this->args = explode('/', $path);
    }

    public function get_GET(string $key): ?string
    {
        if (array_key_exists($key, $this->GET)) {
            if (is_array($this->GET[$key])) {
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
        if ($value === null) {
            throw new UserError("Missing GET parameter {$key}");
        }
        return $value;
    }

    public function get_POST(string $key): ?string
    {
        if (array_key_exists($key, $this->POST)) {
            if (is_array($this->POST[$key])) {
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
        if ($value === null) {
            throw new UserError("Missing POST parameter {$key}");
        }
        return $value;
    }

    /**
     * @return string[]|null
     */
    public function get_POST_array(string $key): ?array
    {
        if (array_key_exists($key, $this->POST)) {
            if (!is_array($this->POST[$key])) {
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
        if ($value === null) {
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
        if ($paged) {
            if ($this->page_matches("$name/{page_num}", $method, $authed, $permission, false)) {
                $pn = $this->get_arg("page_num");
                if (is_numberish($pn)) {
                    return true;
                }
            }
        }

        assert($method === null || in_array($method, ["GET", "POST", "OPTIONS"]));
        $authed = $authed ?? $method === "POST";

        // method check is fast so do that first
        if ($method !== null && $this->method !== $method) {
            return false;
        }

        // check if the path matches
        $parts = explode("/", $name);
        $part_count = count($parts);
        if ($part_count !== count($this->args)) {
            return false;
        }
        $this->named_args = [];
        for ($i = 0; $i < $part_count; $i++) {
            if (str_starts_with($parts[$i], "{")) {
                $this->named_args[substr($parts[$i], 1, -1)] = $this->args[$i];
            } elseif ($parts[$i] !== $this->args[$i]) {
                return false;
            }
        }

        // if we matched the method and the path, but the page requires
        // authentication and the user is not authenticated, then complain
        if ($authed && !defined("UNITTEST")) {
            if (!isset($this->POST["auth_token"])) {
                throw new PermissionDenied("Permission Denied: Missing CSRF Token");
            }
            if ($this->POST["auth_token"] !== Ctx::$user->get_auth_token()) {
                throw new PermissionDenied("Permission Denied: Invalid CSRF Token (Go back, refresh the page, and try again?)");
            }
        }
        if ($permission !== null && !Ctx::$user->can($permission)) {
            throw new PermissionDenied("Permission Denied: " . Ctx::$user->name . " lacks permission {$permission}");
        }

        return true;
    }

    /**
     * Get the n th argument of the page request (if it exists.)
     */
    public function get_arg(string $n, ?string $default = null): string
    {
        if (array_key_exists($n, $this->named_args)) {
            return rawurldecode($this->named_args[$n]);
        } elseif ($default !== null) {
            return $default;
        } else {
            throw new UserError("Page argument {$n} is missing");
        }
    }

    public function get_iarg(string $n, ?int $default = null): int
    {
        if (array_key_exists($n, $this->named_args)) {
            if (is_numberish($this->named_args[$n]) === false) {
                throw new UserError("Page argument {$n} exists but is not numeric");
            }
            return int_escape($this->named_args[$n]);
        } elseif ($default !== null) {
            return $default;
        } else {
            throw new UserError("Page argument {$n} is missing");
        }
    }
}
