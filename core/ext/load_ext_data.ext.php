<?php
class LoadExtData extends Extension {
	public function receive_event($event) {
		if(is_a($event, 'PageRequestEvent')) {
			global $page, $config;

			$data_href = $config->get_string("data_href");

			foreach(glob("ext/*/style.css") as $css_file) {
				$page->add_header("<link rel='stylesheet' href='$data_href/$css_file' type='text/css'>");
			}

			foreach(glob("ext/*/script.js") as $js_file) {
				$page->add_header("<script src='$data_href/$js_file' type='text/javascript'></script>");
			}
		}
	}
}
add_event_listener(new LoadExtData());
?>
