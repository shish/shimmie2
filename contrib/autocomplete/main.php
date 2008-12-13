<?php
/**
 * Name: Autocomplete
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Auto-complete for search and upload tags
 */

class AutoComplete implements Extension {
	public function receive_event(Event $event) {
		if(($event instanceof PageRequestEvent) && ($event->page_name == "index" || $event->page_name == "view")) {
			$event->page->add_header("<script>autocomplete_url='".html_escape(make_link("autocomplete"))."';</script>");
		}
		if(($event instanceof PageRequestEvent) && ($event->page_name == "autocomplete")) {
			$event->page->set_mode("data");
			$event->page->set_type("text/plain");
			$event->page->set_data($this->get_completions($event->get_arg(0)));
		}
	}

	private function get_completions($start) {
		global $database;
		$tags = $database->db->GetCol("SELECT tag,count FROM tags WHERE tag LIKE ? ORDER BY count DESC", array($start.'%'));
		return implode("\n", $tags);
	}
}
add_event_listener(new AutoComplete());
?>
