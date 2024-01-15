<?php

declare(strict_types=1);

namespace Shimmie2;

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Logging convenience                                                       *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

define("SCORE_LOG_CRITICAL", 50);
define("SCORE_LOG_ERROR", 40);
define("SCORE_LOG_WARNING", 30);
define("SCORE_LOG_INFO", 20);
define("SCORE_LOG_DEBUG", 10);
define("SCORE_LOG_NOTSET", 0);

const LOGGING_LEVEL_NAMES = [
    SCORE_LOG_NOTSET => "Not Set",
    SCORE_LOG_DEBUG => "Debug",
    SCORE_LOG_INFO => "Info",
    SCORE_LOG_WARNING => "Warning",
    SCORE_LOG_ERROR => "Error",
    SCORE_LOG_CRITICAL => "Critical",
];

/**
 * A shorthand way to send a LogEvent
 *
 * When parsing a user request, a flash message should give info to the user
 * When taking action, a log event should be stored by the server
 * Quite often, both of these happen at once, hence log_*() having $flash
 */
function log_msg(string $section, int $priority, string $message, ?string $flash = null): void
{
    global $page;
    send_event(new LogEvent($section, $priority, $message));
    $threshold = defined("CLI_LOG_LEVEL") ? CLI_LOG_LEVEL : 0;

    if ((PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') && ($priority >= $threshold)) {
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
    log_msg($section, SCORE_LOG_DEBUG, $message, $flash);
}
function log_info(string $section, string $message, ?string $flash = null): void
{
    log_msg($section, SCORE_LOG_INFO, $message, $flash);
}
function log_warning(string $section, string $message, ?string $flash = null): void
{
    log_msg($section, SCORE_LOG_WARNING, $message, $flash);
}
function log_error(string $section, string $message, ?string $flash = null): void
{
    log_msg($section, SCORE_LOG_ERROR, $message, $flash);
}
function log_critical(string $section, string $message, ?string $flash = null): void
{
    log_msg($section, SCORE_LOG_CRITICAL, $message, $flash);
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
