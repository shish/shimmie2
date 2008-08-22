<?php
/**
 * Name: Featured Image
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Bring a specific image to the users' attentions
 */

class Featured extends Extension {
	var $theme;

	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("featured", "FeaturedTheme");
		
		if($event instanceof InitExtEvent) {
			global $config;
			$config->set_default_int('featured_id', 0);
		}

		if(($event instanceof PageRequestEvent) && ($event->page_name == "set_feature")) {
			global $user;
			if($user->is_admin() && isset($_POST['image_id'])) {
				global $config;
				$id = int_escape($_POST['image_id']);
				if($id > 0) {
					$config->set_int("featured_id", $id);
					$event->page->set_mode("redirect");
					$event->page->set_redirect(make_link("post/view/$id"));
				}
			}
		}

		if($event instanceof PostListBuildingEvent) {
			global $config, $database;
			$fid = $config->get_int("featured_id");
			if($fid > 0) {
				$image = $database->get_image($fid);
				if(!is_null($image)) {
					$this->theme->display_featured($event->page, $image);
				}
			}
		}

		/*
		if(($event instanceof SetupBuildingEvent)) {
			$sb = new SetupBlock("Featured Image");
			$sb->add_int_option("featured_id", "Image ID: ");
			$event->panel->add_block($sb);
		}
		*/

		if($event instanceof ImageAdminBlockBuildingEvent) {
			if($event->user->is_admin()) {
				$event->add_part($this->theme->get_buttons_html($event->image->id));
			}
		}
	}
}
add_event_listener(new Featured());
?>
