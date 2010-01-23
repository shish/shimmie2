<?php
/*
 * Name: Tag History
 * Author: Bzchan <bzchan@animemahou.com>
 * Description: Keep a record of tag changes
 */

class Tag_History implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		global $config, $database, $page, $user;
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if(($event instanceof InitExtEvent)) {
			$config->set_default_int("history_limit", -1);

			// shimmie is being installed so call install to create the table.
			if($config->get_int("ext_tag_history_version") < 3) {
				$this->install();
			}
		}

		if(($event instanceof PageRequestEvent) && $event->page_matches("tag_history"))
		{
			if($event->get_arg(0) == "revert")
			{
				// this is a request to revert to a previous version of the tags
				if($config->get_bool("tag_edit_anon") || !$user->is_anonymous()) {
					$this->process_revert_request($_POST['revert']);
				}
			}
			else if($event->count_args() == 1)
			{
				// must be an attempt to view a tag history
				$image_id = int_escape($event->get_arg(0));
				$this->theme->display_history_page($page, $image_id, $this->get_tag_history_from_id($image_id));
			}
			else {
				$this->theme->display_global_page($page, $this->get_global_tag_history());
			}
		}
		if(($event instanceof DisplayingImageEvent))
		{
			// handle displaying a link on the view page
			$this->theme->display_history_link($page, $event->image->id);
		}
		if(($event instanceof ImageDeletionEvent))
		{
			// handle removing of history when an image is deleted
			$this->delete_all_tag_history($event->image->id);
		}
		if(($event instanceof SetupBuildingEvent)) {
			$sb = new SetupBlock("Tag History");
			$sb->add_label("Limit to ");
			$sb->add_int_option("history_limit");
			$sb->add_label(" entires per image");
			$sb->add_label("<br>(-1 for unlimited)");
			$event->panel->add_block($sb);
		}
		if(($event instanceof TagSetEvent)) {
			$this->add_tag_history($event->image, $event->tags);
		}
	}
	
	protected function install()
	{
		global $database;
		global $config;

		if($config->get_int("ext_tag_history_version") < 1) {
			$database->create_table("tag_histories", "
	    		id SCORE_AIPK,
	    		image_id INTEGER NOT NULL,
				user_id INTEGER NOT NULL,
				user_ip SCORE_INET NOT NULL,
	    		tags TEXT NOT NULL,
				date_set DATETIME NOT NULL,
				INDEX(image_id),
				FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
				FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
			");
			$config->set_int("ext_tag_history_version", 3);
		}
		
		if($config->get_int("ext_tag_history_version") == 1) {
			$database->Execute("ALTER TABLE tag_histories ADD COLUMN user_id INTEGER NOT NULL");
			$database->Execute("ALTER TABLE tag_histories ADD COLUMN date_set DATETIME NOT NULL");
			$config->set_int("ext_tag_history_version", 2);
		}

		if($config->get_int("ext_tag_history_version") == 2) {
			$database->Execute("ALTER TABLE tag_histories ADD COLUMN user_ip CHAR(15) NOT NULL");
			$config->set_int("ext_tag_history_version", 3);
		}
	}
	
	/*
	 * this function is called when a revert request is received
	 */
	private function process_revert_request($revert_id)
	{
		global $page;
		// check for the nothing case
		if($revert_id=="nothing")
		{
			// tried to set it too the same thing so ignore it (might be a bot)
			// go back to the index page with you
			$page->set_mode("redirect");
			$page->set_redirect(make_link());
			return;
		}
		
		$revert_id = int_escape($revert_id);
		
		// lets get this revert id assuming it exists
		$result = $this->get_tag_history_from_revert($revert_id);
		
		if($result==null)
		{
			// there is no history entry with that id so either the image was deleted
			// while the user was viewing the history, someone is playing with form
			// variables or we have messed up in code somewhere.
			die("Error: No tag history with specified id was found.");
		}
		
		// lets get the values out of the result
		$stored_result_id = $result->fields['id'];
		$stored_image_id = $result->fields['image_id'];
		$stored_tags = $result->fields['tags'];
		
		log_debug("tag_history", "Reverting tags of $stored_image_id to [$stored_tags]");
		// all should be ok so we can revert by firing the SetUserTags event.
		send_event(new TagSetEvent(Image::by_id($stored_image_id), $stored_tags));
		
		// all should be done now so redirect the user back to the image
		$page->set_mode("redirect");
		$page->set_redirect(make_link("post/view/$stored_image_id"));
	}
	
	public function get_tag_history_from_revert($revert_id)
	{
		global $database;
		$row = $database->execute("
				SELECT tag_histories.*, users.name
				FROM tag_histories
				JOIN users ON tag_histories.user_id = users.id
				WHERE tag_histories.id = ?", array($revert_id));
		return ($row ? $row : null);
	}
	
	public function get_tag_history_from_id($image_id)
	{
		global $database;
		$row = $database->get_all("
				SELECT tag_histories.*, users.name
				FROM tag_histories
				JOIN users ON tag_histories.user_id = users.id
				WHERE image_id = ?
				ORDER BY tag_histories.id DESC",
				array($image_id));
		return ($row ? $row : array());
	}
	
	public function get_global_tag_history()
	{
		global $database;
		$row = $database->get_all("
				SELECT tag_histories.*, users.name
				FROM tag_histories
				JOIN users ON tag_histories.user_id = users.id
				ORDER BY tag_histories.id DESC
				LIMIT 100");
		return ($row ? $row : array());
	}
	
	/*
	 * this function is called when an image has been deleted
	 */
	private function delete_all_tag_history($image_id)
	{
		global $database;
		$database->execute("DELETE FROM tag_histories WHERE image_id = ?", array($image_id));
	}

	/*
	 * this function is called just before an images tag are changed
	 */
	private function add_tag_history($image, $tags)
	{
		global $database;
		global $config;
		global $user;

		$new_tags = Tag::implode($tags);
		$old_tags = Tag::implode($image->get_tag_array());
		log_debug("tag_history", "adding tag history: [$old_tags] -> [$new_tags]");
		if($new_tags == $old_tags) return;

		// add a history entry		
		$allowed = $config->get_int("history_limit");
		if($allowed == 0) return;

		$row = $database->execute("
				INSERT INTO tag_histories(image_id, tags, user_id, user_ip, date_set)
				VALUES (?, ?, ?, ?, now())",
				array($image->id, $new_tags, $user->id, $_SERVER['REMOTE_ADDR']));
		
		// if needed remove oldest one
		if($allowed == -1) return;
		$entries = $database->db->GetOne("SELECT COUNT(*) FROM tag_histories WHERE image_id = ?", array($image->id));
		if($entries > $allowed)
		{
			// TODO: Make these queries better
			$min_id = $database->db->GetOne("SELECT MIN(id) FROM tag_histories WHERE image_id = ?", array($image->id));
			$database->execute("DELETE FROM tag_histories WHERE id = ?", array($min_id));
		}
	}
}
add_event_listener(new Tag_History(), 40); // in before tags are actually set, so that "get current tags" works
?>
