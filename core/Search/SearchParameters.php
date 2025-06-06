<?php

declare(strict_types=1);

namespace Shimmie2;

final class SearchParameters
{
    public function __construct(
        public readonly TermTracker $tracker,
        public int $tag_count = 0,
        public int $img_count = 0,
        public ?string $order = null,
    ) {
    }

    /**
     * Turn a human input string into a an abstract search query
     *
     * @param search-term-array $terms
     */
    public static function from_terms(array $terms): SearchParameters
    {
        $order = null;

        $stpen = 0;  // search term parse event number
        $output_tracker = new TermTracker();
        $tracker = $output_tracker;
        $depth = 0;
        $tag_count = 0;
        $img_count = 0;
        foreach (array_merge([null], $terms) as $term) {
            if (!is_null($term) && array_key_exists(substr($term, -1), SearchTerm::TERM_GROUPERS)) {
                if (SearchTerm::TERM_GROUPERS[substr($term, -1)]) {
                    $ntracker = new TermTracker($tracker);
                    while (!empty($term) && array_key_exists($term[0], SearchTerm::TERM_OPERANDS)) {
                        $operand = SearchTerm::TERM_OPERANDS[$term[0]];
                        $term = substr($term, 1);
                        $ntracker->$operand = true;
                    }
                    $tracker = $tracker->appendgroup($ntracker);
                    $depth++;
                } elseif ($depth > 0) {
                    $ntracker = $tracker->getparent();
                    if (!is_null($ntracker)) {
                        $tracker = $ntracker;
                    }
                    $depth--;
                }
            } else {
                $stpe = send_event(new SearchTermParseEvent($stpen++, $term, $terms));
                $order ??= $stpe->order;
                foreach ($stpe->img_conditions as $condition) {
                    $tracker->appendchild($condition);
                    $img_count++;
                }
                foreach ($stpe->tag_conditions as $condition) {
                    $tracker->appendchild($condition);
                    $tag_count++;
                }
            }
        }
        $order ??= "images.".Ctx::$config->get(IndexConfig::ORDER);
        $tracker->simplify();
        echo "<div>";
        echo $tracker->create_search_string();
        echo "</div>";
        return new SearchParameters($output_tracker, $tag_count, $img_count, $order);
    }
}
