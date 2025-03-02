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
require_once "tests/defines.php";

CliApp::$logLevel = LogLevel::CRITICAL->value;
$_SERVER['SCRIPT_FILENAME'] = '/var/www/html/test/index.php';
$_SERVER['DOCUMENT_ROOT'] = '/var/www/html';
$_SERVER['QUERY_STRING'] = '/';

if (file_exists("data/test-trace.json")) {
    unlink("data/test-trace.json");
}

sanitize_php();
global $cache, $config, $database, $user, $page, $_tracer, $_shm_event_bus;
_set_up_shimmie_environment();
$tracer_enabled = true;
$_tracer = new \EventTracer();
$_tracer->begin("bootstrap");
_load_ext_files();
$cache = load_cache(SysConfig::getCacheDsn());
$database = new Database(SysConfig::getDatabaseDsn());
Installer::create_dirs();
Installer::create_tables($database);
$config = new DatabaseConfig($database, defaults: ConfigGroup::get_all_defaults());
_load_theme_files();
$page = new Page();
$_shm_event_bus = new EventBus();
$config->set_string("thumb_engine", "static");
$config->set_bool("nice_urls", true);
$config->set_bool("approve_images", false);
send_event(new DatabaseUpgradeEvent());
send_event(new InitExtEvent());
$user = User::by_id($config->get_int(UserAccountsConfig::ANON_ID, 0));
$userPage = new UserPage();
$userPage->onUserCreation(new UserCreationEvent("demo", "demo", "demo", "demo@demo.com", false));
$userPage->onUserCreation(new UserCreationEvent("test", "test", "test", "test@test.com", false));
// in mysql, CREATE TABLE commits transactions, so after the database
// upgrade we may or may not be inside a transaction depending on if
// any tables were created.
if ($database->is_transaction_open()) {
    $database->commit();
}
$_tracer->end();
