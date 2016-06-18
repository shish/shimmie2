<?php
/*
 * Name: Autocomplete
 * Author: Daku <admin@codeanimu.net>
 * Description: Adds autocomplete to search & tagging.
 */

class AutoComplete extends Extension {
	public function onPageRequest(PageRequestEvent $event) {
		global $page, $database;

		if($event->page_matches("api/internal/autocomplete")) {
			if(!isset($_GET["s"])) return;

			//$limit = 0;
			$cache_key = "autocomplete-" . strtolower($_GET["s"]);
			$limitSQL = "";
			$SQLarr = array("search"=>$_GET["s"]."%");
			if(isset($_GET["limit"]) && $_GET["limit"] !== 0){
				$limitSQL = "LIMIT :limit";
				$SQLarr['limit'] = $_GET["limit"];
				$cache_key .= "-" . $_GET["limit"];
			}

			$res = $database->cache->get($cache_key);
			if(!$res) {
				$res = $database->get_pairs($database->scoreql_to_sql("
					SELECT tag, count
					FROM tags
					WHERE SCORE_STRNORM(tag) LIKE SCORE_STRNORM(:search)
					AND count > 0
					ORDER BY count DESC
					$limitSQL"), $SQLarr
				);
				$database->cache->set($cache_key, $res, 600);
			}

			$page->set_mode("data");
			$page->set_type("application/json");
			$page->set_data(json_encode($res));
		}

		$this->theme->build_autocomplete($page);
	}
}
