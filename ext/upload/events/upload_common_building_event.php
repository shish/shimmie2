<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

class UploadCommonBuildingEvent extends Event
{
    /** @var HTMLElement[] */
    public array $parts = [];

    public function add_part(HTMLElement $html, int $position = 50): void
    {
        while (isset($this->parts[$position])) {
            $position++;
        }
        $this->parts[$position] = $html;
    }
}
