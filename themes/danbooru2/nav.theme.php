<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, LI, UL};

use MicroHTML\HTMLElement;

class Danbooru2NavTheme extends NavTheme
{
    public function display_main_links(NavBuildingEvent $event, array $links): void
    {
        $html = $this->render_main_links($links);
        Ctx::$page->add_block(new Block(null, $html, "nav", 0, is_content: false));
    }

    public function display_sub_links(NavBuildingEvent $event, array $links): void
    {
        $html = $this->render_sub_links($links);
        Ctx::$page->add_block(new Block(null, $html, "nav", 1, is_content: false));
    }

    public function render_main_links(array $links): HTMLElement
    {
        $list = UL(["id" => "navbar", "class" => "flat-list"]);
        foreach ($links as $link) {
            $list->appendChild(LI($this->render_link($link)));
        }

        return $list;
    }

    public function render_sub_links(array $links): HTMLElement
    {
        $list = UL(["id" => "subnavbar", "class" => "flat-list"]);
        foreach ($links as $link) {
            $list->appendChild(LI($this->render_link($link)));
        }

        return $list;
    }

    public function render_link(NavLink $link): HTMLElement
    {
        return A([
            "class" => $link->active ? "tab-selected" : "tab",
            "href" => $link->link,
        ], $link->description);
    }
}
