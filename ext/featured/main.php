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

class Featured extends Extension {
	public function onInitExt(InitExtEvent $event) {
		global $config;
		$config->set_default_int('featured_id', 0);
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $config, $page, $user;
		if($event->page_matches("featured_image")) {
			if($event->get_arg(0) == "set" && $user->check_auth_token()) {
				if($user->can("edit_feature") && isset($_POST['image_id'])) {
					$id = int_escape($_POST['image_id']);
					if($id > 0) {
						$config->set_int("featured_id", $id);
						log_info("featured", "Featured image set to $id", "Featured image set");
						$page->set_mode("redirect");
						$page->set_redirect(make_link("post/view/$id"));
					}
				}
			}
			if($event->get_arg(0) == "download") {
				$image = Image::by_id($config->get_int("featured_id"));
				if(!is_null($image)) {
					$page->set_mode("data");
					$page->set_type($image->get_mime_type());
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

	public function onPostListBuilding(PostListBuildingEvent $event) {
		global $config, $database, $page, $user;
		$fid = $config->get_int("featured_id");
		if($fid > 0) {
			$image = $database->cache->get("featured_image_object:$fid");
			if($image === false) {
				$image = Image::by_id($fid);
				if($image) { // make sure the object is fully populated before saving
					$image->get_tag_array();
				}
				$database->cache->set("featured_image_object:$fid", $image, 600);
			}
			if(!is_null($image)) {
				if(ext_is_live("Ratings")) {
					if(strpos(Ratings::get_user_privs($user), $image->rating) === FALSE) {
						return;
					}
				}
				$this->theme->display_featured($page, $image);
			}
		}
	}

	public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event) {
		global $user;
		if($user->can("edit_feature")) {
			$event->add_part($this->theme->get_buttons_html($event->image->id));
		}
	}
}

