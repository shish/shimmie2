<?php
class tagger extends Extension {
	var $theme;
	
	public function receive_event ($event) {
		if(is_null($this->theme))
			$this->theme = get_theme_object("tagger", "taggerTheme");	
		
		if(is_a($event,"DisplayingImageEvent")) {
			//show tagger box
			global $database;
			global $page;
			global $config;
			
			$base_href = $config->get_string('base_href');
			
			$tags = $database->Execute("
				SELECT tag
				FROM `tags`
				WHERE count > 1 AND substring(tag,1,1) != '.'
				ORDER BY tag");
				
			$this->theme->build($page, $tags);
		}
		

		
		if(is_a($event,"PageRequestEvent") && $event->page_name == "about"
			&& $event->get_arg(0) == "tagger")
		{
			global $page;
			$this->theme->show_about($page);
		}
	}
}
add_event_listener( new tagger());
?>
