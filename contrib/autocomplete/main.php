<?php

class AutoComplete extends Extension {
// event handling {{{
	public function receive_event($event) {
		if(is_a($event, 'PageRequestEvent') && ($event->page == "index" || $event->page == "view")) {
			global $page;
			$page->add_header("<script>autocomplete_url='".html_escape(make_link("autocomplete"))."';</script>");
		}
		if(is_a($event, 'PageRequestEvent') && ($event->page == "autocomplete")) {
			global $page;
			$page->set_mode("data");
			$page->set_type("text/plain");
			$page->set_data($this->get_completions($event->get_arg(0)));
		}
	}
// }}}
// do things {{{
	private function get_completions($start) {
		global $database;
		$tags = $database->db->GetCol("SELECT tag,count(image_id) AS count FROM tags WHERE tag LIKE ? GROUP BY tag ORDER BY count DESC", array($start.'%'));
		return implode("\n", $tags);
	}
// }}}
}
add_event_listener(new AutoComplete());
?>
