<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

class PageSubNavBuildingEvent extends Event
{
    public string $parent;

    /** @var NavLink[] */
    public array $links = [];

    public function __construct(string $parent)
    {
        parent::__construct();
        $this->parent = $parent;
    }

    /**
     * @param url-string $link
     * @param string[] $matches
     */
    public function add_nav_link(string $link, string|HTMLElement $desc, array $matches = [], int $order = 50): void
    {
        $this->links[]  = new NavLink($link, $desc, $matches, null, $order);
    }
}
