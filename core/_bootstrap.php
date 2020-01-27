<?php declare(strict_types=1);
/*
 * Load all the files into memory, sanitise the environment, but don't
 * actually do anything as far as the app is concerned
 */

global $cache, $config, $database, $user, $page, $_tracer;

require_once "core/sys_config.php";
require_once "core/polyfills.php";
require_once "core/util.php";
require_once "vendor/autoload.php";

_sanitise_environment();
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
