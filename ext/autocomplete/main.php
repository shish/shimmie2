<?php

declare(strict_types=1);

namespace Shimmie2;

class AutoComplete extends Extension
{
    /** @var AutoCompleteTheme */
    protected Themelet $theme;

    public function get_priority(): int
    {
        return 30;
    } // before Home

    public function onPageRequest(PageRequestEvent $event)
    {
        global $page;

        if ($event->page_matches("api/internal/autocomplete")) {
            $limit = (int)($_GET["limit"] ?? 0);
            $s = $_GET["s"] ?? "";

            $res = $this->complete($s, $limit);

            $page->set_mode(PageMode::DATA);
            $page->set_mime(MimeType::JSON);
            $page->set_data(json_encode($res));
        }

        $this->theme->build_autocomplete($page);
    }

    private function complete(string $search, int $limit): array
    {
        global $cache, $database;

        if (!$search) {
            return [];
        }

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
        $cache_key = "autocomplete:" . md5($search);
        $limitSQL = "";
        $search = str_replace('_', '\_', $search);
        $search = str_replace('%', '\%', $search);
        $SQLarr = ["search" => "$search%"]; #, "cat_search"=>"%:$search%"];
        if ($limit !== 0) {
            $limitSQL = "LIMIT :limit";
            $SQLarr['limit'] = $limit;
            $cache_key .= "-" . $limit;
        }

        $res = $cache->get($cache_key);
        if (is_null($res)) {
            $res = $database->get_pairs(
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
            );
            $cache->set($cache_key, $res, 600);
        }

        return $res;
    }
}
