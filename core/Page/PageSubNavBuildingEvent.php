<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

final class PageSubNavBuildingEvent extends Event
{
    /** @var NavLink[] */
    public array $links = [];

    public function __construct(
        public string $parent
    ) {
        parent::__construct();
    }

    /**
     * @param string[] $matches
     */
    public function add_nav_link(Url $link, string|HTMLElement $desc, array $matches = [], int $order = 50): void
    {
        $this->links[] = new NavLink($link, $desc, $matches, null, $order);
    }
}
