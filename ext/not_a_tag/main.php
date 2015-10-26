<?php
/*
 * Name: Not A Tag
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Redirect users to the rules if they use bad tags
 */
class NotATag extends Extension {
	public function get_priority() {return 30;} // before ImageUploadEvent and tag_history

	public function onInitExt(InitExtEvent $event) {
		global $config, $database;
		if($config->get_int("ext_notatag_version") < 1) {
			$database->create_table("untags", "
				tag VARCHAR(128) NOT NULL PRIMARY KEY,
				redirect VARCHAR(255) NOT NULL
			");
			$config->set_int("ext_notatag_version", 1);
		}
	}

	public function onImageAddition(ImageAdditionEvent $event) {
		$this->scan($event->image->get_tag_array());
	}

	public function onTagSet(TagSetEvent $event) {
		$this->scan($event->tags);
	}

	/**
	 * @param string[] $tags_mixed
	 */
	private function scan($tags_mixed) {
		global $database;

		$tags = array();
		foreach($tags_mixed as $tag) $tags[] = strtolower($tag);

		$pairs = $database->get_all("SELECT * FROM untags");
		foreach($pairs as $tag_url) {
			$tag = strtolower($tag_url[0]);
			$url = $tag_url[1];
			if(in_array($tag, $tags)) {
				header("Location: $url");
				exit; # FIXME: need a better way of aborting the tag-set or upload
			}
		}
	}

	public function onUserBlockBuilding(UserBlockBuildingEvent $event) {
		global $user;
		if($user->can("ban_image")) {
			$event->add_link("UnTags", make_link("untag/list/1"));
		}
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $database, $page, $user;

		if($event->page_matches("untag")) {
			if($user->can("ban_image")) {
				if($event->get_arg(0) == "add") {
					$tag = $_POST["tag"];
					$redirect = isset($_POST['redirect']) ? $_POST['redirect'] : "DNP";

					$database->Execute(
							"INSERT INTO untags(tag, redirect) VALUES (?, ?)",
							array($tag, $redirect));

					$page->set_mode("redirect");
					$page->set_redirect($_SERVER['HTTP_REFERER']);
				}
				else if($event->get_arg(0) == "remove") {
					if(isset($_POST['tag'])) {
						$database->Execute("DELETE FROM untags WHERE tag = ?", array($_POST['tag']));

						flash_message("Image ban removed");
						$page->set_mode("redirect");
						$page->set_redirect($_SERVER['HTTP_REFERER']);
					}
				}
				else if($event->get_arg(0) == "list") {
					$page_num = 0;
					if($event->count_args() == 2) {
						$page_num = int_escape($event->get_arg(1));
					}
					$page_size = 100;
					$page_count = ceil($database->get_one("SELECT COUNT(tag) FROM untags")/$page_size);
					$this->theme->display_untags($page, $page_num, $page_count, $this->get_untags($page_num, $page_size));
				}
			}
		}
	}

	/**
	 * @param int $page
	 * @param int $size
	 * @return array
	 */
	public function get_untags($page, $size=100) {
		global $database;

		// FIXME: many
		$size_i = int_escape($size);
		$offset_i = int_escape($page-1)*$size_i;
		$where = array("(1=1)");
		$args = array();
		if(!empty($_GET['tag'])) {
			$where[] = 'tag SCORE_ILIKE ?';
			$args[] = "%".$_GET['tag']."%";
		}
		if(!empty($_GET['redirect'])) {
			$where[] = 'redirect SCORE_ILIKE ?';
			$args[] = "%".$_GET['redirect']."%";
		}
		$where = implode(" AND ", $where);
		$bans = $database->get_all($database->scoreql_to_sql("
			SELECT *
			FROM untags
			WHERE $where
			ORDER BY tag
			LIMIT $size_i
			OFFSET $offset_i
			"), $args);
		if($bans) {return $bans;}
		else {return array();}
	}
}

