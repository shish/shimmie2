<?php
/*
 * Load all the files into memory, sanitise the environment, but don't
 * actually do anything as far as the app is concerned
 */

global $config, $database, $user, $page, $_tracer;

require_once "core/sys_config.php";
require_once "core/polyfills.php";
require_once "core/util.php";
require_once "vendor/autoload.php";

// set up and purify the environment
_version_check();
_sanitise_environment();

// load base files
$_tracer->begin("Bootstrap");
$_tracer->begin("Opening files");
$_shm_files = array_merge(
    zglob("core/*.php"),
    zglob("core/{".ENABLED_MODS."}/*.php"),
    zglob("ext/{".ENABLED_EXTS."}/main.php")
);
foreach ($_shm_files as $_shm_filename) {
    if (basename($_shm_filename)[0] != "_") {
        require_once $_shm_filename;
    }
}
unset($_shm_files);
unset($_shm_filename);
$_tracer->end();

// connect to the database
$_tracer->begin("Connecting to DB");
$database = new Database();
$config = new DatabaseConfig($database);
$_tracer->end();

// load the theme parts
$_tracer->begin("Loading themelets");
foreach (_get_themelet_files(get_theme()) as $themelet) {
    require_once $themelet;
}
unset($themelet);
$page = class_exists("CustomPage") ? new CustomPage() : new Page();
$_tracer->end();

$_shm_ctx->log_start("Loading user information");
$user = _get_user();
$user_config = new DatabaseConfig($database, "user_config","user_id", $user->id);
$_shm_ctx->log_endok();

// hook up event handlers
$_tracer->begin("Loading extensions");
_load_event_listeners();
$_tracer->end();

send_event(new InitExtEvent());
$_tracer->end();
