<?php

declare(strict_types=1);

namespace Shimmie2;

class PageNavBuildingEvent extends Event
{
    /** @var NavLink[] */
    public array $links = [];

    /**
     * @param url-string $link
     * @param string[] $matches
     */
    public function add_nav_link(string $link, string $desc, array $matches = [], ?string $category = null, int $order = 50): void
    {
        $this->links[]  = new NavLink($link, $desc, $matches, $category, $order);
    }
}
