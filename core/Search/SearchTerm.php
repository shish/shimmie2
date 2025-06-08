<?php

declare(strict_types=1);

namespace Shimmie2;

final class SearchTerm
{
    /*
     * defines operands which have effect on the way a term is processed
     * any additions will have to be added to the SearchTermParseEvent
     * and TagCondition classes, of course along with functionality to alter
     * the final search query
     */
    public const TERM_OPERANDS = [
        "-" => "negative",
        "~" => "disjunctive"
    ];

    /*
     * defines which characters may traverse the term group up or down,
     * with true or false respectively
     */
    public const TERM_GROUPERS = [
        "(" => true,
        ")" => false
    ];
    /**
     * For now SearchTerm::explode() and Tag::explode() are the same,
     * but keeping them separate because they are conceptually different, eg
     * a tag-array must be unordered and unique, while a search-term-array may
     * be ordered and may contain duplicates.
     *
     * @return search-term-array
     */
    public static function explode(string $str): array
    {
        $terms = explode(' ', trim($str));

        /* sanitise by removing invisible / dodgy characters */
        $terms_to_process = Tag::sanitize_array($terms);

        /* resolve aliases */
        $processed_terms = [];
        $i = 0;
        $term_count = count($terms_to_process);
        while ($i < $term_count) {
            $term = $terms_to_process[$i++];

            if (array_key_exists($term, self::TERM_GROUPERS)) {
                $processed_terms[] = $term;
                continue;
            }

            $operands = '';
            while (!empty($term) && array_key_exists($term[0], self::TERM_OPERANDS)) {
                $operands = "$operands$term[0]";
                $term = substr($term, 1);
            }

            $newterms = Ctx::$database->get_one(
                "
					SELECT newtag
					FROM aliases
					WHERE LOWER(oldtag)=LOWER(:tag)
				",
                ["tag" => $term]
            );
            if (empty($newterms)) {
                //tag has no alias, continue
                $processed_terms[] = "$operands$term";
            } else {
                $aliases = explode(" ", $newterms); // SearchTerm::explode($newterms); - recursion can be infinite
                foreach ($aliases as $alias) {
                    if ($term === $alias) {
                        $processed_terms[] = "$operands$alias";
                    } elseif (!in_array($alias, $terms_to_process)) {
                        $terms_to_process[] = "$operands$alias";
                        $term_count++;
                    }
                }
            }
        }

        /* remove any empty terms */
        $processed_terms = array_filter($processed_terms, fn ($t) => !empty($t));
        $processed_terms = array_values($processed_terms);

        return $processed_terms;
    }

    /**
     * @param search-term-array $terms
     */
    public static function implode(array $terms): string
    {
        return implode(' ', $terms);
    }
}
