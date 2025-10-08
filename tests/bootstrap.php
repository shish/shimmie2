<?php

/**
 * Sets up a base test environment:
 * - Loads the code
 * - Runs the installer
 * - Runs the database upgrade
 * - Creates test users
 * - Commits the database transaction
 */
declare(strict_types=1);

namespace Shimmie2;

chdir(dirname(dirname(__FILE__)));
require_once "vendor/autoload.php";

define("DATABASE_DSN", getenv("TEST_DSN") ?: "sqlite::memory:");
define("UNITTEST", true);
define("EXTRA_EXTS", array_map(fn ($x) => str_replace("ext/", "", $x), \Safe\glob('ext/*')));
define("VERSION", 'unit-tests');
define("TIMEZONE", 'UTC');
define("SECRET", "asdfghjkl");

$_SERVER['SCRIPT_FILENAME'] = '/var/www/html/test/index.php';
$_SERVER['DOCUMENT_ROOT'] = '/var/www/html';
$_SERVER['QUERY_STRING'] = '/';

if (file_exists("data/test-trace.json")) {
    unlink("data/test-trace.json");
}

sanitize_php();
_set_up_shimmie_environment();
Ctx::setTracer(new \MicroOTLP\Client());
Ctx::setRootSpan(Ctx::$tracer->startSpan("Root"));
$sBoot = Ctx::$tracer->startSpan("Test Bootstrap");
_load_ext_files();
Ctx::setCache(load_cache(SysConfig::getCacheDsn()));
Ctx::setDatabase(new Database(SysConfig::getDatabaseDsn()));
Installer::create_dirs();
Installer::create_tables(Ctx::$database);
Ctx::setConfig(new DatabaseConfig(Ctx::$database));
Ctx::$config->set(ThumbnailConfig::ENGINE, "static");
Ctx::$config->set(SetupConfig::NICE_URLS, true);
_load_theme_files();
Ctx::setPage(new Page());
Ctx::setEventBus(new EventBus());
send_event(new DatabaseUpgradeEvent());
send_event(new InitExtEvent());
Ctx::setUser(User::get_anonymous());
send_event(new UserCreationEvent("demo", "demo", "demo", "demo@demo.com", false));
send_event(new UserCreationEvent("test", "test", "test", "test@test.com", false));
// in mysql, CREATE TABLE commits transactions, so after the database
// upgrade we may or may not be inside a transaction depending on if
// any tables were created.
if (Ctx::$database->is_transaction_open()) {
    Ctx::$database->commit();
}
$sBoot->end();
Ctx::$root_span->end();
