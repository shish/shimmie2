<?php
/*
 * Name: Favorites
 * Author: Daniel Marschall <info@daniel-marschall.de>
 * License: GPLv2
 * Description: Allow users to favorite images
 * Documentation:
 *  Gives users a "favorite this image" button that they can press
 *  <p>Favorites for a user can then be retrieved by searching for
 *  "favorited_by=UserName"
 *  <p>Popular images can be searched for by eg. "favorites>5"
 *  <p>Favorite info can be added to an image's filename or tooltip
 *  using the $favorites placeholder
 */

class FavoriteSetEvent extends Event {
	var $image_id, $user, $do_set;

	public function FavoriteSetEvent(/*int*/ $image_id, User $user, /*boolean*/ $do_set) {
		assert(is_numeric($image_id));
		assert(is_bool($do_set));

		$this->image_id = $image_id;
		$this->user = $user;
		$this->do_set = $do_set;
	}
}

class Favorites extends Extension {
	public function onInitExt(InitExtEvent $event) {
		global $config;
		if($config->get_int("ext_favorites_version", 0) < 1) {
			$this->install();
		}
	}

	public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event) {
		global $database, $page, $user;
		if(!$user->is_anonymous()) {
			$user_id = $user->id;
			$image_id = $event->image->id;

			$is_favorited = $database->get_one(
				"SELECT COUNT(*) AS ct FROM user_favorites WHERE user_id = :user_id AND image_id = :image_id",
				array("user_id"=>$user_id, "image_id"=>$image_id)) > 0;
		
			$event->add_part($this->theme->get_voter_html($event->image, $is_favorited));
		}
	}

	public function onDisplayingImage(DisplayingImageEvent $event) {
		$people = $this->list_persons_who_have_favorited($event->image);
		if(count($people) > 0) {
			$html = $this->theme->display_people($people);
		}
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $page, $user;
		if($event->page_matches("change_favorite") && !$user->is_anonymous() && $user->check_auth_token()) {
			$image_id = (int)($_POST['image_id']);
			if((($_POST['favorite_action'] == "set") || ($_POST['favorite_action'] == "unset")) && ($image_id > 0)) {
				send_event(new FavoriteSetEvent($image_id, $user, ($_POST['favorite_action'] == "set")));
			}
			$page->set_mode("redirect");
			$page->set_redirect(make_link("post/view/$image_id"));
		}
	}

	public function onUserPageBuilding(UserPageBuildingEvent $event) {
		$i_favorites_count = Image::count_images(array("favorited_by={$event->display_user->name}"));
		$i_days_old = ((time() - strtotime($event->display_user->join_date)) / 86400) + 1;
		$h_favorites_rate = sprintf("%.1f", ($i_favorites_count / $i_days_old));
		$favorites_link = make_link("post/list/favorited_by={$event->display_user->name}/1");
		$event->add_stats("<a href='$favorites_link'>Images favorited</a>: $i_favorites_count, $h_favorites_rate per day");
	}

	public function onImageInfoSet(ImageInfoSetEvent $event) {
		global $user;
		if(
			in_array('favorite_action', $_POST) &&
			(($_POST['favorite_action'] == "set") || ($_POST['favorite_action'] == "unset"))
		) {
			send_event(new FavoriteSetEvent($event->image_id, $user, ($_POST['favorite_action'] == "set")));
		}
	}

	public function onFavoriteSet(FavoriteSetEvent $event) {
		global $user;
		$this->add_vote($event->image_id, $user->id, $event->do_set);
	}

	// FIXME: this should be handled by the foreign key. Check that it
	// is, and then remove this
	public function onImageDeletion(ImageDeletionEvent $event) {
		global $database;
		$database->execute("DELETE FROM user_favorites WHERE image_id=:image_id", array("image_id"=>$event->image->id));
	}

	public function onParseLinkTemplate(ParseLinkTemplateEvent $event) {
		$event->replace('$favorites', $event->image->favorites);
	}

	public function onUserBlockBuilding(UserBlockBuildingEvent $event) {
		global $user;

		$username = url_escape($user->name);
		$event->add_link("My Favorites", make_link("post/list/favorited_by=$username/1"), 20);
	}

	public function onSearchTermParse(SearchTermParseEvent $event) {
		$matches = array();
		if(preg_match("/favorites(<|>|<=|>=|=)(\d+)/", $event->term, $matches)) {
			$cmp = $matches[1];
			$favorites = $matches[2];
			$event->add_querylet(new Querylet("images.id IN (SELECT id FROM images WHERE favorites $cmp $favorites)"));
		}
		else if(preg_match("/favorited_by=(.*)/i", $event->term, $matches)) {
			global $database;
			$user = User::by_name($matches[1]);
			if(!is_null($user)) {
				$user_id = $user->id;
			}
			else {
				$user_id = -1;
			}

			$event->add_querylet(new Querylet("images.id IN (SELECT image_id FROM user_favorites WHERE user_id = $user_id)"));
		}
		else if(preg_match("/favorited_by_userno=([0-9]+)/i", $event->term, $matches)) {
			$user_id = (int)($matches[1]);
			$event->add_querylet(new Querylet("images.id IN (SELECT image_id FROM user_favorites WHERE user_id = $user_id)"));
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
					created_at DATETIME NOT NULL,
					INDEX(image_id),
					UNIQUE(image_id, user_id),
					FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
					FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE
				)
			");
			$config->set_int("ext_favorites_version", 1);
		}

		if($config->get_int("ext_favorites_version") < 2) {
			log_info("favorites", "Cleaning user favourites");
			$database->Execute("DELETE FROM user_favorites WHERE user_id NOT IN (SELECT id FROM users)");
			$database->Execute("DELETE FROM user_favorites WHERE image_id NOT IN (SELECT id FROM images)");

			log_info("favorites", "Adding foreign keys to user favourites");
			$database->Execute("ALTER TABLE user_favorites ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;");
			$database->Execute("ALTER TABLE user_favorites ADD FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE;");
			$config->set_int("ext_favorites_version", 2);
		}
	}

	private function add_vote(/*int*/ $image_id, /*int*/ $user_id, /*bool*/ $do_set) {
		global $database;
		if ($do_set) {
			$database->Execute(
				"INSERT INTO user_favorites(image_id, user_id, created_at) VALUES(:image_id, :user_id, NOW())",
				array("image_id"=>$image_id, "user_id"=>$user_id));
		} else {
			$database->Execute(
				"DELETE FROM user_favorites WHERE image_id = :image_id AND user_id = :user_id",
				array("image_id"=>$image_id, "user_id"=>$user_id));
		}
		$database->Execute(
			"UPDATE images SET favorites=(SELECT COUNT(*) FROM user_favorites WHERE image_id=:image_id) WHERE id=:user_id",
			array("image_id"=>$image_id, "user_id"=>$user_id));
	}
	
	private function list_persons_who_have_favorited(Image $image) {
		global $database;

		return $database->get_col(
				"SELECT name FROM users WHERE id IN (SELECT user_id FROM user_favorites WHERE image_id = :image_id) ORDER BY name",
				array("image_id"=>$image->id));
	}
}
?>
