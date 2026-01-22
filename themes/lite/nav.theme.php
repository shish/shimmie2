<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, B, DIV, IMG};

use MicroHTML\HTMLElement;

class LiteNavTheme extends NavTheme
{
    public function display_main_links(NavBuildingEvent $event, array $links): void
    {
        $site_name = Ctx::$config->get(SetupConfig::TITLE);
        $data_href = Url::base();

        $html = DIV(
            ["class" => "menu"],
            A(
                ["href" => make_link()],
                IMG(["title" => "Home", "src" => "{$data_href}/favicon.ico", "style" => "position: relative; top: 3px;"])
            ),
            B($site_name),
            $this->render_main_links($links)
        );

        Ctx::$page->add_block(new Block(null, $html, "nav", 0, is_content: false));
    }

    public function display_sub_links(NavBuildingEvent $event, array $links): void
    {
        $html = $this->render_sub_links($links);
        Ctx::$page->add_block(new Block(null, $html, "nav", 1, is_content: false));
    }

    public function render_main_links(array $links): HTMLElement
    {
        $list = DIV(["class" => "bar"]);
        foreach ($links as $link) {
            $list->appendChild($this->render_link($link));
        }

        return $list;
    }

    public function render_sub_links(array $links): HTMLElement
    {
        $list = DIV(["class" => "sbar"]);
        foreach ($links as $link) {
            $list->appendChild($this->render_link($link));
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
