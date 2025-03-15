<?php

declare(strict_types=1);

namespace Shimmie2;

use GQLA\Type;
use GQLA\Field;
use GQLA\Query;

#[Type(name: "TagUsage")]
final class TagUsage
{
    #[Field]
    public string $tag;
    #[Field]
    public int $uses;

    public function __construct(string $tag, int $uses)
    {
        $this->tag = $tag;
        $this->uses = $uses;
    }

    /**
     * @return TagUsage[]
     */
    #[Query(name: "tags", type: '[TagUsage!]!')]
    public static function tags(string $search, int $limit = 10): array
    {
        global $cache, $database;

        $search = strtolower($search);
        if (
            $search === '' ||
            $search[0] === '_' ||
            $search[0] === '%' ||
            strlen($search) > 32
        ) {
            return [];
        }

        $cache_key = "tagusage-$search";
        $limitSQL = "";
        $search = str_replace('_', '\_', $search);
        $search = str_replace('%', '\%', $search);
        $SQLarr = ["search" => "$search%"]; #, "cat_search"=>"%:$search%"];
        if ($limit !== 0) {
            $limitSQL = "LIMIT :limit";
            $SQLarr['limit'] = $limit;
            $cache_key .= "-" . $limit;
        }

        $res = cache_get_or_set(
            $cache_key,
            fn () => $database->get_pairs(
                "
                SELECT tag, count
                FROM tags
                WHERE LOWER(tag) LIKE LOWER(:search)
                -- OR LOWER(tag) LIKE LOWER(:cat_search)
                AND count > 0
                ORDER BY count DESC
                $limitSQL
                ",
                $SQLarr
            ),
            600
        );

        $counts = [];
        foreach ($res as $k => $v) {
            $counts[] = new TagUsage($k, $v);
        }
        return $counts;
    }
}
