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
			$limitSQL = "";
			$SQLarr = array("search"=>$_GET["s"]."%");
			if(isset($_GET["limit"]) && $_GET["limit"] !== 0){
				$limitSQL = "LIMIT :limit";
				$SQLarr['limit'] = $_GET["limit"];
			}

			$res = $database->get_col(
					"SELECT tag FROM tags WHERE tag LIKE :search AND count > 0 $limitSQL", $SQLarr);

			$page->set_mode("data");
			$page->set_type("application/json");
			$page->set_data(json_encode($res));
		}

		$this->theme->build_autocomplete($page);
	}
}
