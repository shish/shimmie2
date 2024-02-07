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
require_once "core/sanitize_php.php";
require_once "vendor/autoload.php";
require_once "tests/defines.php";
require_once "core/sys_config.php";
require_once "core/polyfills.php";
require_once "core/util.php";

$_SERVER['SCRIPT_FILENAME'] = '/var/www/html/test/index.php';
$_SERVER['DOCUMENT_ROOT'] = '/var/www/html';
$_SERVER['QUERY_STRING'] = '/';

if (file_exists("data/test-trace.json")) {
    unlink("data/test-trace.json");
}

global $cache, $config, $database, $user, $page, $_tracer;
_set_up_shimmie_environment();
$tracer_enabled = true;
$_tracer = new \EventTracer();
$_tracer->begin("bootstrap");
_load_core_files();
$cache = loadCache(CACHE_DSN);
$database = new Database(getenv("TEST_DSN") ?: "sqlite::memory:");
create_dirs();
create_tables($database);
$config = new DatabaseConfig($database);
_load_extension_files();
_load_theme_files();
$page = new Page();
_load_event_listeners();
$config->set_string("thumb_engine", "static");
$config->set_bool("nice_urls", true);
send_event(new DatabaseUpgradeEvent());
send_event(new InitExtEvent());
$user = User::by_id($config->get_int("anon_id", 0));
$userPage = new UserPage();
$userPage->onUserCreation(new UserCreationEvent("demo", "demo", "demo", "demo@demo.com", false));
$userPage->onUserCreation(new UserCreationEvent("test", "test", "test", "test@test.com", false));
// in mysql, CREATE TABLE commits transactions, so after the database
// upgrade we may or may not be inside a transaction depending on if
// any tables were created.
if($database->is_transaction_open()) {
    $database->commit();
}
$_tracer->end();
