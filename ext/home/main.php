<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

class Home extends Extension
{
    public const KEY = "home";
    /** @var HomeTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page;
        if ($event->page_matches("home")) {
            $sitename = $config->get_string(SetupConfig::TITLE);

            $body = $this->get_body();

            $this->theme->display_page($page, $sitename, $body);
        }
    }

    private function get_body(): HTMLElement
    {
        // returns just the contents of the body
        global $config;
        $base_href = Url::base();
        $sitename = $config->get_string(SetupConfig::TITLE);
        $contact_link = contact_link();
        if (is_null($contact_link)) {
            $contact_link = "";
        }
        $counter_dir = $config->get_string(HomeConfig::COUNTER, 'default');

        $total = Search::count_images();
        $num_comma = number_format($total);
        $counter_text = "";
        if ($counter_dir != 'none') {
            if ($counter_dir != 'text-only') {
                $strtotal = "$total";
                $length = strlen($strtotal);
                for ($n = 0; $n < $length; $n++) {
                    $cur = $strtotal[$n];
                    $counter_text .= "<img class='counter-img' alt='$cur' src='$base_href/ext/home/counters/$counter_dir/$cur.gif' />";
                }
            }
        }

        // get the homelinks and process them
        if (strlen($config->get_string(HomeConfig::LINKS, '')) > 0) {
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
        $main_links = format_text($main_links);
        $main_text = $config->get_string(HomeConfig::TEXT, '');

        return $this->theme->build_body($sitename, $main_links, $main_text, $contact_link, $num_comma, $counter_text);
    }
}
