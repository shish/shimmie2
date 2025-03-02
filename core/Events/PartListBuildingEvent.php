<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * @template T
 */
abstract class PartListBuildingEvent extends Event
{
    /** @var T[] */
    private array $parts = [];

    /**
     * @param T $html
     */
    public function add_part(mixed $html, int $position = 50): void
    {
        while (isset($this->parts[$position])) {
            $position++;
        }
        $this->parts[$position] = $html;
    }

    /**
     * @return array<T>
     */
    public function get_parts(): array
    {
        ksort($this->parts);
        return $this->parts;
    }
}
