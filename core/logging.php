<?php

declare(strict_types=1);

namespace Shimmie2;

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Logging convenience                                                       *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

enum LogLevel: int
{
    case NOT_SET = 0;
    case DEBUG = 10;
    case INFO = 20;
    case WARNING = 30;
    case ERROR = 40;
    case CRITICAL = 50;

    /**
     * @return array<string, int>
     */
    public static function names_to_levels(): array
    {
        $ret = [];
        foreach (LogLevel::cases() as $case) {
            $ret[$case->name] = $case->value;
        }
        return $ret;
    }
}

/**
 * A shorthand way to send a LogEvent
 *
 * When parsing a user request, a flash message should give info to the user
 * When taking action, a log event should be stored by the server
 * Quite often, both of these happen at once, hence log_*() having $flash
 */
function log_msg(string $section, LogLevel $priority, string $message, ?string $flash = null): void
{
    global $page;
    send_event(new LogEvent($section, $priority->value, $message));
    $threshold = defined("CLI_LOG_LEVEL") ? CLI_LOG_LEVEL : 0;

    if ((PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') && ($priority->value >= $threshold)) {
        print date("c")." $section: $message\n";
        ob_flush();
    }
    if (!is_null($flash)) {
        $page->flash($flash);
    }
}

// More shorthand ways of logging
function log_debug(string $section, string $message, ?string $flash = null): void
{
    log_msg($section, LogLevel::DEBUG, $message, $flash);
}
function log_info(string $section, string $message, ?string $flash = null): void
{
    log_msg($section, LogLevel::INFO, $message, $flash);
}
function log_warning(string $section, string $message, ?string $flash = null): void
{
    log_msg($section, LogLevel::WARNING, $message, $flash);
}
function log_error(string $section, string $message, ?string $flash = null): void
{
    log_msg($section, LogLevel::ERROR, $message, $flash);
}
function log_critical(string $section, string $message, ?string $flash = null): void
{
    log_msg($section, LogLevel::CRITICAL, $message, $flash);
}


/**
 * Get a unique ID for this request, useful for grouping log messages.
 */
function get_request_id(): string
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
