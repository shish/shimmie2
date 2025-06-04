<?php

declare(strict_types=1);

namespace Shimmie2;

final class TermTracker
{
    /** @var array<TermTracker|TagCondition|ImgCondition> $children */
    public array $children;
    public int $childcount = 0;
    public bool $disjunctive = false;
    public bool $negative = false;
    public bool $all_disjunctive = true;
    private int $trav_i = 0;

    public function __construct(
        private ?TermTracker $parent = null
    ) {
    }

    public function getparent(): TermTracker
    {
        if (is_null($this->parent)) {
            throw new InvalidInput("No group parent found in search query");
        }
        return $this->parent;
    }

    public function first(): TermTracker|TagCondition|ImgCondition
    {
        $child = $this->children[0];
        return $child instanceof TermTracker ? $child->first() : $child;
    }

    public function appendgroup(TermTracker $child): TermTracker
    {
        $this->children[] = $child;
        $this->all_disjunctive = false;
        $this->childcount++;
        return $child;
    }

    public function appendchild(TagCondition|ImgCondition $child): TagCondition|ImgCondition
    {
        $this->children[] = $child;
        if ($child instanceof TagCondition && !$child->disjunctive) {
            $this->all_disjunctive = false;
        }
        $this->childcount++;
        return $child;
    }

    public function iterate_all(callable $func): void
    {
        foreach ($this->children as $child) {
            if ($child instanceof TermTracker) {
                $child->iterate_all($func);
            } else {
                $func($child);
            }
        }
    }

    public function traverse(): TermTracker|TagCondition|ImgCondition|null
    {
        if ($this->trav_i > $this->childcount) {
            return null;
        }
        return $this->children[$this->trav_i++];
    }
}
