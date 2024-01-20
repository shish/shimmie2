<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

class ImageInfoBoxBuildingEvent extends Event
{
    /** @var HTMLElement[] */
    public array $parts = [];
    public Image $image;
    public User $user;

    public function __construct(Image $image, User $user)
    {
        parent::__construct();
        $this->image = $image;
        $this->user = $user;
    }

    public function add_part(HTMLElement $html, int $position = 50): void
    {
        while (isset($this->parts[$position])) {
            $position++;
        }
        $this->parts[$position] = $html;
    }
}
