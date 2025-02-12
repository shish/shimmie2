<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\rawHTML;

class CustomHtmlHeaders extends Extension
{
    # Adds setup block for custom <head> content
    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Custom HTML Headers");

        // custom headers
        $sb->add_longtext_option(
            "custom_html_headers",
            "HTML Code to place within &lt;head&gt;&lt;/head&gt; on all pages<br>"
        );

        // modified title
        $sb->add_choice_option("sitename_in_title", [
            "none" => "none",
            "as prefix" => "prefix",
            "as suffix" => "suffix"
        ], "<br>Add website name in title");
    }

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_string("sitename_in_title", "none");
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page;

        $header = $config->get_string('custom_html_headers', '');
        if ($header != '') {
            $page->add_html_header(rawHTML($header));
        }

        // check sitename is not already in title (can occur on index & other pages)
        $site_title = $config->get_string(SetupConfig::TITLE);
        $sitename_in_title = $config->get_string("sitename_in_title");
        if (!str_contains($page->title, $site_title)) {
            if ($sitename_in_title == "prefix") {
                $page->title = "$site_title - $page->title";
            } elseif ($sitename_in_title == "suffix") {
                $page->title = "$page->title - $site_title";
            }
        }
    }
}
