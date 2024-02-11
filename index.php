<?php

declare(strict_types=1);

namespace Shimmie2;

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

if (!file_exists("data/config/shimmie.conf.php") && !getenv("SHM_DATABASE_DSN")) {
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
require_once "core/microhtml.php";

global $cache, $config, $database, $user, $page, $_tracer;
_set_up_shimmie_environment();
$_tracer = new \EventTracer();
$_tracer->begin("Bootstrap");
_load_core_files();
$cache = loadCache(CACHE_DSN);
$database = new Database(DATABASE_DSN);
$config = new DatabaseConfig($database);
_load_extension_files();
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
            "user" => $_COOKIE["shm_user"] ?? "No User",
            "ip" => get_real_ip() ?? "No IP",
            "user_agent" => $_SERVER['HTTP_USER_AGENT'] ?? "No UA",
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
        ob_end_flush();
        ob_implicit_flush(true);
        $app = new CliApp();
        send_event(new CliGenEvent($app));
        if($app->run() !== 0) {
            throw new \Exception("CLI command failed");
        }
    } else {
        send_event(new PageRequestEvent($_SERVER['REQUEST_METHOD'], _get_query(), $_GET, $_POST));
        $page->display();
    }

    if ($database->is_transaction_open()) {
        $database->commit();
    }

    // saving cache data and profiling data to disk can happen later
    if (function_exists("fastcgi_finish_request")) {
        fastcgi_finish_request();
    }
    $exit_code = 0;
} catch (\Exception $e) {
    if ($database->is_transaction_open()) {
        $database->rollback();
    }
    if(is_a($e, \Shimmie2\UserError::class)) {
        $page->set_mode(PageMode::PAGE);
        $page->set_code($e->http_code);
        $page->set_title("Error");
        $page->add_block(new Block(null, \MicroHTML\SPAN($e->getMessage())));
        $page->display();
    } else {
        _fatal_error($e);
    }
    $exit_code = 1;
} finally {
    $_tracer->end();
    if (TRACE_FILE) {
        if (
            empty($_SERVER["REQUEST_URI"])
            || (@$_GET["trace"] == "on")
            || (
                (ftime() - $_shm_load_start) > TRACE_THRESHOLD
                && ($_SERVER["REQUEST_URI"] ?? "") != "/upload"
            )
        ) {
            $_tracer->flush(TRACE_FILE);
        }
    }
}
if (PHP_SAPI === 'cli' || PHP_SAPI == 'phpdbg') {
    exit($exit_code);
}
