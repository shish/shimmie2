<?php

class AutoCompleteTheme extends Themelet {
	public function build_autocomplete(Page $page) {
		$base_href = get_base_href();
		// TODO: AJAX test and fallback.

		$page->add_html_header("<script src='$base_href/ext/autocomplete/lib/jquery-ui.min.js' type='text/javascript'></script>");
		$page->add_html_header("<script src='$base_href/ext/autocomplete/lib/tag-it.min.js' type='text/javascript'></script>");
		$page->add_html_header('<link rel="stylesheet" type="text/css" href="//ajax.googleapis.com/ajax/libs/jqueryui/1/themes/flick/jquery-ui.css">');
		$page->add_html_header("<link rel='stylesheet' type='text/css' href='$base_href/ext/autocomplete/lib/jquery.tagit.css' />");
	}
}
