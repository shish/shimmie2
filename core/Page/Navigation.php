<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, BR, emptyHTML, joinHTML};

use MicroHTML\HTMLElement;

final class Navigation
{
    public string $title = "Navigation";
    public bool $isPaginated = false;
    public ?Url $prev = null;
    public ?Url $index = null;
    public ?Url $next = null;

    /** @var array<array{0: HTMLElement|string, 1: int}> */
    public array $extras = [];

    public function set_prev(?Url $prev): void
    {
        $this->isPaginated = true;
        $this->prev = $prev;
    }

    public function set_index(?Url $index): void
    {
        $this->index = $index;
    }

    public function set_next(?Url $next): void
    {
        $this->isPaginated = true;
        $this->next = $next;
    }

    public function add_extra(HTMLElement|string $html, int $position = 50): void
    {
        $this->extras[] = [$html, $position];
    }

    /**
     * @return array<array{0: HTMLElement|string, 1: int}>
     */
    public function get_extras(): array
    {
        return $this->extras;
    }

    public function render(): HTMLElement
    {
        $html = emptyHTML();
        if ($this->isPaginated) {
            $html->appendChild(joinHTML(" | ", [
                $this->prev === null ? "Prev" : A(["href" => $this->prev, "class" => "prevlink"], "Prev"),
                A(["href" => $this->index ?? make_link()], "Index"),
                $this->next === null ? "Next" : A(["href" => $this->next, "class" => "nextlink"], "Next"),
            ]));
        } else {
            $html->appendChild(A(["href" => $this->index ?? make_link()], "Index"));
        }

        if (\count($this->extras) > 0) {
            usort($this->extras, fn ($a, $b) => $a[1] - $b[1]);
            $html->appendChild(BR(), joinHTML(BR(), array_column($this->extras, "0")));
        }

        return $html;
    }
}
