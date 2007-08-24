<?php
/* AdminBuildingEvent {{{
 *
 * Sent when the admin page is ready to be added to
 */
class AdminBuildingEvent extends Event {
	var $page;
	public function AdminBuildingEvent($page) {
		$this->page = $page;
	}
}
// }}}

class AdminPage extends Extension {
	var $theme;
	
	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("admin", "AdminPageTheme");

		if(is_a($event, 'PageRequestEvent') && ($event->page_name == "admin")) {
			if(!$event->user->is_admin()) {
				$this->theme->display_error($event->page, "Permission Denied", "This page is for admins only");
			}
			else {
				if($event->get_arg(0) == "delete_image") {
					// FIXME: missing lots of else {complain}
					if(isset($_POST['image_id'])) {
						global $database;
						$image = $database->get_image($_POST['image_id']);
						if($image) {
							send_event(new ImageDeletionEvent($image));
							$event->page->set_mode("redirect");
							$event->page->set_redirect(make_link("post/list"));
						}
					}
				}
				else {
					send_event(new AdminBuildingEvent($event->page));
				}
			}
		}

		if(is_a($event, 'DisplayingImageEvent')) {
			global $user;
			if($user->is_admin()) {
				$this->theme->display_deleter($event->page, $event->image->id);
			}
		}

		if(is_a($event, 'AdminBuildingEvent')) {
			$this->theme->display_page($event->page);
		}

		if(is_a($event, 'UserBlockBuildingEvent')) {
			if($event->user->is_admin()) {
				$event->add_link("Board Admin", make_link("admin"));
			}
		}
	}
}
add_event_listener(new AdminPage());
?>
