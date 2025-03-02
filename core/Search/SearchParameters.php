<?php

declare(strict_types=1);

namespace Shimmie2;

class SearchParameters
{
    /** @var TagCondition[] */
    public array $tag_conditions = [];
    /** @var ImgCondition[] */
    public array $img_conditions = [];
    public ?string $order = null;

    /**
     * Turn a human input string into a an abstract search query
     *
     * @param string[] $terms
     */
    public static function from_terms(array $terms): SearchParameters
    {
        global $config;

        $sp = new SearchParameters();

        $stpen = 0;  // search term parse event number
        foreach (array_merge([null], $terms) as $term) {
            $stpe = send_event(new SearchTermParseEvent($stpen++, $term, $terms));
            $sp->order ??= $stpe->order;
            $sp->img_conditions = array_merge($sp->img_conditions, $stpe->img_conditions);
            $sp->tag_conditions = array_merge($sp->tag_conditions, $stpe->tag_conditions);
        }

        $sp->order ??= "images.".$config->get_string(IndexConfig::ORDER);

        return $sp;
    }
}
