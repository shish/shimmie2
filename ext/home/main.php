<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

final class Home extends Extension
{
    public const KEY = "home";
    /** @var HomeTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page;
        if ($event->page_matches("home")) {
            $this->theme->display_page(
                $config->get_string(SetupConfig::TITLE),
                $this->get_body()
            );
        }
    }

    private function get_body(): HTMLElement
    {
        // returns just the contents of the body
        global $config;

        // get the homelinks and process them
        if (!empty($config->get_string(HomeConfig::LINKS))) {
            $main_links = $config->get_string(HomeConfig::LINKS);
        } else {
            $main_links = '[url=site://post/list]Posts[/url][url=site://comment/list]Comments[/url][url=site://tags]Tags[/url]';
            if (PoolsInfo::is_enabled()) {
                $main_links .= '[url=site://pool/list]Pools[/url]';
            }
            if (WikiInfo::is_enabled()) {
                $main_links .= '[url=site://wiki]Wiki[/url]';
            }
            $main_links .= '[url=site://ext_doc]Documentation[/url]';
        }

        return $this->theme->build_body(
            $config->get_string(SetupConfig::TITLE),
            format_text($main_links),
            $config->get_string(HomeConfig::TEXT),
            contact_link(),
            Search::count_images(),
        );
    }
}
