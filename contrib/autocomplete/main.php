<?php

class AutoComplete extends Extension {
// event handling {{{
	public function receive_event($event) {
		if(is_a($event, 'PageRequestEvent') && ($event->page == "index" || $event->page == "view")) {
			global $page;
			$page->add_side_block(new Block(null, $this->build_autocomplete_script()));
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
// html {{{
	private function build_autocomplete_script() {
		global $database;
		$ac_url = html_escape(make_link("autocomplete"));

		return <<<EOD
<script>
//completion_cache = new array();

input = byId("search_input");
output = byId("search_completions");

function get_cached_completions(start) {
//	if(completion_cache[start]) {
//		return completion_cache[start];
//	}
//	else {
		return null;
//	}
}
function get_completions(start) {
	cached = get_cached_completions(start);
	if(cached) {
		output.innerHTML = cached;
	}
	else {
		ajaxRequest("$ac_url/"+start, function(data) {set_completions(start, data);});
	}
}
function set_completions(start, data) {
//	completion_cache[start] = data;
	output.innerHTML = data;
}

input.onkeyup = function() {
	if(input.value.length < 3) {
		output.innerHTML = "";
	}
	else {
		get_completions(input.value);
	}
};
</script>
EOD;
	}
// }}}
}
add_event_listener(new AutoComplete());
?>
