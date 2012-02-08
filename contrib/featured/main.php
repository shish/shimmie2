<?php
/*
 * Name: Featured Image
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Bring a specific image to the users' attentions
 * Documentation:
 *  Once enabled, a new "feature this" button will appear next
 *  to the other image control buttons (delete, rotate, etc).
 *  Clicking it will set the image as the site's current feature,
 *  which will be shown in the side bar of the post list.
 *  <p><b>Viewing a featured image</b>
 *  <br>Visit <code>/featured_image/view</code>
 *  <p><b>Downloading a featured image</b>
 *  <br>Link to <code>/featured_image/download</code>. This will give
 *  the raw data for an image (no HTML). This is useful so that you
 *  can set your desktop wallpaper to be the download URL, refreshed
 *  every couple of hours.
 */

class Featured extends SimpleExtension {
	public function onInitExt($event) {
		global $config;
		$config->set_default_int('featured_id', 0);
	}

	public function onPageRequest($event) {
		global $config, $page, $user;
		if($event->page_matches("featured_image")) {
			if($event->get_arg(0) == "set" && $user->check_auth_token()) {
				if($user->is_admin() && isset($_POST['image_id'])) {
					$id = int_escape($_POST['image_id']);
					if($id > 0) {
						$config->set_int("featured_id", $id);
						$page->set_mode("redirect");
						$page->set_redirect(make_link("post/view/$id"));
					}
				}
			}
			if($event->get_arg(0) == "download") {
				$image = Image::by_id($config->get_int("featured_id"));
				if(!is_null($image)) {
					$page->set_mode("data");
					$page->set_type("image/jpeg");
					$page->set_data(file_get_contents($image->get_image_filename()));
				}
			}
			if($event->get_arg(0) == "view") {
				$image = Image::by_id($config->get_int("featured_id"));
				if(!is_null($image)) {
					send_event(new DisplayingImageEvent($image, $page));
				}
			}
		}
	}

	public function onPostListBuilding($event) {
		global $config, $database, $page, $user;
		$fid = $config->get_int("featured_id");
		if($fid > 0) {
			$image = $database->cache->get("featured_image_object");
			if(empty($image)) {
				$image = Image::by_id($fid);
				$database->cache->set("featured_image_object", $image, 60);
			}
			if(!is_null($image)) {
				if(class_exists("Ratings")) {
					if(strpos(Ratings::get_user_privs($user), $image->rating) === FALSE) {
						return;
					}
				}
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
