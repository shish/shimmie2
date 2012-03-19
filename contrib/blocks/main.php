<?php
/*
 * Name: Generic Blocks
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Add HTML to some space
 * Documentation:
 *  Any HTML is allowed
 *  <br>Separate different blocks with a line of 4 dashes
 *  <br>Within each block, some settings can be set.
 *  <br>Example settings
 *  <pre>
 *  Title: some text
 *  Area: main
 *  Priority: 100
 *  Pages: *
 *  
 *  Here is some &lt;b&gt;html&lt;/b&gt;
 *  ----
 *  Title: another block, on the left this time
 *  Priority: 0
 *  Pages: post/view/*
 *  
 *  Area can be "left" or "main" in the default theme
 *  other themes may have more areas. Priority has 0
 *  near the top of the screen and 100 near the bottom
 *  </pre>
 */

class Blocks extends Extension {
	public function onInitExt(InitExtEvent $event) {
		global $config, $database;
		if($config->get_int("ext_blocks_version") < 1) {
			$database->create_table("blocks", "
				id SCORE_AIPK,
				pages VARCHAR(128) NOT NULL,
				title VARCHAR(128) NOT NULL,
				area VARCHAR(16) NOT NULL,
				priority INTEGER NOT NULL,
				content TEXT NOT NULL,
				INDEX (pages)
			");
			$config->set_int("ext_blocks_version", 1);
		}
	}

	public function onUserBlockBuilding(UserBlockBuildingEvent $event) {
		global $user;
		if($user->can("manage_blocks")) {
			$event->add_link("Blocks Editor", make_link("blocks/list"));
		}
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $config, $database, $page, $user;

		$blocks = $database->get_all("SELECT * FROM blocks");
		foreach($blocks as $block) {
			if(fnmatch($block['pages'], implode("/", $event->args))) {
				$page->add_block(new Block($block['title'], $block['content'], $block['area'], $block['priority']));
			}
		}

		if($event->page_matches("blocks") && $user->can("manage_blocks")) {
			if($event->get_arg(0) == "add") {
				if($user->check_auth_token()) {
					$database->execute("
						INSERT INTO blocks (pages, title, area, priority, content)
						VALUES (?, ?, ?, ?, ?)
					", array($_POST['pages'], $_POST['title'], $_POST['area'], (int)$_POST['priority'], $_POST['content']));
					$page->set_mode("redirect");
					$page->set_redirect(make_link("blocks/list"));
				}
			}
			if($event->get_arg(0) == "update") {
				if($user->check_auth_token()) {
					if(!empty($_POST['delete'])) {
						$database->execute("
							DELETE FROM blocks
							WHERE id=?
						", array($_POST['id']));
					}
					else {
						$database->execute("
							UPDATE blocks SET pages=?, title=?, area=?, priority=?, content=?
							WHERE id=?
						", array($_POST['pages'], $_POST['title'], $_POST['area'], (int)$_POST['priority'], $_POST['content'], $_POST['id']));
					}
					$page->set_mode("redirect");
					$page->set_redirect(make_link("blocks/list"));
				}
			}
			else if($event->get_arg(0) == "remove") {
				if($user->check_auth_token()) {
					$database->execute("DELETE FROM blocks WHERE id=:id", array("id" => $_POST['id']));
					log_info("alias_editor", "Deleted Block #".$_POST['id']);

					$page->set_mode("redirect");
					$page->set_redirect(make_link("blocks/list"));
				}
			}
			else if($event->get_arg(0) == "list") {
				$this->theme->display_blocks($database->get_all("SELECT * FROM blocks"));
			}
		}
	}
}
?>
