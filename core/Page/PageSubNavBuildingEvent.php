<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

final class PageSubNavBuildingEvent extends Event
{
    /** @var NavLink[] */
    public array $links = [];
    public ?NavLink $active_link = null;

    public function __construct(
        public string $parent
    ) {
        parent::__construct();
    }

    /**
     * @param string[] $matches
     */
    public function add_nav_link(Url $link, string|HTMLElement $desc, string $key, array $matches = [], int $order = 50): void
    {
        $navlink = new NavLink($link, $desc, $key, $matches, $order, parent: $this->parent);

        if ($this->active_link === null && $navlink->active) {
            $this->active_link = $navlink;
        }

        $this->links[] = $navlink;
    }
}
