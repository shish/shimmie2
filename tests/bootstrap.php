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

$_shm_load_start = microtime(true);

chdir(dirname(dirname(__FILE__)));
require_once "vendor/autoload.php";

define("DATABASE_DSN", getenv("TEST_DSN") ?: "sqlite::memory:");
define("UNITTEST", true);
define("EXTRA_EXTS", array_map(fn ($x) => str_replace("ext/", "", $x), \Safe\glob('ext/*')));
define("VERSION", 'unit-tests');
define("TIMEZONE", 'UTC');
define("SECRET", "asdfghjkl");

CliApp::$logLevel = LogLevel::CRITICAL->value;

$_SERVER['SCRIPT_FILENAME'] = '/var/www/html/test/index.php';
$_SERVER['DOCUMENT_ROOT'] = '/var/www/html';
$_SERVER['QUERY_STRING'] = '/';

if (file_exists("data/test-trace.json")) {
    unlink("data/test-trace.json");
}

sanitize_php();
global $database, $user, $page;
_set_up_shimmie_environment();
Ctx::$tracer_enabled = true;
Ctx::setTracer(new \EventTracer());
Ctx::$tracer->begin("bootstrap");
_load_ext_files();
$cache = Ctx::setCache(load_cache(SysConfig::getCacheDsn()));
$database = Ctx::setDatabase(new Database(SysConfig::getDatabaseDsn()));
Installer::create_dirs();
Installer::create_tables($database);
$config = Ctx::setConfig(new DatabaseConfig($database));
_load_theme_files();
$page = Ctx::setPage(new Page());
Ctx::setEventBus(new EventBus());
$config->set(ThumbnailConfig::ENGINE, "static");
$config->set(SetupConfig::NICE_URLS, true);
send_event(new DatabaseUpgradeEvent());
send_event(new InitExtEvent());
$user = Ctx::setUser(User::by_id($config->req(UserAccountsConfig::ANON_ID)));
$userPage = new UserPage();
$userPage->onUserCreation(new UserCreationEvent("demo", "demo", "demo", "demo@demo.com", false));
$userPage->onUserCreation(new UserCreationEvent("test", "test", "test", "test@test.com", false));
// in mysql, CREATE TABLE commits transactions, so after the database
// upgrade we may or may not be inside a transaction depending on if
// any tables were created.
if ($database->is_transaction_open()) {
    $database->commit();
}
Ctx::$tracer->end();
