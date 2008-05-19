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
		
		if(is_a($event, 'InitExtEvent')) {
			global $config;
			$config->set_default_int('featured_id', 0);
		}

		if(is_a($event, 'PageRequestEvent') && ($event->page_name == "set_feature")) {
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

		if(is_a($event, 'PostListBuildingEvent')) {
			global $config, $database;
			$fid = $config->get_int("featured_id");
			if($fid > 0) {
				$this->theme->display_featured($event->page, $database->get_image($fid));
			}
		}

		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("Featured Image");
			$sb->add_int_option("featured_id", "Image ID: ");
			$event->panel->add_block($sb);
		}

		if(is_a($event, 'DisplayingImageEvent')) {
			global $user;
			if($user->is_admin()) {
				$this->theme->display_buttons($event->page, $event->image->id);
			}
		}
	}
}
add_event_listener(new Featured());
?>
