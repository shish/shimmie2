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

    /** @param url-string $link */
    public function add_nav_link(string $name, string $link, string|HTMLElement $desc, ?bool $active = null, int $order = 50): void
    {
        $this->links[]  = new NavLink($name, $link, $desc, $active, $order);
    }
}
