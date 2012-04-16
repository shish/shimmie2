<?php
/*
 * Name: Blotter
 * Author: Zach Hall <zach@sosguy.net> [http://seemslegit.com/]
 * License: GPLv2
 * Description: Displays brief updates about whatever you want on every page.
 *				Colors and positioning can be configured to match your site's design.
 *
 *				Development TODO at http://github.com/zshall/shimmie2/issues
 */
class Blotter extends Extension {
	public function onInitExt(InitExtEvent $event) {
		/**
		 * I love re-using this installer don't I...
		 */
		global $config;
		$version = $config->get_int("blotter_version", 0);
		/**
		 * If this version is less than "1", it's time to install.
		 *
		 * REMINDER: If I change the database tables, I must change up version by 1.
		 */
		if($version < 1) {
			/**
			 * Installer
			 */
			global $database, $config;
			$database->create_table("blotter", "
					id SCORE_AIPK,
					entry_date SCORE_DATETIME DEFAULT SCORE_NOW,
					entry_text TEXT NOT NULL,
					important SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N
					");
			// Insert sample data:
			$database->execute("INSERT INTO blotter (id, entry_date, entry_text, important) VALUES (?, now(), ?, ?)", 
					array(NULL, "Installed the blotter extension!", "Y"));
			log_info("blotter", "Installed tables for blotter extension.");
			$config->set_int("blotter_version", 1);
		}
		// Set default config:
		$config->set_default_int("blotter_recent", 5);
		$config->set_default_string("blotter_color", "FF0000");
		$config->set_default_string("blotter_position", "subheading");

	}
	public function onSetupBuilding(SetupBuildingEvent $event) {
		global $config;
		$sb = new SetupBlock("Blotter");
		$sb->add_int_option("blotter_recent", "<br />Number of recent entries to display: ");
		$sb->add_text_option("blotter_color", "<br />Color of important updates: (ABCDEF format) ");
		$sb->add_choice_option("blotter_position", array("Top of page" => "subheading", "In navigation bar" => "left"), "<br>Position: ");
		$event->panel->add_block($sb);
	}
	public function onUserBlockBuilding(UserBlockBuildingEvent $event) {
		global $user;
		if($user->is_admin()) {
			$event->add_link("Blotter Editor", make_link("blotter/editor"));
		}
	}
	public function onPageRequest(PageRequestEvent $event) {
		global $page, $database, $user;
		if($event->page_matches("blotter")) {
			switch($event->get_arg(0)) {
				case "editor":		
					/**
					 * Displays the blotter editor.
					 */
					if(!$user->is_admin()) {
						$this->theme->display_permission_denied();
					} else {
						$entries = $database->get_all("SELECT * FROM blotter ORDER BY id DESC");
						$this->theme->display_editor($entries);
					}
					break;
				case "add":
					/**
					 * Adds an entry
					 */
					if(!$user->is_admin() || !$user->check_auth_token()) {
						$this->theme->display_permission_denied();
					} else {
						$entry_text = $_POST['entry_text'];
						if($entry_text == "") { die("No entry message!"); }
						if(isset($_POST['important'])) { $important = 'Y'; } else { $important = 'N'; }
						// Now insert into db:
						$database->execute("INSERT INTO blotter (entry_date, entry_text, important) VALUES (now(), ?, ?)", 
								array($entry_text, $important));
						log_info("blotter", "Added Message: $entry_text");
						$page->set_mode("redirect");
						$page->set_redirect(make_link("blotter/editor"));
					}
					break;
				case "remove":
					/**
					 * Removes an entry
					 */
					if(!$user->is_admin() || !$user->check_auth_token()) {
						$this->theme->display_permission_denied();
					} else {
						$id = (int)($_POST['id']);
						if(!isset($id)) { die("No ID!"); }
						$database->Execute("DELETE FROM blotter WHERE id=:id", array("id"=>$id));
						log_info("blotter", "Removed Entry #$id");
						$page->set_mode("redirect");
						$page->set_redirect(make_link("blotter/editor"));
					}
					break;
				case "":
					/**
					 * Displays all blotter entries
					 */
					$entries = $database->get_all("SELECT * FROM blotter ORDER BY id DESC");
					$this->theme->display_blotter_page($entries);
					break;
			}
		}
		/**
		 * Finally, display the blotter on whatever page we're viewing.
		 */
		$this->display_blotter();
	}

	private function display_blotter() {
		global $database, $config;
		$limit = $config->get_int("blotter_recent", 5);
		$sql = 'SELECT * FROM blotter ORDER BY id DESC LIMIT '.intval($limit);
		$entries = $database->get_all($sql);
		$this->theme->display_blotter($entries);
	}
}
?>
