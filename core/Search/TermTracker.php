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
            if (!$c->disjunctive) {
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

    public function everything_sql(int $mi = 0): string
    {
        global $rc;
        $q = " SELECT DISTINCT it$mi.image_id FROM image_tags it$mi ";
        $extra_q = "";
        $pos_tag_ids = [];
        $neg_tag_ids = [];
        $dis_pos_tag_ids = [];
        $dis_neg_tags = [];
        $dis_pos_group_sql = [];
        $dis_neg_group_sql = [];
        $and_pos_group_sql = [];
        $and_neg_group_sql = [];
        foreach ($this->children as $c) {
            if ($c instanceof TermTracker) {
                $sql = $c->generate_sql($mi);
                if (!empty($sql)) {
                    if ($c->disjunctive) {
                        if ($c->negative) {
                            $dis_neg_group_sql[] = $sql;
                        } else {
                            $dis_pos_group_sql[] = $sql;
                        }
                    } elseif ($c->all_disjunctive_tags) {
                        $q .= $sql;
                    } else { // and
                        if ($c->negative) {
                            $and_neg_group_sql[] = $sql;
                        } else {
                            $and_pos_group_sql[] = $sql;
                        }
                    }
                }
            } elseif ($c instanceof ImgCondition) {
                echo $c->qlet->sql;
            } else {
                $tag_ids = search::tag_or_wildcard_to_ids($c->tag);
                if ($c->disjunctive) {
                    if ($c->negative) {
                        $tc = count($tag_ids);
                        if ($tc === 1) {
                            $tag_id = array_shift($tag_ids);
                            $dis_neg_tags[] = "= $tag_id";
                        } elseif ($tc > 1) {
                            $tag_list = join(', ', $tag_ids);
                            $dis_neg_tags[] = "IN ($tag_list)";
                        }
                    } else {
                        $dis_pos_tag_ids = array_merge($dis_pos_tag_ids, $tag_ids);
                    }
                } else {
                    if ($c->negative) {
                        $neg_tag_ids = array_merge($neg_tag_ids, $tag_ids);
                    } else {
                        if (!empty($tag_ids)) {
                            $pos_tag_ids[] = $tag_ids;
                        }
                    }
                }
            }
        }
        if (!empty($pos_tag_ids)) {
            $first = array_shift($pos_tag_ids);
            if (count($first) > 1) {
                $tag_list = join(', ', $tag_ids);
                $extra_q .= " WHERE it$mi.tag_id IN ($tag_list) ";
            } else {
                $tag = array_shift($tag_ids);
                $extra_q .= " WHERE it$mi.tag_id = $tag ";
            }

            foreach ($pos_tag_ids as $tag_ids) {
                $i = $rc++;
                if (count($tag_ids) > 1) {
                    $tag_list = join(', ', $tag_ids);
                    $q .= " INNER JOIN image_tags it$i ON it$i.image_id = it$mi.image_id AND it$i.tag_id IN ($tag_list) ";
                } else {
                    $tag = array_shift($tag_ids);
                    $q .= " INNER JOIN image_tags it$i ON it$i.image_id = it$mi.image_id AND it$i.tag_id = $tag ";
                }
            }
        } elseif (!empty($dis_pos_tag_ids)) {
            $tag_list = join(', ', $dis_pos_tag_ids);
            $extra_q .= " WHERE it$mi.tag_id IN ($tag_list) ";
        } else {
            $extra_q .= " WHERE 1=1 ";
        }

        if (!empty($neg_tag_ids)) {
            $i = $rc++;
            $tag_list = join(', ', $neg_tag_ids);
            $q .= " LEFT JOIN image_tags neg$i ON neg$i.image_id = it$mi.image_id AND neg$i.tag_id IN ($tag_list) ";
            $extra_q .= " AND neg$i.image_id IS NULL ";
        }

        if (!empty($dis_pos_tag_ids)) {
            $i = $rc++;
            $tag_list = join(', ', $dis_pos_tag_ids);
            $q .= " INNER JOIN image_tags it$i ON it$i.image_id = it$mi.image_id AND it$i.tag_id IN ($tag_list) ";
        }

        foreach ($and_pos_group_sql as $asql) {
            $i = $rc++;
            $q .= " INNER JOIN ( $asql ) b$i on b$i.image_id = it$mi.image_id ";
        }
        foreach ($and_neg_group_sql as $sql) {
            $i = $rc++;
            $q .= " LEFT JOIN ( $sql ) neg$i ON neg$i.image_id = it$mi.image_id ";
            $extra_q .= " AND neg$i.image_id IS NULL ";
        }
        $q .= $extra_q;
        foreach ($dis_pos_group_sql as $dsql) {
            $q .= " UNION $dsql ";
        }

        foreach ($dis_neg_tags as $in) {
            $i = $rc++;
            $q .= " UNION SELECT gne$i.image_id FROM image_tags gne$i
                    LEFT JOIN image_tags neg$i ON neg$i.image_id = gne$i.image_id AND neg$i.tag_id $in
                    WHERE neg$i.image_id IS NULL ";
        }

        foreach ($dis_neg_group_sql as $sql) {
            $i = $rc++;
            $q .= " UNION SELECT image_id FROM (SELECT ne$i.image_id FROM image_tags ne$i EXCEPT $sql ) ";
        }
        return $q;
    }

    private function all_disjuctive(int $mi): string
    {
        global $rc;
        $q = " SELECT DISTINCT it$mi.image_id FROM image_tags it$mi ";
        $tags_only = [];
        $trackers_only = [];
        foreach ($this->children as $c) {
            if ($c instanceof TermTracker) {
                $trackers_only[] = $c;
            } else {
                $tags_only[] = $c;
            }
        }
        if (!empty($tags_only)) {
            $q .= $this->all_disjunctive_tags($tags_only, ++$rc, $mi);
        }
        $pos_sql = [];
        $neg_sql = [];
        foreach ($trackers_only as $tracker) {
            $sql = $tracker->generate_sql($mi);
            if (!empty($sql)) {
                if ($tracker->negative) {
                    $neg_sql[] = $tracker->generate_sql($mi);
                } else {
                    $pos_sql[] = $tracker->generate_sql($mi);
                }
            }
        }
        if (!empty($pos_sql)) {
            $q .= " UNION ";
            $q .= join(" UNION ", $pos_sql);
        }
        foreach ($neg_sql as $sql) {
            $i = $rc++;
            $q .= " UNION SELECT image_id FROM (SELECT ne$i.image_id FROM image_tags ne$i EXCEPT $sql ) ";
        }
        return $q;
    }
    private function all_disjunctive_tags(array $tags, int $mi, int $pi): string
    {
        global $rc;
        $q = "";
        $pos_tag_ids = [];
        $neg_tags = [];
        foreach ($tags as $t) {
            $tag_ids = search::tag_or_wildcard_to_ids($t->tag);
            if ($t->negative) {
                $tc = count($tag_ids);
                if ($tc === 1) {
                    $tag_id = array_shift($tag_ids);
                    $neg_tags[] = "= $tag_id";
                } elseif ($tc > 1) {
                    $tag_list = join(', ', $tag_ids);
                    $neg_tags[] = "IN ($tag_list)";
                }
            } else {
                $pos_tag_ids = array_merge($pos_tag_ids, $tag_ids);
            }
        }
        $ptc = count($pos_tag_ids);
        $ne = empty($neg_tags);
        if ($ptc === 0 && $ne === 0) { // maybe raise to user that a tag doesnt have results?
            return $this->disjunctive ? "" : " INNER JOIN image_tags no$mi ON no$mi.image_id = it$pi.image_id AND 1=0 ";
        }
        $pos_tags = "";
        if ($ptc === 1) {
            $tag_id = array_shift($pos_tag_ids);
            $pos_tags = "= $tag_id";
        } else {
            $tag_list = join(', ', $pos_tag_ids);
            $pos_tags = "IN ($tag_list)";
        }

        if ($this->disjunctive) {
            if ($ptc !== 0) {
                $q = " SELECT dp$mi.image_id FROM image_tags dp$mi
                    WHERE dp$mi.tag_id $pos_tags ";
            }
            if ($ptc !== 0 && !$ne) {
                $q .= " UNION ";
            }
            if (!$ne) {
                $ni = $rc++;
                $q .= " SELECT de$ni.image_id FROM image_tags de$ni  EXCEPT SELECT DISTINCT de$ni.image_id FROM image_tags de$ni";
                foreach ($neg_tags as $in) {
                    $i = $rc++;
                    $q .= " INNER JOIN image_tags de$i ON de$i.image_id = de$ni.image_id AND de$i.tag_id $in";
                }
            }
        } else {
            if ($ptc !== 0 && !$ne) { // positive and negative
                $q .= " INNER JOIN ( ";
                $i = $rc++;
                $q .= " SELECT ap$i.image_id FROM image_tags ap$i
                        WHERE ap$i.image_id $pos_tags 
                        UNION ";
                $qs = [];
                foreach ($neg_tags as $in) {
                    $i = $rc++;
                    $qs[] = " SELECT ac$i.image_id FROM image_tags ac$i
                            LEFT JOIN image_tags an$i ON an$i.image_id = ac$i.image_id AND an$i.tag_id $in
                            WHERE an$i.image_id IS NULL ";
                }
                $i = $rc++;
                $q .= join(" UNION ", $qs);
                $q .= ") c$i on c$i.image_id = it$pi.image_id ";
            } elseif ($ptc !== 0) { // only positives
                $q = " INNER JOIN image_tags it$mi ON it$mi.image_id = it$pi.image_id AND it$mi.tag_id $pos_tags ";
            } elseif (!$ne) { // only negatives
                $q .= " INNER JOIN ( ";
                $qs = [];
                foreach ($neg_tags as $in) {
                    $i = $rc++;
                    $qs[] = " SELECT de$i.image_id FROM image_tags de$i
                            LEFT JOIN image_tags an$i ON an$i.image_id = de$i.image_id AND an$i.tag_id $in
                            WHERE an$i.image_id IS NULL ";
                }
                $i = $rc++;
                $q .= join(" UNION ", $qs);
                $q .= ") c$i on c$i.image_id = it$pi.image_id ";
            }
        }
        return $q;
    }

    public function generate_sql(int $pi = 0): string
    {
        global $rc;
        $mi = $rc++;
        // TODO img_condition
        if ($this->all_disjunctive_tags) {
            return $this->all_disjunctive_tags($this->children, $mi, $pi);
        }
        // TODO img_condition
        elseif ($this->all_disjunctive) {
            return $this->all_disjuctive($mi);
        }
        // TODO img_condition
        else {
            return $this->everything_sql($mi);
        }
        // TODO make sure every query has a WHERE clause
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
