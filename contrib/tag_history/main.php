<?php
/**
 * Name: Tag History
 * Author: Bzchan <bzchan@animemahou.com>
 * Description: Keep a record of tag changes
 */

class Tag_History extends Extension {
	var $theme;

	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("tag_history", "Tag_HistoryTheme");

		if(is_a($event, 'InitExtEvent')) {
			// shimmie is being installed so call install to create the table.
			global $config;
			if($config->get_int("ext_tag_history_version") < 3) {
				$this->install();
			}
		}

		if(is_a($event, 'PageRequestEvent') && ($event->page_name == "tag_history"))
		{
			if($event->get_arg(0) == "revert")
			{
				// this is a request to revert to a previous version of the tags
				$this->process_revert_request($_POST['image_id'], $_POST['revert']);
			}
			else
			{
				// must be an attempt to view a tag history
				$image_id = int_escape($event->get_arg(0));
				$this->theme->display_history_page($event->page, $image_id, $this->build_tag_history($image_id));
			}
		}
		if(is_a($event, 'DisplayingImageEvent'))
		{
			// handle displaying a link on the view page
			$this->theme->display_history_link($event->page, $event->image->id);
		}
		if(is_a($event, 'ImageDeletionEvent'))
		{
			// handle removing of history when an image is deleted
			$this->delete_all_tag_history($event->image->id);
		}
		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("Tag History");
			$sb->add_label("Limit to ");
			$sb->add_int_option("history_limit");
			$sb->add_label(" entires per image");
			$event->panel->add_block($sb);
		}
		if(is_a($event, 'TagSetEvent')) {
			$this->add_tag_history($event->image_id, $event->tags);
		}
	}
	
	protected function install()
	{
		global $database;
		global $config;

		if($config->get_int("ext_tag_history_version") < 1) {
			$database->Execute("CREATE TABLE tag_histories
			(
	    		id integer NOT NULL auto_increment PRIMARY KEY,
	    		image_id integer NOT NULL,
	    		tags text NOT NULL
			)");
			$config->set_int("ext_tag_history_version", 1);
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
	
	private function process_revert_request($image_id, $revert_id)
	{
		// this function is called when a revert request is received
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
		$image_id = int_escape($image_id);
		
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
		
		if($image_id!=$stored_image_id)
		{
			// wth is going on there ids should be the same otherwise we are trying
			// to edit another image... banhammer this user... j/k
			die("Error: Mismatch in history image ids.");
		}
		
		// all should be ok so we can revert by firing the SetUserTags event.
		send_event(new TagSetEvent($image_id, $stored_tags));
		
		// all should be done now so redirect the user back to the image
		$page->set_mode("redirect");
		$page->set_redirect(make_link("post/view/$image_id"));
	}
	
	
	
	private function build_tag_history($image_id)
	{
		// this function is called when user tries to view tag history of an image
		// check if the image exists
		global $database;
		$image = $database->get_image($image_id);
		if($image==null)return "<strong>No image with the specified id currently exists</strong>";
				
		// get the current images tags
		$current_tags = html_escape(implode(' ', $image->get_tag_array()));
		//$current_tags = $image->cached_tags;
		
		// get any stored tag histories
		$result = $this->get_tag_history_from_id($image_id);
		$html =  "<div style='text-align: left'>";
		$html .= "<br><form enctype='multipart/form-data' action='".make_link("tag_history/revert")."' method='POST'>\n";
		$html .= "<input type='hidden' name='image_id' value='$image_id'>\n";
		$html .= "<ul style='list-style-type:none;'>\n";
		// $html .= "<li><input type='radio' name='revert' value='nothing' checked>$current_tags (<strong>current</strong>)<br></li>\n";
		
		$end_string = "<input type='submit' value='Revert'></ul></form></div>";
		// check for no stored history
		if($result==null) return $html.$end_string;
		
		global $user;

		// process each one
		while(!$result->EOF)
		{
			$fields = $result->fields;
			$current_id = $fields['id'];
			$current_tags = $fields['tags'];
			$setter = $fields['name'];
			if($user->is_admin()) {
				$setter .= " / " . $fields['user_ip'];
			}
			$html .= "<li><input type='radio' name='revert' value='$current_id'>$current_tags (Set by $setter)<br></li>\n";
			$result->MoveNext();	
		}
		$html .= $end_string;
		
		// now return the finished html
		return $html;
	}
	
	public function get_tag_history_from_revert($revert_id)
	{
		global $database;
		$row = $database->execute("
				SELECT *
				FROM tag_histories
				JOIN users ON tag_histories.user_id = users.id
				WHERE tag_histories.id = ?", array($revert_id));
		return ($row ? $row : null);
	}
	
	public function get_tag_history_from_id($image_id)
	{
		global $database;
		$row = $database->execute("
				SELECT *
				FROM tag_histories
				JOIN users ON tag_histories.user_id = users.id
				WHERE image_id = ?
				ORDER BY tag_histories.id DESC",
				array($image_id));
		return ($row ? $row : null);
	}
	
	private function delete_all_tag_history($image_id)
	{
		// this function is called when an image has been deleted
		global $database;
		$database->execute("DELETE FROM tag_histories WHERE image_id = ?", array($image_id));
	}

	private function add_tag_history($image_id, $tags)
	{
		// this function is called just before an images tag are changed
		global $database;
		global $config;
		global $user;
		
		// add a history entry		
		$allowed = $config->get_int("history_limit",10);
		if($allowed<=0) return;
		$row = $database->execute("
				INSERT INTO tag_histories(image_id, tags, user_id, user_ip, date_set)
				VALUES (?, ?, ?, ?, now())",
				array($image_id, $tags, $user->id, $_SERVER['REMOTE_ADDR']));
		$entries = $database->db->GetOne("SELECT COUNT(*) FROM `tag_histories` WHERE image_id = ?", array($image_id));
		
		// if needed remove oldest one
		if($entries > $allowed)
		{
			// TODO: Make these queries better
			$min_id = $database->db->GetOne("SELECT MIN(id) FROM tag_histories WHERE image_id = ?", array($image_id));
			$database->execute("DELETE FROM tag_histories WHERE id = ?", array($min_id));
		}
	}
}
add_event_listener(new Tag_History());
?>
