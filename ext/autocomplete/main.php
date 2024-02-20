<?php

declare(strict_types=1);

namespace Shimmie2;

class AutoComplete extends Extension
{
    public function get_priority(): int
    {
        return 30;
    } // before Home

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page;

        if ($event->page_matches("api/internal/autocomplete")) {
            $limit = (int)($event->get_GET("limit") ?? 1000);
            $s = $event->get_GET("s") ?? "";

            $res = $this->complete($s, $limit);

            $page->set_mode(PageMode::DATA);
            $page->set_mime(MimeType::JSON);
            $page->set_data(\Safe\json_encode($res));
        }
    }

    /**
     * @return array<string, array{newtag:string|null,count:int}>
     */
    private function complete(string $search, int $limit): array
    {
        global $cache, $database;

        $search = strtolower($search);
        if (
            $search == '' ||
            $search[0] == '_' ||
            $search[0] == '%' ||
            strlen($search) > 32
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
            "cat_search" => Extension::is_enabled(TagCategoriesInfo::KEY) ? "%:$search%" : "",
        ];
        if ($limit !== 0) {
            $limitSQL = "LIMIT :limit";
            $SQLarr['limit'] = $limit;
        }

        return cache_get_or_set($cache_key, function () use ($database, $limitSQL, $SQLarr) {
            $rows = $database->get_all(
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
            foreach($rows as $row) {
                $ret[(string)$row['tag']] = [
                    "newtag" => $row["newtag"],
                    "count" => $row["count"],
                ];
            }
            return $ret;
        }, 600);
    }
}
