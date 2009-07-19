<?php
/**
 * Name: Featured Image
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Bring a specific image to the users' attentions
 * Documentation:
 *  Once enabled, a new "feature this" button will appear next
 *  to the other image control buttons (delete, rotate, etc).
 *  Clicking it will set the image as the site's current feature,
 *  which will be shown in the side bar of the post list.
 */

class Featured extends SimpleExtension {
	public function onInitExt($event) {
		global $config;
		$config->set_default_int('featured_id', 0);
	}

	public function onPageRequest($event) {
		global $config, $page, $user;
		if($event->page_matches("set_feature")) {
			if($user->is_admin() && isset($_POST['image_id'])) {
				$id = int_escape($_POST['image_id']);
				if($id > 0) {
					$config->set_int("featured_id", $id);
					$page->set_mode("redirect");
					$page->set_redirect(make_link("post/view/$id"));
				}
			}
		}
	}

	public function onPostListBuilding($event) {
		global $config, $page;
		$fid = $config->get_int("featured_id");
		if($fid > 0) {
			$image = Image::by_id($fid);
			if(!is_null($image)) {
				$this->theme->display_featured($page, $image);
			}
		}
	}

	public function onImageAdminBlockBuilding($event) {
		global $user;
		if($user->is_admin()) {
			$event->add_part($this->theme->get_buttons_html($event->image->id));
		}
	}
}
?>
