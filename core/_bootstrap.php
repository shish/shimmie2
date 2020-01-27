<?php declare(strict_types=1);
/*
 * Load all the files into memory, sanitise the environment, but don't
 * actually do anything as far as the app is concerned
 */

global $cache, $config, $database, $user, $page, $_tracer, $tracer_enabled;

require_once "core/sys_config.php";
require_once "core/polyfills.php";
require_once "core/util.php";
require_once "vendor/autoload.php";

// set up and purify the environment
_version_check();
_sanitise_environment();

// The trace system has a certain amount of memory consumption every time it is used,
// so to prevent running out of memory during complex operations code that uses it should
// check if tracer output is enabled before making use of it.
$tracer_enabled = constant('TRACE_FILE')!==null;

// load base files
$_tracer->begin("Bootstrap");
require_all(array_merge(
    zglob("core/*.php"),
    zglob("core/{".ENABLED_MODS."}/*.php"),
    zglob("ext/*/info.php")
));

$cache = new Cache(CACHE_DSN);
$database = new Database(DATABASE_DSN);
$config = new DatabaseConfig($database);

ExtensionInfo::load_all_extension_info();
Extension::determine_enabled_extensions();
require_all(zglob("ext/{".Extension::get_enabled_extensions_as_string()."}/main.php"));

// load the theme parts
require_all(_get_themelet_files(get_theme()));
$page = new Page();

// hook up event handlers
_load_event_listeners();

if (AUTO_DB_UPGRADE) {
    send_event(new DatabaseUpgradeEvent());
}
send_event(new InitExtEvent());
$_tracer->end();
