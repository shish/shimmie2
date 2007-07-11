<?php

class Index extends Extension {
	var $theme;

	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("index", "IndexTheme");
		
		if(is_a($event, 'PageRequestEvent') && ($event->page == "index")) {
			$search_terms = array();
			$page_number = 1;

			if($event->count_args() > 0) {
				$page_number = int_escape($event->get_arg(0));
				if($page_number == 0) $page_number = 1; // invalid -> 0
			}

			if(isset($_GET['search'])) {
				$search_terms = explode(' ', $_GET['search']);
			}

			global $config;
			global $database;

			$total_pages = $database->count_pages($search_terms);
			$count = $config->get_int('index_width') * $config->get_int('index_height');
			$images = $database->get_images(($page_number-1)*$count, $count, $search_terms);
			
			$this->theme->set_page($page_number, $total_pages, $search_terms);
			$this->theme->display_page($event->page_object, $images);
		}

		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("Index Options");
			$sb->position = 20;
			
			$sb->add_label("Index table size ");
			$sb->add_int_option("index_width");
			$sb->add_label(" x ");
			$sb->add_int_option("index_height");
			$sb->add_label(" images");

			$sb->add_text_option("image_tip", "<br>Image tooltip ");

			$event->panel->add_block($sb);
		}
		if(is_a($event, 'ConfigSaveEvent')) {
			$event->config->set_int_from_post("index_width");
			$event->config->set_int_from_post("index_height");
			$event->config->set_string_from_post("image_tip");
		}
	}
}
add_event_listener(new Index());
?>
