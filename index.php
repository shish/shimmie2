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
version_check("8.4");

if (!file_exists("data/config/shimmie.conf.php")) {
    Installer::install();
    exit(0);
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Load files                                                                *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

@include_once "data/config/shimmie.conf.php";
@include_once "data/config/extensions.conf.php";

_set_up_shimmie_environment();
Ctx::setTracer(new \MicroOTLP\Client(
    resourceAttributes: [
        'service.name' => 'shimmie2',
        'service.instance.id' => gethostname() ?: 'unknown',
    ],
    scopeAttributes: [
        'name' => 'shimmie2',
        'version' => SysConfig::getVersion(),
    ],
));
// Override TS to show that bootstrapping started in the past
Ctx::setRootSpan(Ctx::$tracer->startSpan("Root", startTime: (int)($_SERVER["REQUEST_TIME_FLOAT"] * 1e9)));
$sBoot = Ctx::$tracer->startSpan("Bootstrap", startTime: (int)($_SERVER["REQUEST_TIME_FLOAT"] * 1e9));
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
$sBoot->end();

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Send events, display output                                               *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

function main(): int
{
    // Ctx::$tracer->mark($_SERVER["REQUEST_URI"] ?? "No Request");
    $sMain = Ctx::$tracer->startSpan(
        "Main",
        [
            "enduser.id" => $_COOKIE["shm_user"] ?? "No User",
            "net.peer.ip" => Network::get_real_ip(),
            "http.uri" => $_SERVER["REQUEST_URI"] ?? "No URI",
            "http.user_agent" => $_SERVER['HTTP_USER_AGENT'] ?? "No UA",
        ]
    );

    $iee = null;
    // nested try-catch blocks so that we can try to handle user-errors
    // in a pretty and theme-customisable way, but if that breaks, the
    // breakage will be handled by the server-error handler
    try {
        try {
            if (!Ctx::$config->get(SetupConfig::NO_AUTO_DB_UPGRADE)) {
                send_event(new DatabaseUpgradeEvent());
            }
            $iee = send_event(new InitExtEvent());

            // start the page generation waterfall
            if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
                ob_end_flush();
                ob_implicit_flush(true);
                $app = new CliApp();
                send_event(new CliGenEvent($app));
                if ($app->run() !== 0) {
                    throw new \Exception("CLI command failed");
                }
            } else {
                send_event(new UserLoginEvent(_get_user()));
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
            $sMain->end(success: true, attributes: ["http.status_code" => Ctx::$page->code]);
            $exit_code = 0;
        } catch (UserError $e) {
            if (Ctx::$database->is_transaction_open()) {
                Ctx::$database->rollback();
            }
            Ctx::$page->set_error($e);
            Ctx::$page->display();
            // "User Error" is considered success from a system perspective
            $sMain->end(success: true, message: (string)$e, attributes: ["http.status_code" => Ctx::$page->code]);
            $exit_code = 2;
        }
    } catch (\Throwable $e) {
        _fatal_error($e);
        $code = is_a($e, SCoreException::class) ? $e->http_code : 500;
        $sMain->end(success: false, message: (string)$e, attributes: ["http.status_code" => $code]);
        $exit_code = 1;
    } finally {
        Ctx::$root_span->end();
        $iee?->run_shutdown_handlers();
    }
    return $exit_code;
}

if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
    exit(main());
} else {
    main();
}
