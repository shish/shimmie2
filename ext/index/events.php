<?php

/*
 * SearchTermParseEvent:
 * Signal that a search term needs parsing
 */
class SearchTermParseEvent extends Event
{
    /** @var null|string  */
    public $term = null;
    /** @var string[] */
    public $context = [];
    /** @var Querylet[] */
    public $querylets = [];

    public function __construct(string $term=null, array $context=[])
    {
        $this->term = $term;
        $this->context = $context;
    }

    public function is_querylet_set(): bool
    {
        return (count($this->querylets) > 0);
    }

    public function get_querylets(): array
    {
        return $this->querylets;
    }

    public function add_querylet(Querylet $q)
    {
        $this->querylets[] = $q;
    }
}

class SearchTermParseException extends SCoreException
{
}

class PostListBuildingEvent extends Event
{
    /** @var array */
    public $search_terms = [];

    /** @var array */
    public $parts = [];

    /**
     * #param string[] $search
     */
    public function __construct(array $search)
    {
        $this->search_terms = $search;
    }

    public function add_control(string $html, int $position=50)
    {
        while (isset($this->parts[$position])) {
            $position++;
        }
        $this->parts[$position] = $html;
    }
}
