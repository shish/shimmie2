<?php
/* AdminBuildingEvent {{{
 *
 * Sent when the admin page is ready to be added to
 */
class AdminBuildingEvent extends Event {
	public function AdminBuildingEvent() {
	}
}
// }}}
class AdminPage extends Extension {
// event handler {{{
	public function receive_event($event) {
		if(is_a($event, 'PageRequestEvent') && ($event->page_name == "admin")) {
			global $user;
			if(!$user->is_admin()) {
				global $page;
				$page->set_title("Error");
				$page->set_heading("Error");
				$page->add_block(new NavBlock());
				$page->add_block(new Block("Permission Denied", "This page is for admins only"));
			}
			else {
				if($event->get_arg(0) == "delete_image") {
					// FIXME: missing lots of else {complain}
					if(isset($_POST['image_id'])) {
						global $database;
						$image = $database->get_image($_POST['image_id']);
						if($image) {
							send_event(new ImageDeletionEvent($image));
							global $page;
							$page->set_mode("redirect");
							$page->set_redirect(make_link("index"));
						}
					}
				}
				else {
					send_event(new AdminBuildingEvent());
				}
			}
		}

		if(is_a($event, 'DisplayingImageEvent')) {
			global $user;
			if($user->is_admin()) {
				$event->page->add_block(new Block("Admin", $this->build_del_block($event->image->id), "left"));
			}
		}

		if(is_a($event, 'AdminBuildingEvent')) {
			$this->build_page();
		}

		if(is_a($event, 'UserBlockBuildingEvent')) {
			if($event->user->is_admin()) {
				$event->add_link("Board Admin", make_link("admin"));
			}
		}
	}
// }}}
// block HTML {{{
	private function build_del_block($image_id) {
		$i_image_id = int_escape($image_id);
		return "
			<form action='".make_link("admin/delete_image")."' method='POST'>
				<input type='hidden' name='image_id' value='$i_image_id'>
				<input type='submit' value='Delete'>
			</form>
		";
	}
// }}}
// admin page HTML {{{
	private function build_page() {
		global $page;
		$page->set_title("Admin Tools");
		$page->set_heading("Admin Tools");
		$page->add_block(new NavBlock());
	}
// }}}
}
add_event_listener(new AdminPage());
?>
