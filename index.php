<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Make sure that shimmie is correctly installed                             *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

require_once "core/sanitize_php.php";
require_once "core/polyfills.php";

if (!file_exists("vendor/")) {
    $cwd = getcwd();
    die_nicely(
        "Shimmie is unable to find the composer <code>vendor</code> directory.",
        "
			<p>To finish installing, you need to run <code>composer install</code>
			in the shimmie directory (<code>$cwd</code>).</p>
			<p>(If you don't have composer, <a href='https://getcomposer.org/'>get it here</a>)</p>
		"
    );
}

if (!file_exists("data/config/shimmie.conf.php")) {
    require_once "core/install.php";
    install();
    exit;
}

require_once "vendor/autoload.php";


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Load files                                                                *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

@include_once "data/config/shimmie.conf.php";
@include_once "data/config/extensions.conf.php";
require_once "core/sys_config.php";
require_once "core/util.php";

global $cache, $config, $database, $user, $page, $_tracer;
_set_up_shimmie_environment();
$_tracer = new EventTracer();
$_tracer->begin("Bootstrap");
_load_core_files();
$cache = new Cache(CACHE_DSN);
$database = new Database(DATABASE_DSN);
$config = new DatabaseConfig($database);
ExtensionInfo::load_all_extension_info();
Extension::determine_enabled_extensions();
require_all(zglob("ext/{".Extension::get_enabled_extensions_as_string()."}/main.php"));
_load_theme_files();
$page = new Page();
_load_event_listeners();
$_tracer->end();


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Send events, display output                                               *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

try {
    // $_tracer->mark($_SERVER["REQUEST_URI"] ?? "No Request");
    $_tracer->begin(
        $_SERVER["REQUEST_URI"] ?? "No Request",
        [
            "user"=>$_COOKIE["shm_user"] ?? "No User",
            "ip"=>$_SERVER['REMOTE_ADDR'] ?? "No IP",
            "user_agent"=>$_SERVER['HTTP_USER_AGENT'] ?? "No UA",
        ]
    );

    if (!SPEED_HAX) {
        send_event(new DatabaseUpgradeEvent());
    }
    send_event(new InitExtEvent());

    // start the page generation waterfall
    $user = _get_user();
    send_event(new UserLoginEvent($user));
    if (PHP_SAPI === 'cli' || PHP_SAPI == 'phpdbg') {
        send_event(new CommandEvent($argv));
    } else {
        send_event(new PageRequestEvent(_get_query()));
        $page->display();
    }

    if ($database->is_transaction_open()) {
        $database->commit();
    }

    // saving cache data and profiling data to disk can happen later
    if (function_exists("fastcgi_finish_request")) {
        fastcgi_finish_request();
    }
} catch (Exception $e) {
    if ($database && $database->is_transaction_open()) {
        $database->rollback();
    }
    _fatal_error($e);
} finally {
    $_tracer->end();
    if (TRACE_FILE) {
        if (
            empty($_SERVER["REQUEST_URI"])
            || (@$_GET["trace"] == "on")
            || (
                (microtime(true) - $_shm_load_start) > TRACE_THRESHOLD
                && ($_SERVER["REQUEST_URI"] ?? "") != "/upload"
            )
        ) {
            $_tracer->flush(TRACE_FILE);
        }
    }
}
