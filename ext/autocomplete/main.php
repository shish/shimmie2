<?php declare(strict_types=1);

class AutoComplete extends Extension
{
    /** @var AutoCompleteTheme */
    protected $theme;

    public function get_priority(): int
    {
        return 30;
    } // before Home

    public function onPageRequest(PageRequestEvent $event)
    {
        global $cache, $page, $database;

        if ($event->page_matches("api/internal/autocomplete")) {
            if (!isset($_GET["s"])) {
                return;
            }

            $page->set_mode(PageMode::DATA);
            $page->set_mime(MimeType::JSON);

            $s = strtolower($_GET["s"]);
            if (
                $s == '' ||
                $s[0] == '_' ||
                $s[0] == '%' ||
                strlen($s) > 32
            ) {
                $page->set_data("{}");
                return;
            }

            //$limit = 0;
            $cache_key = "autocomplete-$s";
            $limitSQL = "";
            $s = str_replace('_', '\_', $s);
            $s = str_replace('%', '\%', $s);
            $SQLarr = ["search"=>"$s%"]; #, "cat_search"=>"%:$s%"];
            if (isset($_GET["limit"]) && $_GET["limit"] !== 0) {
                $limitSQL = "LIMIT :limit";
                $SQLarr['limit'] = $_GET["limit"];
                $cache_key .= "-" . $_GET["limit"];
            }

            $res = $cache->get($cache_key);
            if (!$res) {
                $res = $database->get_pairs(
                    "
					SELECT tag, count
					FROM tags
					WHERE LOWER(tag) LIKE LOWER(:search)
					-- OR LOWER(tag) LIKE LOWER(:cat_search)
					AND count > 0
					ORDER BY count DESC
					$limitSQL",
                    $SQLarr
                );
                $cache->set($cache_key, $res, 600);
            }

            $page->set_data(json_encode($res));
        }

        $this->theme->build_autocomplete($page);
    }
}
