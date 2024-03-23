<?php

declare(strict_types=1);

namespace Shimmie2;

/*
 * SearchTermParseEvent:
 * Signal that a search term needs parsing
 */
class SearchTermParseEvent extends Event
{
    public int $id = 0;
    public ?string $term = null;
    public bool $positive = true;
    public bool $disjunctive = false;
    /** @var string[] */
    public array $context = [];
    /** @var ImgCondition[] */
    public array $img_conditions = [];
    /** @var TagCondition[] */
    public array $tag_conditions = [];
    public ?string $order = null;

    /**
     * @param string[] $context
     */
    public function __construct(int $id, string $term = null, array $context = [])
    {
        parent::__construct();

        if ($term == "-" || $term == "*" || $term == "~" || $term == "~-") {
            throw new SearchTermParseException("'$term' is not a valid search term");
        }

        $positive = true;
        $disjunctive = false;
        if (is_string($term) && !empty($term)) {
            if ($term[0] == '~') {
                $disjunctive = true;
                $term = substr($term, 1);
            }
            if ($term[0] == '-') {
                $positive = false;
                $term = substr($term, 1);
            }
        }

        $this->id = $id;
        $this->positive = $positive;
        $this->disjunctive = $disjunctive;
        $this->term = $term;
        $this->context = $context;
    }

    public function add_querylet(Querylet $q): void
    {
        $this->add_img_condition(new ImgCondition($q, $this->positive, $this->disjunctive));
    }

    public function add_img_condition(ImgCondition $c): void
    {
        $this->img_conditions[] = $c;
    }

    public function add_tag_condition(TagCondition $c): void
    {
        $this->tag_conditions[] = $c;
    }
}

class SearchTermParseException extends InvalidInput
{
}

class PostListBuildingEvent extends Event
{
    /** @var string[] */
    public array $search_terms = [];
    /** @var array<int,string> */
    public array $parts = [];

    /**
     * @param string[] $search
     */
    public function __construct(array $search)
    {
        parent::__construct();
        $this->search_terms = $search;
    }

    public function add_control(string $html, int $position = 50): void
    {
        while (isset($this->parts[$position])) {
            $position++;
        }
        $this->parts[$position] = $html;
    }
}
