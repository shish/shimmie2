<?php declare(strict_types=1);

/*
 * SearchTermParseEvent:
 * Signal that a search term needs parsing
 */
class SearchTermParseEvent extends Event
{
    /** @var int */
    public $id = 0;
    /** @var null|string */
    public $term = null;
    /** @var string[] */
    public $context = [];
    /** @var Querylet[] */
    public $querylets = [];
    /** @var null|string  */
    public $order = null;

    public function __construct(int $id, string $term=null, array $context=[])
    {
        parent::__construct();
        $this->id = $id;
        $this->term = $term;
        $this->context = $context;
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
        parent::__construct();
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
