<?php
class HellBan extends Extension {
	public function onPageRequest(PageRequestEvent $event) {
		global $page, $user;

		if($user->can("hellbanned")) {
			$s = "";
		}
		else if($user->can("view_hellbanned")) {
			$s = "border: 1px solid red !important;";
		}
		else {
			$s = "display: none !important;";
		}

		if($s) {
			$page->add_html_header("<style>.hb { $s }</style>");
		}
	}
}
?>
