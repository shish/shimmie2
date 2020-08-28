<?php

require_once "a_api_controller.php";

class ApiTagsController extends AApiController
{
    public function process(array $args)
    {
        if (@$args[3]==="by_image") {
            $this->get_for_image();
        } else {
            $this->search_tags();
        }
    }

    public static function prepare_output(PDOStatement  $input): string
    {
        $output = [];
        $output["tags"] = [];
        foreach ($input as $item) {
            $output["tags"][] = ["id"=>$item["id"], "count"=>$item["count"], "tag"=>$item["tag"]];
        }
        return json_encode($output);
    }

    private function get_for_image()
    {
        if (!isset($_GET["id"])) {
            return;
        }
        $id = intval($_GET["id"]);
        self::get_for_image_id($id);
    }

    public static function get_for_image_id($id)
    {
        global  $database, $page, $cache;

        $page->set_mode(PageMode::DATA);
        $page->set_mime(MimeType::JSON);

        $cache_key = "api_tags_by_image-$id";
        $SQLarr = ["id"=>$id];
        $res = $cache->get($cache_key);
        if (!$res) {
            $res = $database->get_all_iterable(
                $database->scoreql_to_sql("
					SELECT *
					FROM tags t
					    INNER JOIN image_tags it ON t.id = it.tag_id AND it.image_id = :id
					ORDER BY count DESC"),
                $SQLarr
            );
            $cache->set($cache_key, $res, 600);
        }

        $page->set_data(self::prepare_output($res));
    }

    private function search_tags()
    {
        global  $database, $page, $cache;
        if (!isset($_GET["query"])) {
            return;
        }

        $page->set_mode(PageMode::DATA);
        $page->set_mime(MimeType::JSON);

        $s = strtolower($_GET["query"]);
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
        $cache_key = "api_tags_search-$s";
        $limitSQL = "";
        $searchSQL = "LOWER(tag) LIKE LOWER(:search)";
        $s = str_replace('_', '\_', $s);
        $s = str_replace('%', '\%', $s);
        $SQLarr = ["search"=>"$s%"];

        if (isset($_GET["search_categories"]) && $_GET["search_categories"] == "true") {
            $searchSQL .= " OR LOWER(tag) LIKE LOWER(:cat_search)";
            $SQLarr['cat_search'] = "%:$s%";
            $cache_key .= "+cat";
        }

        if (isset($_GET["limit"]) && $_GET["limit"] !== 0) {
            $limitSQL = "LIMIT :limit";
            $SQLarr['limit'] = $_GET["limit"];
            $cache_key .= "-" . $_GET["limit"];
        }

        $res = $cache->get($cache_key);
        if (!$res) {
            $res = $database->get_all_iterable(
                "
					SELECT *
					FROM tags
					WHERE $searchSQL
					AND count > 0
					ORDER BY count DESC
					$limitSQL",
                $SQLarr
            );
            $cache->set($cache_key, $res, 600);
        }

        $page->set_data(self::prepare_output($res));
    }
}
