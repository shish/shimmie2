<?php

class CustomHtmlHeaders extends Extension
{
    # Adds setup block for custom <head> content
    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = new SetupBlock("Custom HTML Headers");

        // custom headers
        $sb->add_longtext_option(
            "custom_html_headers",
            "HTML Code to place within &lt;head&gt;&lt;/head&gt; on all pages<br>"
        );

        // modified title
        $sb->add_choice_option("sitename_in_title", [
                    "none" => 0,
                    "as prefix" => 1,
                    "as suffix" => 2
                    ], "<br>Add website name in title");

        $event->panel->add_block($sb);
    }

    public function onInitExt(InitExtEvent $event)
    {
        global $config;
        $config->set_default_int("sitename_in_title", 0);
    }

    # Load Analytics tracking code on page request
    public function onPageRequest(PageRequestEvent $event)
    {
        $this->handle_custom_html_headers();
        $this->handle_modified_page_title();
    }

    private function handle_custom_html_headers()
    {
        global $config, $page;

        $header = $config->get_string('custom_html_headers', '');
        if ($header!='') {
            $page->add_html_header($header);
        }
    }

    private function handle_modified_page_title()
    {
        global $config, $page;

        // get config values
        $site_title = $config->get_string(SetupConfig::TITLE);
        $sitename_in_title = $config->get_int("sitename_in_title");

        // if feature is enabled & sitename isn't already in title
        // (can occur on index & other pages)
        if ($sitename_in_title != 0 && !strstr($page->title, $site_title)) {
            if ($sitename_in_title == 1) {
                $page->title = "$site_title - $page->title";
            } // as prefix
            elseif ($sitename_in_title == 2) {
                $page->title = "$page->title - $site_title";
            } // as suffix
        }
    }
}
