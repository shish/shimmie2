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
    public function add_nav_link(string $name, string $link, string $desc, array $matches = [], int $order = 50): void
    {
        $this->links[]  = new NavLink($name, $link, $desc, $matches, $order);
    }
}
