<?php 
// author: Erik Youngren 
// email: artanis.00@gmail.com 
class tagger extends Extension { 
	var $theme; 

	public function receive_event ($event) { 
		if(is_null($this->theme)) 
			$this->theme = get_theme_object("tagger", "taggerTheme");    
		if(is_a($event,"DisplayingImageEvent")) { 
			//show tagger box 
			global $database; 
			global $page; 

			$tags = $database->Execute(" 
					SELECT tag 
					FROM `tags` 
					WHERE count > 1 
					ORDER BY tag"); 

			$this->theme->build($page, $tags); 
		} 
	} 
} 
add_event_listener(new tagger()); 
?>
