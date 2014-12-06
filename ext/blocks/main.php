<?php
/*
 * Name: Generic Blocks
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Add HTML to some space (News, Ads, etc)
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
				content TEXT NOT NULL
			");
			$database->execute("CREATE INDEX blocks_pages_idx ON blocks(pages)", array());
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
		global $database, $page, $user;

		$blocks = $database->cache->get("blocks");
		if($blocks === false) {
			$blocks = $database->get_all("SELECT * FROM blocks");
			$database->cache->set("blocks", $blocks, 600);
		}
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
					log_info("blocks", "Added Block #".($database->get_last_insert_id('blocks_id_seq'))." (".$_POST['title'].")");
					$database->cache->delete("blocks");
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
						log_info("blocks", "Deleted Block #".$_POST['id']);
					}
					else {
						$database->execute("
							UPDATE blocks SET pages=?, title=?, area=?, priority=?, content=?
							WHERE id=?
						", array($_POST['pages'], $_POST['title'], $_POST['area'], (int)$_POST['priority'], $_POST['content'], $_POST['id']));
						log_info("blocks", "Updated Block #".$_POST['id']." (".$_POST['title'].")");
					}
					$database->cache->delete("blocks");
					$page->set_mode("redirect");
					$page->set_redirect(make_link("blocks/list"));
				}
			}
			else if($event->get_arg(0) == "list") {
				$this->theme->display_blocks($database->get_all("SELECT * FROM blocks ORDER BY area, priority"));
			}
		}
	}
}

