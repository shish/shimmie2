<?php

declare(strict_types=1);

namespace Shimmie2;

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Make sure that shimmie is correctly installed                             *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

if (!file_exists("vendor/")) {
    die("
        <p>Shimmie is unable to find the composer <code>vendor</code> directory.</p>
		<p>To finish installing, you need to run <code>composer install</code>
		in the shimmie directory (<code>".getcwd()."</code>).</p>
		<p>(If you don't have composer, <a href='https://getcomposer.org/'>get it here</a>)</p>
	");
}
require_once "vendor/autoload.php";

sanitize_php();
version_check("8.2");

if (!file_exists("data/config/shimmie.conf.php")) {
    Installer::install();
    exit;
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Load files                                                                *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

@include_once "data/config/shimmie.conf.php";
@include_once "data/config/extensions.conf.php";

_set_up_shimmie_environment();
Ctx::setTracer(new \EventTracer());
// Override TS to show that bootstrapping started in the past
Ctx::$tracer->begin("Bootstrap", raw: ["ts" => $_SERVER["REQUEST_TIME_FLOAT"] * 1e6]);
_load_ext_files();
// Depends on core files
$cache = Ctx::setCache(load_cache(SysConfig::getCacheDsn()));
$database = Ctx::setDatabase(new Database(SysConfig::getDatabaseDsn()));
// $config depends on _load_ext_files (to load config.php files and
// calculate defaults) and $cache (to cache config values)
$config = Ctx::setConfig(new DatabaseConfig($database));
// theme files depend on $config (theme name is a config value)
_load_theme_files();
// $page depends on theme files (to load theme-specific Page class)
$page = Ctx::setPage(Themelet::get_theme_class(Page::class) ?? new Page());
// $event_bus depends on ext/*/main.php being loaded
Ctx::setEventBus(new EventBus());
Ctx::$tracer->end();

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Send events, display output                                               *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

function main(): int
{
    // nested try-catch blocks so that we can try to handle user-errors
    // in a pretty and theme-customisable way, but if that breaks, the
    // breakage will be handled by the server-error handler
    try {
        try {
            // Ctx::$tracer->mark($_SERVER["REQUEST_URI"] ?? "No Request");
            Ctx::$tracer->begin(
                $_SERVER["REQUEST_URI"] ?? "No Request",
                [
                    "user" => $_COOKIE["shm_user"] ?? "No User",
                    "ip" => Network::get_real_ip(),
                    "user_agent" => $_SERVER['HTTP_USER_AGENT'] ?? "No UA",
                ]
            );

            if (!Ctx::$config->get(SetupConfig::NO_AUTO_DB_UPGRADE)) {
                send_event(new DatabaseUpgradeEvent());
            }
            send_event(new InitExtEvent());

            // start the page generation waterfall
            Ctx::setUser(_get_user());
            send_event(new UserLoginEvent(Ctx::$user));
            if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
                ob_end_flush();
                ob_implicit_flush(true);
                $app = new CliApp();
                send_event(new CliGenEvent($app));
                if ($app->run() !== 0) {
                    throw new \Exception("CLI command failed");
                }
            } else {
                send_event(new PageRequestEvent(
                    $_SERVER['REQUEST_METHOD'],
                    _get_query(),
                    new QueryArray($_GET),
                    new QueryArray($_POST)
                ));
                Ctx::$page->display();
            }

            if (Ctx::$database->is_transaction_open()) {
                Ctx::$database->commit();
            }

            // saving cache data and profiling data to disk can happen later
            if (function_exists("fastcgi_finish_request")) {
                fastcgi_finish_request();
            }
            $exit_code = 0;
        } catch (UserError $e) {
            if (Ctx::$database->is_transaction_open()) {
                Ctx::$database->rollback();
            }
            Ctx::$page->set_error($e);
            Ctx::$page->display();
            $exit_code = 2;
        }
    } catch (\Throwable $e) {
        _fatal_error($e);
        $exit_code = 1;
    } finally {
        Ctx::$tracer->end();
        if (
            SysConfig::getTraceFile() !== null
            && (
                @$_GET["trace"] === "on"
                || (ftime() - $_SERVER["REQUEST_TIME_FLOAT"]) > SysConfig::getTraceThreshold()
            )
            && ($_SERVER["REQUEST_URI"] ?? "") !== "/upload"
            && is_writable(SysConfig::getTraceFile())
        ) {
            Ctx::$tracer->flush(SysConfig::getTraceFile());
        }
    }
    return $exit_code;
}

if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
    exit(main());
} else {
    main();
}
