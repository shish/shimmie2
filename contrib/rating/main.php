<?php
/*
 * Name: Image Ratings
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Allow users to rate images "safe", "questionable" or "explicit"
 */

class RatingSetEvent extends Event {
	var $image, $user, $rating;

	public function RatingSetEvent(Image $image, User $user, $rating) {
		assert(in_array($rating, array("s", "q", "e", "u")));
		$this->image = $image;
		$this->user = $user;
		$this->rating = $rating;
	}
}

class Ratings implements Extension {
	var $theme;

	public function get_priority() {return 50;}

	public function receive_event(Event $event) {
		global $config, $database, $page, $user;
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if($event instanceof AdminBuildingEvent) {
			$this->theme->display_bulk_rater();
		}

		if(($event instanceof PageRequestEvent) && $event->page_matches("admin/bulk_rate")) {
			global $database, $user, $page;
			if(!$user->is_admin()) {
				throw PermissionDeniedException();
			}
			else {
				$n = 0;
				while(true) {
					$images = Image::find_images($n, 100, Tag::explode($_POST["query"]));
					if(count($images) == 0) break;
					foreach($images as $image) {
						send_event(new RatingSetEvent($image, $user, $_POST['rating']));
					}
					$n += 100;
				}
				#$database->execute("
				#	update images set rating=? where images.id in (
				#		select image_id from image_tags join tags
				#		on image_tags.tag_id = tags.id where tags.tag = ?);
				#	", array($_POST["rating"], $_POST["tag"]));
				$page->set_mode("redirect");
				$page->set_redirect(make_link("admin"));
			}
		}

		if($event instanceof InitExtEvent) {
			if($config->get_int("ext_ratings2_version") < 2) {
				$this->install();
			}

			$config->set_default_string("ext_rating_anon_privs", 'squ');
			$config->set_default_string("ext_rating_user_privs", 'sqeu');
			$config->set_default_string("ext_rating_admin_privs", 'sqeu');
		}

		if($event instanceof RatingSetEvent) {
			if(empty($event->image->rating)){
				$old_rating = "";
			}else{
				$old_rating = $event->image->rating;
			}
			$this->set_rating($event->image->id, $event->rating, $old_rating);
		}

		if($event instanceof ImageInfoBoxBuildingEvent) {
			if($this->can_rate()) {
				$event->add_part($this->theme->get_rater_html($event->image->id, $event->image->rating), 80);
			}
		}

		if($event instanceof ImageInfoSetEvent) {
			if($this->can_rate() && isset($_POST["rating"])) {
				send_event(new RatingSetEvent($event->image, $user, $_POST['rating']));
			}
		}

		if($event instanceof SetupBuildingEvent) {
			$privs = array();
			$privs['Safe Only'] = 's';
			$privs['Safe and Unknown'] = 'su';
			$privs['Safe and Questionable'] = 'sq';
			$privs['Safe, Questionable, Unknown'] = 'squ';
			$privs['All'] = 'sqeu';

			$sb = new SetupBlock("Image Ratings");
			$sb->add_choice_option("ext_rating_anon_privs", $privs, "Anonymous: ");
			$sb->add_choice_option("ext_rating_user_privs", $privs, "<br>Users: ");
			$sb->add_choice_option("ext_rating_admin_privs", $privs, "<br>Admins: ");
			$event->panel->add_block($sb);
		}

		if($event instanceof ParseLinkTemplateEvent) {
			$event->replace('$rating', $this->theme->rating_to_name($event->image->rating));
		}

		if($event instanceof SearchTermParseEvent) {
			$matches = array();
			if(is_null($event->term) && $this->no_rating_query($event->context)) {
				$set = Ratings::privs_to_sql(Ratings::get_user_privs($user));
				$event->add_querylet(new Querylet("rating IN ($set)"));
			}
			if(preg_match("/^rating=([sqeu]+)$/", $event->term, $matches)) {
				$sqes = $matches[1];
				$arr = array();
				$length = strlen($sqes);
				for($i=0; $i<$length; $i++) {
					$arr[] = "'" . $sqes[$i] . "'";
				}
				$set = join(', ', $arr);
				$event->add_querylet(new Querylet("rating IN ($set)"));
			}
			if(preg_match("/^rating=(safe|questionable|explicit|unknown)$/", strtolower($event->term), $matches)) {
				$text = $matches[1];
				$char = $text[0];
				$event->add_querylet(new Querylet("rating = :img_rating", array("img_rating"=>$char)));
			}
		}
		
		if($event instanceof DisplayingImageEvent) {
			/**
			 * Deny images upon insufficient permissions.
			 **/
			global $user, $database, $page;
			$user_view_level = Ratings::get_user_privs($user);
			$user_view_level = preg_split('//', $user_view_level, -1);
			if(!in_array($event->image->rating, $user_view_level)) {
				$page->set_mode("redirect");
				$page->set_redirect(make_link("post/list"));
			}
		}
	}

	public static function get_user_privs($user) {
		global $config;
		if($user->is_anonymous()) {
			$sqes = $config->get_string("ext_rating_anon_privs");
		}
		else if($user->is_admin()) {
			$sqes = $config->get_string("ext_rating_admin_privs");
		}
		else {
			$sqes = $config->get_string("ext_rating_user_privs");
		}
		return $sqes;
	}

	public static function privs_to_sql($sqes) {
		$arr = array();
		$length = strlen($sqes);
		for($i=0; $i<$length; $i++) {
			$arr[] = "'" . $sqes[$i] . "'";
		}
		$set = join(', ', $arr);
		return $set;
	}

	public static function rating_to_human($rating) {
		switch($rating) {
			case "s": return "Safe";
			case "q": return "Questionable";
			case "e": return "Explicit";
			default:  return "Unknown";
		}
	}

	// FIXME: this is a bit ugly and guessey, should have proper options
	private function can_rate() {
		global $config, $user;
		if($user->is_anonymous() && $config->get_string("ext_rating_anon_privs") == "sqeu") return false;
		if($user->is_admin()) return true;
		if(!$user->is_anonymous() && $config->get_string("ext_rating_user_privs") == "sqeu") return true;
		return false;
	}

	private function no_rating_query($context) {
		foreach($context as $term) {
			if(preg_match("/^rating=/", $term)) {
				return false;
			}
		}
		return true;
	}

	private function install() {
		global $database;
		global $config;

		if($config->get_int("ext_ratings2_version") < 1) {
			$database->Execute("ALTER TABLE images ADD COLUMN rating CHAR(1) NOT NULL DEFAULT 'u'");
			$database->Execute("CREATE INDEX images__rating ON images(rating)");
			$config->set_int("ext_ratings2_version", 3);
		}

		if($config->get_int("ext_ratings2_version") < 2) {
			$database->Execute("CREATE INDEX images__rating ON images(rating)");
			$config->set_int("ext_ratings2_version", 2);
		}

		if($config->get_int("ext_ratings2_version") < 3) {
			$database->Execute("ALTER TABLE images CHANGE rating rating CHAR(1) NOT NULL DEFAULT 'u'");
			$config->set_int("ext_ratings2_version", 3);
		}
	}

	private function set_rating($image_id, $rating, $old_rating) {
		global $database;
		if($old_rating != $rating){
			$database->Execute("UPDATE images SET rating=? WHERE id=?", array($rating, $image_id));
			log_info("rating", "Rating for Image #{$image_id} set to: ".$this->theme->rating_to_name($rating));
		}
	}
}
?>
