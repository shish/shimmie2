<?php

declare(strict_types=1);

namespace Shimmie2;

class UploadHeaderBuildingEvent extends Event
{
    /** @var string[] */
    public array $parts = [];

    public function add_part(string $string, int $position = 50): void
    {
        while (isset($this->parts[$position])) {
            $position++;
        }
        $this->parts[$position] = $string;
    }
}
