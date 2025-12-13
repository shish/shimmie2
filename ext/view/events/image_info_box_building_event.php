<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

/**
 * @extends PartListBuildingEvent<HTMLElement>
 */
class ImageInfoBoxBuildingEvent extends PartListBuildingEvent
{
    /** @var HTMLElement[] */
    private array $sidebar_parts = [];

    public function __construct(
        public Image $image,
        public User $user
    ) {
        parent::__construct();
    }

    /**
     * Add content to the right-hand sidebar of the info box
     */
    public function add_sidebar_part(HTMLElement $html, int $position = 50): void
    {
        while (isset($this->sidebar_parts[$position])) {
            $position++;
        }
        $this->sidebar_parts[$position] = $html;
    }

    /**
     * @return array<HTMLElement>
     */
    public function get_sidebar_parts(): array
    {
        ksort($this->sidebar_parts);
        return $this->sidebar_parts;
    }
}
