<?php
/**
 * Name: Help Pages
 * Author: Matthew Barbour <matthew@darkholme.net>
 * License: MIT
 * Description: Provides documentation screens
 */

class HelpPageListBuildingEvent extends Event
{
    public $pages = [];

    public function add_page(string $key, string $name)
    {
        $this->pages[$key] = $name;
    }
}

class HelpPageBuildingEvent extends Event
{
    public $key;
    public $blocks = [];

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public function add_block(Block $block, int $position = 50)
    {
        if (!array_key_exists("$position", $this->blocks)) {
            $this->blocks["$position"] = [];
        }
        $this->blocks["$position"][] = $block;
    }
}

class HelpPages extends Extension
{
    public const SEARCH = "search";

    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $user;

        if ($event->page_matches("help")) {
            $e = new HelpPageListBuildingEvent();
            send_event($e);
            $page->set_mode(PageMode::PAGE);

            if ($event->count_args() == 0) {
                $this->theme->display_list_page($e->pages);
            } else {
                $name = $event->get_arg(0);
                $title = $name;
                if (array_key_exists($name, $e->pages)) {
                    $title = $e->pages[$name];
                }

                $this->theme->display_help_page($title);

                $hpbe = new HelpPageBuildingEvent($name);
                send_event($hpbe);
                asort($hpbe->blocks);

                foreach ($hpbe->blocks as $key=>$value) {
                    foreach ($value as $block) {
                        $page->add_block($block);
                    }
                }
            }
        }
    }

    public function onHelpPageListBuilding(HelpPageListBuildingEvent $event)
    {
        $event->add_page("search", "Searching");
    }

    public function onPageNavBuilding(PageNavBuildingEvent $event)
    {
        $event->add_nav_link("help", new Link('help'), "Help");
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event)
    {
        global $user;
        $event->add_link("Help", make_link("help"));
    }
}
