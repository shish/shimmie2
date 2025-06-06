<?php

declare(strict_types=1);

namespace Shimmie2;

final class TermTracker
{
    /** @var array<TermTracker|TagCondition|ImgCondition> $children */
    public array $children;
    public int $childcount = 0;
    private int $childcount_sort = 0;
    public bool $disjunctive = false;
    public bool $negative = false;
    public bool $all_disjunctive = true;
    public bool $has_disjunctive = false;
    public bool $no_child_groups = true;
    private int $trav_i = 0;

    public function __construct(
        private ?TermTracker $parent = null
    ) {
    }

    public function getparent(): ?TermTracker
    {
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
        $this->no_child_groups = false;
        if (!$child->disjunctive) {
            $this->all_disjunctive = false;
        } else {
            $this->has_disjunctive = true;
        }
        $this->childcount++;
        return $child;
    }

    public function appendchild(TagCondition|ImgCondition $child): TagCondition|ImgCondition
    {
        $this->children[] = $child;
        if (!$child->disjunctive) {
            $this->all_disjunctive = false;
        } else {
            $this->has_disjunctive = true;
        }
        $this->childcount++;
        return $child;
    }

    public function iterate_all(callable $func, mixed ...$params): void
    {
        foreach ($this->children as $child) {
            if ($child instanceof TermTracker) {
                $child->iterate_all($func, ...$params);
            } else {
                $func($child, ...$params);
            }
        }
    }

    public function traverse(): TermTracker|TagCondition|ImgCondition|null
    {
        if ($this->trav_i >= $this->childcount) {
            return null;
        }
        return $this->children[$this->trav_i++];
    }

    private function appendchild_sort(TermTracker|TagCondition|ImgCondition $child): void
    {
        $this->children[] = $child;
        $this->childcount++;
        $this->childcount_sort++;
    }

    public function check_all_dis(): void
    {
        $this->all_disjunctive = true;
        $this->has_disjunctive = false;
        foreach ($this->children as $c) {
            if (!$c->disjunctive || $c instanceof TermTracker) {
                $this->all_disjunctive = false;
            } elseif ($c->disjunctive) {
                $this->has_disjunctive = true;
            }
        }
    }

    /* simplify the query as much as possible without colliding*/
    public function simplify(): void
    {
        $this->childcount_sort = $this->childcount;
        for ($i = 0; $i < $this->childcount; $i++) {
            $child = $this->children[$i];
            if ($child instanceof TermTracker) { // keep recursion going
                $child->simplify();
                if ($child->all_disjunctive && !$this->has_disjunctive) { // try to bring disjunction up, but careful to not mix
                    foreach ($child->children as $c) {
                        $this->appendchild_sort($c);
                    }
                    unset($this->children[$i]);
                    $this->check_all_dis();
                } elseif ($child->childcount_sort <= 1) { // can always bring up if there is only one child
                    $new = array_shift($child->children);
                    $new->disjunctive = ($child->disjunctive || $new->disjunctive); // pass on the disjunctive sign
                    $this->children[$i] = $new;
                    $this->check_all_dis();
                }
            } elseif (!is_null($this->parent) && (!($this->disjunctive || $child->disjunctive))) { // if i or my child is disjunctive, do not move the child
                $child->negative = ($child->negative xor $this->negative); // pass on the negative sign
                $this->parent->appendchild_sort($child);
                unset($this->children[$i]);
                $this->check_all_dis();
                $this->childcount_sort--;
            }
        }
        $this->childcount = count($this->children); // not keeping great track, but it works
    }

    public function create_search_string(int $depth = 0): string
    {
        $output = "";
        if ($depth > 0) {
            $output = "( ";
        }
        foreach ($this->children as $child) {
            if ($child instanceof TermTracker) {
                $operators = ($child->disjunctive ? "~" : "") . ($child->negative ? "-" : "");
                $output .= $operators;
                $output .= $child->create_search_string($depth + 1);
            } elseif ($child instanceof TagCondition) {
                $operators = ($child->disjunctive ? "~" : "") . ($child->negative ? "-" : "");
                $output .= "$operators$child->tag ";
            } else {
                $operators = ($child->disjunctive ? "~" : "") . ($child->negative ? "-" : "");
                $output .= "$operators imgcondition "; // TODO get the original imgcondition string
            }
        }
        if ($depth > 0) {
            $output .= " ) ";
        }
        return $output;
    }
}
