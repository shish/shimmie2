<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

final class Navigation
{
    public string $title = "Navigation";

    public ?Url $prev = null;
    public ?Url $index = null;
    public ?Url $next = null;

    /** @var array<array{html: HTMLElement|string, position: int}> */
    protected array $parts = [];

    public function set_prev(?Url $prev): void
    {
        $this->prev = $prev;
    }

    public function set_index(?Url $index): void
    {
        $this->index = $index;
    }

    public function set_next(?Url $next): void
    {
        $this->next = $next;
    }

    /**
     * @return array<array{html: HTMLElement|string, position: int}>
     */
    public function get_parts(): array
    {
        return $this->parts;
    }

    public function add_part(HTMLElement|string $html, int $position = 50): void
    {
        $this->parts[] = ["html" => $html, "position" => $position];
    }
}
