<?php

declare(strict_types=1);

namespace Shimmie2;

global $rc;
$rc = 0;
final class TermTracker
{
    /** @var array<TermTracker|TagCondition|ImgCondition> $children */
    public array $children = [];
    public int $childcount = 0;
    private int $childcount_sort = 0;
    public bool $disjunctive = false;
    public bool $negative = false;
    public bool $all_disjunctive = true;
    public bool $all_disjunctive_tags = true;
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
        $this->all_disjunctive_tags = false;
        if ($child->disjunctive) {
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
            $this->all_disjunctive_tags = false;
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
        $this->no_child_groups = true;
        foreach ($this->children as $c) {
            if ($c instanceof TermTracker) {
                $this->no_child_groups = false;
                $this->all_disjunctive_tags = false;
            }
            if (!$c->disjunctive ) {
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
                if ($child->all_disjunctive && $this->childcount_sort === 1) { // try to bring disjunction up, but careful to not mix
                    foreach ($child->children as $c) {
                        $c->disjunctive = ($child->disjunctive || $c->disjunctive); // pass on the disjunctive sign
                        $c->negative = ($child->negative xor $c->negative); // pass on the negative sign
                        $this->appendchild_sort($c);
                    }
                    unset($this->children[$i]);
                    $this->childcount_sort--; // because of the removal, no need to add because appendchild does already
                    $this->check_all_dis();
                } elseif ($child->childcount_sort === 1) { // can always bring up if there is only one child (i think?)
                    $new = array_shift($child->children);
                    $new->disjunctive = ($child->disjunctive || $new->disjunctive); // pass on the disjunctive sign
                    $new->negative = ($child->negative xor $new->negative); // pass on the negative sign
                    $this->children[$i] = $new;
                    $this->check_all_dis();
                } elseif ($child->childcount_sort === 0) { // begone emptiness!
                    unset($this->children[$i]);
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

    public function generate_sql(int $pi = 0): string {
        global $rc;
        $q = "";
        $mi = $rc++;
        // TODO negative
        // TODO img_condition
        if ($this->all_disjunctive_tags) {
            $tag_ids = [];
            foreach ($this->children as $c) {
                $tag_ids = array_merge($tag_ids, search::tag_or_wildcard_to_ids($c->tag));
            }
            $tc = count($tag_ids);
            if (count($tag_ids) <= 0) {
                // uuuuh
            } elseif ($tc === 1) {
                $tag_id = array_shift($tag_ids);
                $in = "= $tag_id";
            } else {
                $tag_list = join(', ', $tag_ids);
                $in = "IN ($tag_list)";
            }
            
            if ($this->disjunctive) {
                $q = " SELECT DISTINCT it$mi.image_id FROM image_tags it$mi
                WHERE it$mi.tag_id IN ($tag_list) ";
            } else {
                $q = " INNER JOIN image_tags it$mi ON it$mi.image_id = it$pi.image_id AND it$mi.tag_id $in ";
            }
            
        } 
        elseif ($this->all_disjunctive) {
            $tag_ids = [];
            $dis_group_sql = [];
            foreach ($this->children as $c) {
                if ($c instanceof TermTracker) {
                    $dis_group_sql[] = $c->generate_sql($mi);
                } else {
                    $tag_ids = array_merge($tag_ids, search::tag_or_wildcard_to_ids($c->tag));
                }
            }
            $tc = count($tag_ids);
            if ($tc > 0){
                if ($tc === 1) {
                    $tag_id = array_shift($tag_ids);
                    $q = " SELECT DISTINCT it$mi.image_id FROM image_tags it$mi
                        WHERE it$mi.tag_id = $tag_id ";
                } else {
                    $tag_list = join(', ', $tag_ids);
                    $q = " SELECT DISTINCT it$mi.image_id FROM image_tags it$mi
                        WHERE it$mi.tag_id IN ($tag_list) ";
                }
                
                foreach($dis_group_sql as $dsql) {
                    $q .= " UNION $dsql ";
                } 
            } else {
                $q .= join(" UNION ", $dis_group_sql);
            }
            
        } 
        else {
            $q = " SELECT DISTINCT it$mi.image_id FROM image_tags it$mi ";
            $dis_tag_ids = [];
            $dis_group_sql = [];
            $and_group_sql = [];
            foreach ($this->children as $c) {
                if ($c instanceof TermTracker) {
                    if ($c->disjunctive) {
                        $dis_group_sql[] = $c->generate_sql($mi);
                    } elseif ($c->all_disjunctive_tags) {
                        $q .= $c->generate_sql($mi);
                    } else {
                        $and_group_sql[] = $c->generate_sql($mi);
                    }
                } else {
                    if ($c->disjunctive) {
                        $dis_tag_ids = array_merge($dis_tag_ids, search::tag_or_wildcard_to_ids($c->tag));
                    } else {
                        $ids = search::tag_or_wildcard_to_ids($c->tag);
                        $i = $rc++;
                        if (count($ids) > 1) {
                            $tag_list = join(', ', $ids);
                            $q .= " INNER JOIN image_tags it$i ON it$mi.image_id = it$mi.image_id AND it$i.tag_id IN ($tag_list) ";
                        } else {
                            $q .= " INNER JOIN image_tags it$i ON it$i.image_id = it$mi.image_id AND it$i.tag_id = $ids[0] ";
                        }
                    }
                }
            }
            if (count($dis_tag_ids) > 1){
                $i = $rc++;
                $tag_list = join(', ', $dis_tag_ids);
                $q .= " INNER JOIN image_tags it$i ON it$i.image_id = it$mi.image_id AND it$i.tag_id IN ($tag_list) ";
            }
            foreach($and_group_sql as $asql) {
                $i = $rc++;
                $q .= " INNER JOIN ( $asql ) b$i on b$i.image_id = it$mi.image_id";
            }
            foreach($dis_group_sql as $dsql) {
                $q .= " UNION $dsql ";
            }
        }
        // TODO make sure every query has a WHERE clause

        return $q;
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
