<?php

declare(strict_types=1);

namespace Shimmie2;

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

    # Load Analytics tracking code on page request
    public function onPageRequest(PageRequestEvent $event): void
    {
        $this->handle_custom_html_headers();
        $this->handle_modified_page_title();
    }

    private function handle_custom_html_headers(): void
    {
        global $config, $page;

        $header = $config->get_string('custom_html_headers', '');
        if ($header != '') {
            $page->add_html_header($header);
        }
    }

    private function handle_modified_page_title(): void
    {
        global $config, $page;

        // get config values
        $site_title = $config->get_string(SetupConfig::TITLE);
        $sitename_in_title = $config->get_string("sitename_in_title");

        // sitename is already in title (can occur on index & other pages)
        if (str_contains($page->title, $site_title)) {
            return;
        }

        if ($sitename_in_title == "prefix") {
            $page->title = "$site_title - $page->title";
        } elseif ($sitename_in_title == "suffix") {
            $page->title = "$page->title - $site_title";
        }
    }
}
