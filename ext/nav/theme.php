<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, BR, SPAN, emptyHTML, joinHTML};

use MicroHTML\HTMLElement;

class NavTheme extends Themelet
{
    public function display_navigation_block(NavBuildingEvent $event): void
    {
        $html = joinHTML(BR(), $event->get_parts());
        Ctx::$page->add_block(new Block("Navigation", $html, "left", 0, is_content: false));
    }

    /**
     * @param NavLink[] $links
     */
    public function display_main_links(NavBuildingEvent $event, array $links): void
    {
        $event->add_part($this->render_main_links($links));
    }

    /**
     * @param NavLink[] $links
     */
    public function display_sub_links(NavBuildingEvent $event, array $links): void
    {
        $event->add_part($this->render_sub_links($links), 20);
    }

    public function display_nav_pagination(NavBuildingEvent $event, ?Url $prev, Url $index, ?Url $next): void
    {
        $html = emptyHTML();

        if ($prev !== null || $next !== null) {
            $html->appendChild(joinHTML(" | ", [
                $prev === null ? "Prev" : A(["href" => $prev, "class" => "prevlink"], "Prev"),
                A(["href" => $index], "Index"),
                $next === null ? "Next" : A(["href" => $next, "class" => "nextlink"], "Next"),
            ]));
        } else {
            $html->appendChild(A(["href" => $index], "Index"));
        }

        $event->add_part($html, 0);
    }

    /**
     * @param NavLink[] $links
     */
    public function render_main_links(array $links): HTMLElement
    {
        $html = emptyHTML();
        foreach ($links as $link) {
            $html->appendChild($this->render_link($link), BR());
        }
        return $html;
    }

    /**
     * @param NavLink[] $links
     */
    public function render_sub_links(array $links): HTMLElement
    {
        $html = emptyHTML();
        foreach ($links as $link) {
            $html->appendChild($this->render_link($link), BR());
        }
        return $html;
    }

    public function render_link(NavLink $link): HTMLElement
    {
        return A(
            [
                "href" => $link->link,
                ... $link->active ? ["class" => "active"] : [],
            ],
            SPAN(
                $link->description
            )
        );
    }
}
