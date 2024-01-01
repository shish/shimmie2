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
    /** @var string[] */
    public array $context = [];
    /** @var ImgCondition[] */
    public array $img_conditions = [];
    /** @var TagCondition[] */
    public array $tag_conditions = [];
    public ?string $order = null;

    public function __construct(int $id, string $term = null, array $context = [])
    {
        parent::__construct();

        if ($term == "-" || $term == "*") {
            throw new SearchTermParseException("'$term' is not a valid search term");
        }

        $positive = true;
        if (is_string($term) && !empty($term) && ($term[0] == '-')) {
            $positive = false;
            $term = substr($term, 1);
        }

        $this->id = $id;
        $this->positive = $positive;
        $this->term = $term;
        $this->context = $context;
    }

    public function add_querylet(Querylet $q)
    {
        $this->add_img_condition(new ImgCondition($q, $this->positive));
    }

    public function add_img_condition(ImgCondition $c)
    {
        $this->img_conditions[] = $c;
    }

    public function add_tag_condition(TagCondition $c)
    {
        $this->tag_conditions[] = $c;
    }
}

class SearchTermParseException extends SCoreException
{
}

class PostListBuildingEvent extends Event
{
    public array $search_terms = [];
    public array $parts = [];

    /**
     * @param string[] $search
     */
    public function __construct(array $search)
    {
        parent::__construct();
        $this->search_terms = $search;
    }

    public function add_control(string $html, int $position = 50)
    {
        while (isset($this->parts[$position])) {
            $position++;
        }
        $this->parts[$position] = $html;
    }
}
