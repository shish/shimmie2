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
    public function add_nav_link(Url $link, string $desc, string $key, array $matches = [], int $order = 50): void
    {
        $navlink = new NavLink($link, $desc, $key, $matches, $order);

        if ($this->active_link === null && $navlink->active) {
            $this->active_link = $navlink;
        }

        $this->links[] = $navlink;
    }
}
