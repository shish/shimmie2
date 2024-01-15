<?php

declare(strict_types=1);

namespace Shimmie2;

class SiteDescription extends Extension
{
    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page;
        if (!empty($config->get_string("site_description"))) {
            $description = $config->get_string("site_description");
            $page->add_html_header("<meta name=\"description\" content=\"$description\">");
        }
        if (!empty($config->get_string("site_keywords"))) {
            $keywords = $config->get_string("site_keywords");
            $page->add_html_header("<meta name=\"keywords\" content=\"$keywords\">");
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Site Description");
        $sb->add_text_option("site_description", "Description: ");
        $sb->add_text_option("site_keywords", "<br>Keywords: ");
    }
}
