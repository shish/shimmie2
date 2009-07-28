<?php
/**
 * Name: Favorites
 * Author: Daniel Marschall <info@daniel-marschall.de>
 * License: GPLv2
 * Description: Allow users to favorite images
 */

class FavoriteSetEvent extends Event {
	var $image_id, $user, $do_set;

	public function FavoriteSetEvent($image_id, $user, $do_set) {
		$this->image_id = $image_id;
		$this->user = $user;
		$this->do_set = $do_set;
	}
}

class Favorites extends Extension {
	var $theme;

	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("favorites", "FavoritesTheme");

		if(is_a($event, 'InitExtEvent')) {
			global $config;
			if($config->get_int("ext_favorites_version", 0) < 1) {
				$this->install();
			}
		}
		
		if(is_a($event, 'DisplayingImageEvent')) {
			global $user;
			if(!$user->is_anonymous()) {
			
				$user_id = $user->id;				
				$image_id = $event->image->id;
				
				global $database;
				
				$is_favorited = false;
				$sqlresult = $database->execute("SELECT COUNT(*) AS ct FROM user_favorites WHERE user_id = ? AND image_id = ?", array($user_id, $image_id));
				if(!$sqlresult->EOF)
				{
					$is_favorited = $sqlresult->fields['ct'] > 0;
				}
			
				$html = $this->theme->get_voter_html($event->image, $is_favorited);
			} else {
				$html = $this->theme->show_anonymous_html($event->image);
			}
			$event->page->add_block(new Block("Favorites", $html, "left", 20));
			
			$html = $this->theme->show_favorite_marks($this->list_persons_who_have_favorited($event->image));

			$event->page->add_block(new Block("Favorited by", $html, "left", 25));
		}
		
		if(is_a($event, 'PageRequestEvent') && ($event->page_name == "change_favorite")) {
			if(!$event->user->is_anonymous()) {
				$image_id = int_escape($_POST['image_id']);
				if (($_POST['favorite_action'] == "set") || ($_POST['favorite_action'] == "unset")) {
					send_event(new FavoriteSetEvent($image_id, $event->user, ($_POST['favorite_action'] == "set")));
				}
				$event->page->set_mode("redirect");
				$event->page->set_redirect(make_link("post/view/$image_id"));
			}
		}
		
		if(is_a($event, 'ImageInfoSetEvent')) {
			global $user;
			if (($_POST['favorite_action'] == "set") || ($_POST['favorite_action'] == "unset")) {
				send_event(new FavoriteSetEvent($event->image_id, $user, ($_POST['favorite_action'] == "set")));
			}
		}
		
		if(is_a($event, 'FavoriteSetEvent')) {
			$this->add_vote($event->image_id, $event->user->id, $event->do_set);
		}

		if(is_a($event, 'ImageDeletionEvent')) {
			global $database;
			$database->execute("DELETE FROM user_favorites WHERE image_id=?", array($event->image->id));
		}

		if(is_a($event, 'ParseLinkTemplateEvent')) {
			$event->replace('$favorites', $event->image->favorites);
		}

		if(is_a($event, 'SearchTermParseEvent')) {
			$matches = array();
			if(preg_match("/favorites(<|>|<=|>=|=)(\d+)/", $event->term, $matches)) {
				$cmp = $matches[1];
				$favorites = $matches[2];
				$event->set_querylet(new Querylet("favorites $cmp $favorites"));
			}
			else if(preg_match("/favorited_by=(.*)/i", $event->term, $matches)) {
				global $database;
				$user = $database->get_user_by_name($matches[1]);
				if(!is_null($user)) {
					$user_id = $user->id;
				}
				else {
					$user_id = -1;
				}

				$event->set_querylet(new Querylet("images.id IN (SELECT image_id FROM user_favorites WHERE user_id = $user_id)"));
			}
			else if(preg_match("/favorited_by_userno=([0-9]+)/i", $event->term, $matches)) {
				$user_id = int_escape($matches[1]);
				$event->set_querylet(new Querylet("images.id IN (SELECT image_id FROM user_favorites WHERE user_id = $user_id)"));
			}
		}
	}

	private function install() {
		global $database;
		global $config;

		if($config->get_int("ext_favorites_version") < 1) {
			$database->Execute("ALTER TABLE images ADD COLUMN favorites INTEGER NOT NULL DEFAULT 0");
			$database->Execute("CREATE INDEX images__favorites ON images(favorites)");
			$database->Execute("
				CREATE TABLE user_favorites (
					image_id INTEGER NOT NULL,
					user_id INTEGER NOT NULL,
					created_at DATETIME NOT NULL,,
					UNIQUE(image_id, user_id),
					INDEX(image_id)
				)
			");
			$config->set_int("ext_favorites_version", 1);
		}
	}

	private function add_vote($image_id, $user_id, $do_set) {
		global $database;
		if ($do_set) {
			$database->Execute(
				"INSERT INTO user_favorites(image_id, user_id, created_at) VALUES(?, ?, NOW())",
				array($image_id, $user_id));
		} else {
			$database->Execute(
				"DELETE FROM user_favorites WHERE image_id = ? AND user_id = ?",
				array($image_id, $user_id));
		}
		$database->Execute(
			"UPDATE images SET favorites=(SELECT COUNT(*) FROM user_favorites WHERE image_id=?) WHERE id=?",
			array($image_id, $image_id));
	}
	
	private function list_persons_who_have_favorited($image) {
		global $database;

		$result = $database->execute(
				"SELECT name FROM users WHERE id IN (SELECT user_id FROM user_favorites WHERE image_id = ?) ORDER BY name",
				array($image->id));
				
		return $result->GetArray();
	}
}
add_event_listener(new Favorites());
?>
