<?php

declare(strict_types=1);

namespace Shimmie2;

final class AutoComplete extends Extension
{
    public const KEY = "autocomplete";

    public function get_priority(): int
    {
        return 30;
    } // before Home

    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("api/internal/autocomplete")) {
            $limit = (int)($event->GET->get("limit") ?? 1000);
            $s = $event->GET->get("s") ?? "";

            $res = $this->complete($s, $limit);

            Ctx::$page->set_data(MimeType::JSON, \Safe\json_encode($res));
        }
    }

    /**
     * @return array<string, array{newtag:string|null,count:int}>
     */
    private function complete(string $search, int $limit): array
    {
        $search = mb_strtolower($search);
        if (
            $search === '' ||
            $search[0] === '_' ||
            $search[0] === '%' ||
            mb_strlen($search) > 32
        ) {
            return [];
        }

        # memcache keys can't contain spaces
        $cache_key = "autocomplete:$limit:" . md5($search);
        $limitSQL = "";
        $search = str_replace('_', '\_', $search);
        $search = str_replace('%', '\%', $search);
        $SQLarr = [
            "search" => "$search%",
            "cat_search" => TagCategoriesInfo::is_enabled() ? "%:$search%" : "",
        ];
        if ($limit !== 0) {
            $limitSQL = "LIMIT :limit";
            $SQLarr['limit'] = $limit;
        }

        return cache_get_or_set($cache_key, function () use ($limitSQL, $SQLarr) {
            $rows = Ctx::$database->get_all(
                "
                    -- (
                        SELECT tag, NULL AS newtag, count
                        FROM tags
                        WHERE (
                            LOWER(tag) LIKE LOWER(:search)
                            OR LOWER(tag) LIKE LOWER(:cat_search)
                        )
                        AND count > 0
                    -- )
                    UNION
                    -- (
                        SELECT oldtag AS tag, newtag, count
                        FROM aliases
                        JOIN tags ON tag = newtag
                        WHERE (
                            (LOWER(oldtag) LIKE LOWER(:search) AND LOWER(newtag) NOT LIKE LOWER(:search))
                            OR (LOWER(oldtag) LIKE LOWER(:cat_search) AND LOWER(newtag) NOT LIKE LOWER(:cat_search))
                        )
                        AND count > 0
                    -- )
                    ORDER BY count DESC, tag ASC
                    $limitSQL
                ",
                $SQLarr
            );
            $ret = [];
            foreach ($rows as $row) {
                $ret[(string)$row['tag']] = [
                    "newtag" => $row["newtag"],
                    "count" => $row["count"],
                ];
            }
            return $ret;
        }, 600);
    }
}
