<?php
class LoadExtData extends Extension {
	public function receive_event($event) {
		if($event instanceof PageRequestEvent) {
			global $page, $config;

			$data_href = get_base_href();

			$css_files = glob("ext/*/style.css");
			if($css_files) {
				foreach($css_files as $css_file) {
					$page->add_header("<link rel='stylesheet' href='$data_href/$css_file' type='text/css'>");
				}
			}

			$js_files = glob("ext/*/script.js");
			if($js_files) {
				foreach($js_files as $js_file) {
					$page->add_header("<script src='$data_href/$js_file' type='text/javascript'></script>");
				}
			}
		}
	}
}
add_event_listener(new LoadExtData());
?>
