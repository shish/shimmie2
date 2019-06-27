<?php
/*
 * Name: System Info
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Show various bits of system information
 * Documentation:
 *  Knowing the information that this extension shows can be
 *  very useful for debugging. There's also an option to send
 *  your stats to my database, so I can get some idea of how
 *  shimmie is used, which servers I need to support, which
 *  versions of PHP I should test with, etc.
 */

class ET extends Extension
{
    public function onPageRequest(PageRequestEvent $event)
    {
        global $user;
        if ($event->page_matches("system_info")) {
            if ($user->can(Permissions::VIEW_SYSINTO)) {
                $this->theme->display_info_page($this->get_info());
            }
        }
    }


    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event)
    {
        global $user;
        if($event->parent==="system") {
            if ($user->can(Permissions::VIEW_SYSINTO)) {
                $event->add_nav_link("system_info", new Link('system_info'), "System Info", null, 10);
            }
        }
    }


    public function onUserBlockBuilding(UserBlockBuildingEvent $event)
    {
        global $user;
        if ($user->can(Permissions::VIEW_SYSINTO)) {
            $event->add_link("System Info", make_link("system_info"));
        }
    }

    /**
     * Collect the information and return it in a keyed array.
     */
    private function get_info()
    {
        global $config, $database;

        $info = [];
        $info['site_title'] = $config->get_string(SetupConfig::TITLE);
        $info['site_theme'] = $config->get_string(SetupConfig::THEME);
        $info['site_url']   = "http://" . $_SERVER["HTTP_HOST"] . get_base_href();

        $info['sys_shimmie'] = VERSION;
        $info['sys_schema']  = $config->get_string("db_version");
        $info['sys_php']     = phpversion();
        $info['sys_db']      = $database->get_driver_name();
        $info['sys_os']      = php_uname();
        $info['sys_disk']    = to_shorthand_int(disk_total_space("./") - disk_free_space("./")) . " / " .
                               to_shorthand_int(disk_total_space("./"));
        $info['sys_server']  = isset($_SERVER["SERVER_SOFTWARE"]) ? $_SERVER["SERVER_SOFTWARE"] : 'unknown';

        $info[MediaConfig::FFMPEG_PATH]	= $config->get_string(MediaConfig::FFMPEG_PATH);
        $info[MediaConfig::CONVERT_PATH]	= $config->get_string(MediaConfig::CONVERT_PATH);
        $info[MediaConfig::MEM_LIMIT]	= $config->get_int(MediaConfig::MEM_LIMIT);

        $info[ImageConfig::THUMB_ENGINE]	= $config->get_string(ImageConfig::THUMB_ENGINE);
        $info[ImageConfig::THUMB_QUALITY]	= $config->get_int(ImageConfig::THUMB_QUALITY);
        $info[ImageConfig::THUMB_WIDTH]	= $config->get_int(ImageConfig::THUMB_WIDTH);
        $info[ImageConfig::THUMB_HEIGHT]	= $config->get_int(ImageConfig::THUMB_HEIGHT);
        $info[ImageConfig::THUMB_SCALING]	= $config->get_int(ImageConfig::THUMB_SCALING);
        $info[ImageConfig::THUMB_TYPE]	    = $config->get_string(ImageConfig::THUMB_TYPE);

        $info['stat_images']   = $database->get_one("SELECT COUNT(*) FROM images");
        $info['stat_comments'] = $database->get_one("SELECT COUNT(*) FROM comments");
        $info['stat_users']    = $database->get_one("SELECT COUNT(*) FROM users");
        $info['stat_tags']     = $database->get_one("SELECT COUNT(*) FROM tags");
        $info['stat_image_tags'] = $database->get_one("SELECT COUNT(*) FROM image_tags");

        $els = [];
        foreach (get_declared_classes() as $class) {
            $rclass = new ReflectionClass($class);
            if ($rclass->isAbstract()) {
                // don't do anything
            } elseif (is_subclass_of($class, "Extension")) {
                $els[] = $class;
            }
        }
        $info['sys_extensions'] = join(', ', $els);

        //$cfs = array();
        //foreach($database->get_all("SELECT name, value FROM config") as $pair) {
        //	$cfs[] = $pair['name']."=".$pair['value'];
        //}
        //$info[''] = "Config: ".join(", ", $cfs);

        return $info;
    }
}
