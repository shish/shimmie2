<?php

declare(strict_types=1);

namespace Shimmie2;

class Holiday extends Extension
{
    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_bool("holiday_aprilfools", false);
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Holiday Theme");
        $sb->add_bool_option("holiday_aprilfools", "Enable April Fools");
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page;
        if (date('d/m') == '01/04' && $config->get_bool("holiday_aprilfools")) {
            $page->add_html_header(
                "<link rel='stylesheet' href='".get_base_href()."/ext/holiday/stylesheets/aprilfools.css' type='text/css'>"
            );
        }
    }
}
