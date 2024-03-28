<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

class UploadSpecificBuildingEvent extends Event
{
    /** @var HTMLElement[] */
    public array $parts = [];
    public string $suffix;

    public function __construct(string $suffix)
    {
        parent::__construct();

        $this->suffix = $suffix;
    }

    public function add_part(HTMLElement $html, int $position = 50): void
    {
        while (isset($this->parts[$position])) {
            $position++;
        }
        $this->parts[$position] = $html;
    }
}
