<?php
/*
 * Name: Image Ratings
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Allow users to rate images "safe", "questionable" or "explicit"
 * Documentation:
 *  This shimmie extension provides filter:
 *  <ul>
 *    <li>rating = (safe|questionable|explicit|unknown)
 *      <ul>
 *        <li>rating=s -- safe images
 *        <li>rating=q -- questionable images
 *        <li>rating=e -- explicit images
 *        <li>rating=u -- Unknown rating
 *        <li>rating=sq -- safe and questionable images
 *      </ul>
 *  </ul>
 */

class RatingSetEvent extends Event {
	/** @var \Image */
	public $image;
	/** @var string  */
	public $rating;

	/**
	 * @param Image $image
	 * @param string $rating
	 */
	public function __construct(Image $image, /*char*/ $rating) {
		assert(in_array($rating, array("s", "q", "e", "u")));

		$this->image = $image;
		$this->rating = $rating;
	}
}

class Ratings extends Extension {
	protected $db_support = ['mysql'];  // ?

	/**
	 * @return int
	 */
	public function get_priority() {return 50;}

	public function onInitExt(InitExtEvent $event) {
		global $config;
		
		if($config->get_int("ext_ratings2_version") < 2) {
			$this->install();
		}

		$config->set_default_string("ext_rating_anon_privs", 'squ');
		$config->set_default_string("ext_rating_user_privs", 'sqeu');
		$config->set_default_string("ext_rating_admin_privs", 'sqeu');
	}
	
	public function onSetupBuilding(SetupBuildingEvent $event) {
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
	
	public function onPostListBuilding(PostListBuildingEvent $event) {
		global $user;
		if($user->is_admin() && !empty($event->search_terms)) {
			$this->theme->display_bulk_rater(implode(" ", $event->search_terms));
		}
	}

	
	public function onDisplayingImage(DisplayingImageEvent $event) {
		global $user, $page;
		/**
		 * Deny images upon insufficient permissions.
		 **/
		$user_view_level = Ratings::get_user_privs($user);
		$user_view_level = preg_split('//', $user_view_level, -1);
		if(!in_array($event->image->rating, $user_view_level)) {
			$page->set_mode("redirect");
			$page->set_redirect(make_link("post/list"));
		}
	}
	
	public function onRatingSet(RatingSetEvent $event) {
		if(empty($event->image->rating)){
			$old_rating = "";
		}else{
			$old_rating = $event->image->rating;
		}
		$this->set_rating($event->image->id, $event->rating, $old_rating);
	}
	
	public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event) {
		if($this->can_rate()) {
			$event->add_part($this->theme->get_rater_html($event->image->id, $event->image->rating), 80);
		}
	}
	
	public function onImageInfoSet(ImageInfoSetEvent $event) {
		if($this->can_rate() && isset($_POST["rating"])) {
			send_event(new RatingSetEvent($event->image, $_POST['rating']));
		}
	}

	public function onParseLinkTemplate(ParseLinkTemplateEvent $event) {
		$event->replace('$rating', $this->rating_to_human($event->image->rating));
	}

	public function onSearchTermParse(SearchTermParseEvent $event) {
		global $user;
		
		$matches = array();
		if(is_null($event->term) && $this->no_rating_query($event->context)) {
			$set = Ratings::privs_to_sql(Ratings::get_user_privs($user));
			$event->add_querylet(new Querylet("rating IN ($set)"));
		}
		if(preg_match("/^rating[=|:](?:([sqeu]+)|(safe|questionable|explicit|unknown))$/D", strtolower($event->term), $matches)) {
			$ratings = $matches[1] ? $matches[1] : $matches[2][0];
			$ratings = array_intersect(str_split($ratings), str_split(Ratings::get_user_privs($user)));
			$set = "'" . join("', '", $ratings) . "'";
			$event->add_querylet(new Querylet("rating IN ($set)"));
		}
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $user, $page;
		
		if ($event->page_matches("admin/bulk_rate")) {
			if(!$user->is_admin()) {
				throw new PermissionDeniedException();
			}
			else {
				$n = 0;
				while(true) {
					$images = Image::find_images($n, 100, Tag::explode($_POST["query"]));
					if(count($images) == 0) break;
					
					reset($images); // rewind to first element in array.
					
					foreach($images as $image) {
						send_event(new RatingSetEvent($image, $_POST['rating']));
					}
					$n += 100;
				}
				#$database->execute("
				#	update images set rating=? where images.id in (
				#		select image_id from image_tags join tags
				#		on image_tags.tag_id = tags.id where tags.tag = ?);
				#	", array($_POST["rating"], $_POST["tag"]));
				$page->set_mode("redirect");
				$page->set_redirect(make_link("post/list"));
			}
		}
	}

	/**
	 * @param \User $user
	 * @return string
	 */
	public static function get_user_privs(User $user) {
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

	/**
	 * @param string $sqes
	 * @return string
	 */
	public static function privs_to_sql(/*string*/ $sqes) {
		$arr = array();
		$length = strlen($sqes);
		for($i=0; $i<$length; $i++) {
			$arr[] = "'" . $sqes[$i] . "'";
		}
		$set = join(', ', $arr);
		return $set;
	}

	/**
	 * @param string $rating
	 * @return string
	 */
	public static function rating_to_human(/*string*/ $rating) {
		switch($rating) {
			case "s": return "Safe";
			case "q": return "Questionable";
			case "e": return "Explicit";
			default:  return "Unknown";
		}
	}

	/**
	 * FIXME: this is a bit ugly and guessey, should have proper options
	 *
	 * @return bool
	 */
	private function can_rate() {
		global $config, $user;
		if($user->is_anonymous() && $config->get_string("ext_rating_anon_privs") == "sqeu") return false;
		if($user->is_admin()) return true;
		if(!$user->is_anonymous() && $config->get_string("ext_rating_user_privs") == "sqeu") return true;
		return false;
	}

	/**
	 * @param $context
	 * @return bool
	 */
	private function no_rating_query($context) {
		foreach($context as $term) {
			if(preg_match("/^rating[=|:]/", $term)) {
				return false;
			}
		}
		return true;
	}

	private function install() {
		global $database, $config;

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

	/**
	 * @param int $image_id
	 * @param string $rating
	 * @param string $old_rating
	 */
	private function set_rating(/*int*/ $image_id, /*string*/ $rating, /*string*/ $old_rating) {
		global $database;
		if($old_rating != $rating){
			$database->Execute("UPDATE images SET rating=? WHERE id=?", array($rating, $image_id));
			log_info("rating", "Rating for Image #{$image_id} set to: ".$this->rating_to_human($rating));
		}
	}
}

