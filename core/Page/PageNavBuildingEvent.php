<?php

declare(strict_types=1);

namespace Shimmie2;

final class PageNavBuildingEvent extends Event
{
    /** @var NavLink[] */
    public array $links = [];
    public ?NavLink $active_link = null;

    /**
     * @param string[] $matches
     */
    public function add_nav_link(Url $link, string $desc, array $matches = [], ?string $category = null, int $order = 50): void
    {
        $navlink = new NavLink($link, $desc, $matches, $category, $order);

        if ($this->active_link === null && $navlink->active) {
            $this->active_link = $navlink;
        }

        $this->links[] = $navlink;
    }
}
