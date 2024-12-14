<?php

declare(strict_types=1);

namespace Shimmie2;

const TAG_OPERANDS = [
    "-" => "negative",
];

/*
 * SearchTermParseEvent:
 * Signal that a search term needs parsing
 */
class SearchTermParseEvent extends Event
{
    public int $id = 0;
    public ?string $term = null;
    public bool $negative = false;
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
    public function __construct(int $id, ?string $term = null, array $context = [])
    {
        parent::__construct();
        $original_term = $term;

        if ($term !== null) {
            // pull any operands off the start of the search term
            while (!empty($term) && array_key_exists($term[0], TAG_OPERANDS)) {
                $operand = TAG_OPERANDS[$term[0]];
                $term = substr($term, 1);
                $this->$operand = true;
            }

            // if the term is in quotes, strip them
            if (str_starts_with($term, '"') && str_ends_with($term, '"')) {
                $term = substr($term, 1, -1);
            }

            // if what's left is empty, it's not a valid search term
            if ($term === "" || $term === "*") {
                throw new SearchTermParseException("'$original_term' is not a valid search term");
            }
        }

        $this->id = $id;
        $this->term = $term;
        $this->context = $context;
    }

    public function add_querylet(Querylet $q): void
    {
        $this->add_img_condition(new ImgCondition($q, !$this->negative));
    }

    public function add_img_condition(ImgCondition $c): void
    {
        $this->img_conditions[] = $c;
    }

    public function add_tag_condition(TagCondition $c): void
    {
        $this->tag_conditions[] = $c;
    }

    /**
     * @return array<string>|null
     */
    public function matches(string $regex): ?array
    {
        $matches = [];
        if (is_null($this->term)) {
            return null;
        }
        if (\Safe\preg_match($regex, $this->term, $matches)) {
            // @phpstan-ignore-next-line
            return $matches;
        }
        return null;
    }
}

class SearchTermParseException extends InvalidInput
{
}

class PostListBuildingEvent extends Event
{
    /** @var list<string> */
    public array $search_terms = [];
    /** @var array<int,string> */
    public array $parts = [];

    /**
     * @param list<string> $search
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
