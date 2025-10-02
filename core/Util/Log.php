<?php

declare(strict_types=1);

namespace Shimmie2;

final class Log
{
    /**
    * A shorthand way to send a LogEvent
    *
    * When parsing a user request, a flash message should give info to the user
    * When taking action, a log event should be stored by the server
    * Quite often, both of these happen at once, hence log_*() having $flash
    */
    private static function msg(string $section, LogLevel $priority, string $message, ?string $flash = null): void
    {
        send_event(new LogEvent($section, $priority->value, $message));

        if (!is_null($flash)) {
            Ctx::$page->flash($flash);
        }
    }

    // More shorthand ways of logging
    public static function debug(string $section, string $message, ?string $flash = null): void
    {
        self::msg($section, LogLevel::DEBUG, $message, $flash);
    }
    public static function info(string $section, string $message, ?string $flash = null): void
    {
        self::msg($section, LogLevel::INFO, $message, $flash);
    }
    public static function warning(string $section, string $message, ?string $flash = null): void
    {
        self::msg($section, LogLevel::WARNING, $message, $flash);
    }
    public static function error(string $section, string $message, ?string $flash = null): void
    {
        self::msg($section, LogLevel::ERROR, $message, $flash);
    }
    public static function critical(string $section, string $message, ?string $flash = null): void
    {
        self::msg($section, LogLevel::CRITICAL, $message, $flash);
    }

    /**
    * Get a unique ID for this request, useful for grouping log messages.
    */
    public static function get_request_id(): string
    {
        static $request_id = null;
        if (!$request_id) {
            // not completely trustworthy, as a user can spoof this
            if (@$_SERVER['HTTP_X_VARNISH']) {
                $request_id = $_SERVER['HTTP_X_VARNISH'];
            } else {
                $request_id = "P" . uniqid();
            }
        }
        return $request_id;
    }
}
