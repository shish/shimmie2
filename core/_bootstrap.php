<?php
/*
 * Load all the files into memory, sanitise the environment, but don't
 * actually do anything as far as the app is concerned
 */

global $config, $database, $user, $page, $_shm_ctx;

require_once "core/sys_config.php";
require_once "core/polyfills.php";
require_once "core/util.php";
require_once "vendor/shish/libcontext-php/context.php";
require_once "vendor/autoload.php";

// set up and purify the environment
_version_check();
_sanitise_environment();

// load base files
$_shm_ctx->log_start("Bootstrap");
$_shm_ctx->log_start("Opening files");
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
$_shm_ctx->log_endok();

// connect to the database
$_shm_ctx->log_start("Connecting to DB");
$database = new Database();
$config = new DatabaseConfig($database);
$_shm_ctx->log_endok();

// load the theme parts
$_shm_ctx->log_start("Loading themelets");
foreach (_get_themelet_files(get_theme()) as $themelet) {
    require_once $themelet;
}
unset($themelet);
$page = class_exists("CustomPage") ? new CustomPage() : new Page();
$_shm_ctx->log_endok();

// hook up event handlers
$_shm_ctx->log_start("Loading extensions");
_load_event_listeners();
$_shm_ctx->log_endok();

send_event(new InitExtEvent());
$_shm_ctx->log_endok();
