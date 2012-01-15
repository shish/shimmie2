<?php
/*
 * Name: Tag History
 * Author: Bzchan <bzchan@animemahou.com>, modified by jgen <jgen.tech@gmail.com>
 * Description: Keep a record of tag changes, and allows you to revert changes.
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
		
		if(($event instanceof AdminBuildingEvent))
		{
			if(isset($_POST['revert_ip']) && $user->is_admin() && $user->check_auth_token())
			{
				$revert_ip = filter_var($_POST['revert_ip'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE);
				
				if ($revert_ip === false) {
					// invalid ip given.
					$this->theme->display_admin_block('Invalid IP');
					return;
				}
				
				if (isset($_POST['revert_date']) && !empty($_POST['revert_date'])) {
					if (isValidDate($_POST['revert_date'])){
						$revert_date = addslashes($_POST['revert_date']); // addslashes is really unnecessary since we just checked if valid, but better safe.
					} else {
						$this->theme->display_admin_block('Invalid Date');
						return;
					}
				} else {
					$revert_date = null;
				}
				
				set_time_limit(0); // reverting changes can take a long time, disable php's timelimit if possible.
				
				// Call the revert function.
				$this->process_revert_all_changes_by_ip($revert_ip, $revert_date);
				// output results
				$this->theme->display_revert_ip_results();
			}
			else
			{				
				$this->theme->display_admin_block(); // add a block to the admin panel
			}
		}
		
		if (($event instanceof PageRequestEvent) && ($event->page_matches("tag_history")))
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
		if($event instanceof UserBlockBuildingEvent) {
			if($user->is_admin()) {
				$event->add_link("Tag Changes", make_link("tag_history"));
			}
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
		if(empty($revert_id) || $revert_id=="nothing")
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
		
		if(empty($result))
		{
			// there is no history entry with that id so either the image was deleted
			// while the user was viewing the history, someone is playing with form
			// variables or we have messed up in code somewhere.
			/* calling die() is probably not a good idea, we should throw an Exception */
			die("Error: No tag history with specified id was found.");
		}
		
		// lets get the values out of the result
		$stored_result_id = $result['id'];
		$stored_image_id = $result['image_id'];
		$stored_tags = $result['tags'];
		
		log_debug("tag_history", 'Reverting tags of Image #'.$stored_image_id.' to ['.$stored_tags.']');
		// all should be ok so we can revert by firing the SetUserTags event.
		send_event(new TagSetEvent(Image::by_id($stored_image_id), $stored_tags));
		
		// all should be done now so redirect the user back to the image
		$page->set_mode("redirect");
		$page->set_redirect(make_link('post/view/'.$stored_image_id));
	}
	
	/*
	 * This function is used by   process_revert_all_changes_by_ip()
	 * to just revert an image's tag history.
	 */
	private function process_revert_request_only($revert_id)
	{
		if(empty($revert_id)) {
			return;
		}
		$id = (int) $revert_id;
		$result = $this->get_tag_history_from_revert($id);
		
		if(empty($result)) {
			// there is no history entry with that id so either the image was deleted
			// while the user was viewing the history,  or something messed up
			/* calling die() is probably not a good idea, we should throw an Exception */
			die('Error: No tag history with specified id ('.$id.') was found in the database.'."\n\n".
				'Perhaps the image was deleted while processing this request.');
		}
		
		// lets get the values out of the result
		$stored_result_id = $result['id'];
		$stored_image_id = $result['image_id'];
		$stored_tags = $result['tags'];
		
		log_debug("tag_history", 'Reverting tags of Image #'.$stored_image_id.' to ['.$stored_tags.']');
		// all should be ok so we can revert by firing the SetUserTags event.
		send_event(new TagSetEvent(Image::by_id($stored_image_id), $stored_tags));
	}
	
	public function get_tag_history_from_revert($revert_id)
	{
		global $database;
		$row = $database->get_row("
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
	
	/* This doesn't actually get _ALL_ IPs as it limits to 1000. */
	public function get_all_user_ips()
	{
		global $database;
		$row = $database->get_all("
				SELECT DISTINCT user_ip
				FROM tag_histories
				ORDER BY tag_histories.user_ip DESC
				LIMIT 1000");
		return ($row ? $row : array());
	}
	
	/*
	 * This function attempts to revert all changes by a given IP within an (optional) timeframe.
	 */
	public function process_revert_all_changes_by_ip($ip, $date=null)
	{
		global $database;
		$date_select = '';
		
		if (!empty($date)) {
			$date_select = 'and date_set >= '.$date;
		} else {
			$date = 'forever';
		}
		
		log_info("tag_history", 'Attempting to revert edits by ip='.$ip.' (from '.$date.' to now).');
		
		// Get all the images that the given IP has changed tags on (within the timeframe) that were last editied by the given IP
		$result = $database->get_all('
				SELECT t1.image_id FROM tag_histories t1 LEFT JOIN tag_histories t2
				ON (t1.image_id = t2.image_id AND t1.date_set < t2.date_set)
				WHERE t2.image_id IS NULL AND t1.user_ip="'.$ip.'" AND t1.image_id IN
				( select image_id from `tag_histories` where user_ip="'.$ip.'" '.$date_select.') 
				ORDER BY t1.image_id;');
	
		if (empty($result)) {
			log_info("tag_history", 'Nothing to revert! for ip='.$ip.' (from '.$date.' to now).');
			$this->theme->add_status('Nothing to Revert','Nothing to revert for ip='.$ip.' (from '.$date.' to now)');
			return; // nothing to do.
		}
		
		for ($i = 0 ; $i < count($result) ; $i++)
		{
			$image_id = (int) $result[$i]['image_id'];
			
			// Get the first tag history that was done before the given IP edit
			$row = $database->get_row('
				SELECT id,tags FROM `tag_histories` WHERE image_id="'.$image_id.'" AND user_ip!="'.$ip.'" '.$date_select.' ORDER BY date_set DESC LIMIT 1');
			
			if (empty($row)) {
				// we can not revert this image based on the date restriction.
				// Output a message perhaps?
			} else {
				$id = (int) $row['id'];
				$this->process_revert_request_only($id);
				$this->theme->add_status('Reverted Change','Reverted Image #'.$image_id.' to Tag History #'.$id.' ('.$row['tags'].')');
			}
		}
		log_info("tag_history", 'Reverted '.count($result).' edits by ip='.$ip.' (from '.$date.' to now).');
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
		$allowed = $config->get_int("history_limit");
		if($allowed == 0) return;
		
		// if the image has no history, make one with the old tags
		$entries = $database->get_one("SELECT COUNT(*) FROM tag_histories WHERE image_id = ?", array($image->id));
		if($entries == 0){
			/* these two queries could probably be combined */
			$database->execute("
				INSERT INTO tag_histories(image_id, tags, user_id, user_ip, date_set)
				VALUES (?, ?, ?, ?, now())",
				array($image->id, $old_tags, 1, '127.0.0.1')); // TODO: Pick appropriate user id
			$entries++;
		}

		// add a history entry	
		$row = $database->execute("
				INSERT INTO tag_histories(image_id, tags, user_id, user_ip, date_set)
				VALUES (?, ?, ?, ?, now())",
				array($image->id, $new_tags, $user->id, $_SERVER['REMOTE_ADDR']));
		$entries++;
		
		// if needed remove oldest one
		if($allowed == -1) return;
		if($entries > $allowed)
		{
			// TODO: Make these queries better
			$min_id = $database->get_one("SELECT MIN(id) FROM tag_histories WHERE image_id = ?", array($image->id));
			$database->execute("DELETE FROM tag_histories WHERE id = ?", array($min_id));
		}
	}
}
add_event_listener(new Tag_History(), 40); // in before tags are actually set, so that "get current tags" works
?>
