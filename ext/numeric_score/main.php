<?php
/*
 * Name: Image Scores (Numeric)
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Allow users to score images
 * Documentation:
 *  Each registered user may vote an image +1 or -1, the
 *  image's score is the sum of all votes.
 */

class NumericScoreSetEvent extends Event {
	var $image_id, $user, $score;

	/**
	 * @param int $image_id
	 * @param User $user
	 * @param int $score
	 */
	public function __construct($image_id, User $user, $score) {
		$this->image_id = $image_id;
		$this->user = $user;
		$this->score = $score;
	}
}

class NumericScore extends Extension {
	public function onInitExt(InitExtEvent $event) {
		global $config;
		if($config->get_int("ext_numeric_score_version", 0) < 1) {
			$this->install();
		}
	}

	public function onDisplayingImage(DisplayingImageEvent $event) {
		global $user;
		if(!$user->is_anonymous()) {
			$this->theme->get_voter($event->image);
		}
	}

	public function onUserPageBuilding(UserPageBuildingEvent $event) {
		global $user;
		if($user->can("edit_other_vote")) {
			$this->theme->get_nuller($event->display_user);
		}
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $config, $database, $user, $page;

		if($event->page_matches("numeric_score_votes")) {
			$image_id = int_escape($event->get_arg(0));
			$x = $database->get_all(
				"SELECT users.name as username, user_id, score 
				FROM numeric_score_votes 
				JOIN users ON numeric_score_votes.user_id=users.id
				WHERE image_id=?",
				array($image_id));
			$html = "<table style='width: 100%;'>";
			foreach($x as $vote) {
				$html .= "<tr><td>";
				$html .= "<a href='".make_link("user/{$vote['username']}")."'>{$vote['username']}</a>";
				$html .= "</td><td width='10'>";
				$html .= $vote['score'];
				$html .= "</td></tr>";
			}
			die($html);
		}
		else if($event->page_matches("numeric_score_vote") && $user->check_auth_token()) {
			if(!$user->is_anonymous()) {
				$image_id = int_escape($_POST['image_id']);
				$char = $_POST['vote'];
				$score = null;
				if($char == "up") $score = 1;
				else if($char == "null") $score = 0;
				else if($char == "down") $score = -1;
				if(!is_null($score) && $image_id>0) send_event(new NumericScoreSetEvent($image_id, $user, $score));
				$page->set_mode("redirect");
				$page->set_redirect(make_link("post/view/$image_id"));
			}
		}
		else if($event->page_matches("numeric_score/remove_votes_on") && $user->check_auth_token()) {
			if($user->can("edit_other_vote")) {
				$image_id = int_escape($_POST['image_id']);
				$database->execute(
						"DELETE FROM numeric_score_votes WHERE image_id=?",
						array($image_id));
				$database->execute(
						"UPDATE images SET numeric_score=0 WHERE id=?",
						array($image_id));
				$page->set_mode("redirect");
				$page->set_redirect(make_link("post/view/$image_id"));
			}
		}
		else if($event->page_matches("numeric_score/remove_votes_by") && $user->check_auth_token()) {
			if($user->can("edit_other_vote")) {
				$this->delete_votes_by(int_escape($_POST['user_id']));
				$page->set_mode("redirect");
				$page->set_redirect(make_link());
			}
		}
		else if($event->page_matches("popular_by_day") || $event->page_matches("popular_by_month") || $event->page_matches("popular_by_year")) {
			//FIXME: popular_by isn't linked from anywhere
			list($day, $month, $year) = array(date("d"), date("m"), date("Y"));

			if(!empty($_GET['day'])){
				$D = (int) $_GET['day'];
				$day = clamp($D, 1, 31);
			}
			if(!empty($_GET['month'])){
				$M = (int) $_GET['month'];
				$month = clamp($M, 1 ,12);
			}
			if(!empty($_GET['year'])){
				$Y = (int) $_GET['year'];
				$year = clamp($Y, 1970, 2100);
			}

			$totaldate = $year."/".$month."/".$day;

			$sql = "SELECT id FROM images
			        WHERE EXTRACT(YEAR FROM posted) = :year
					";
			$args = array("limit" => $config->get_int("index_images"), "year" => $year);

			if($event->page_matches("popular_by_day")){
				$sql .=
					"AND EXTRACT(MONTH FROM posted) = :month
					AND EXTRACT(DAY FROM posted) = :day";

				$args = array_merge($args, array("month" => $month, "day" => $day));
				$dte = array($totaldate, date("F jS, Y", (strtotime($totaldate))), "\\y\\e\\a\\r\\=Y\\&\\m\\o\\n\\t\\h\\=m\\&\\d\\a\\y\\=d", "day");
			}
			else if($event->page_matches("popular_by_month")){
				$sql .=	"AND EXTRACT(MONTH FROM posted) = :month";

				$args = array_merge($args, array("month" => $month));
				$dte = array($totaldate, date("F Y", (strtotime($totaldate))), "\\y\\e\\a\\r\\=Y\\&\\m\\o\\n\\t\\h\\=m", "month");
			}
			else if($event->page_matches("popular_by_year")){
				$dte = array($totaldate, $year, "\\y\\e\\a\\r\=Y", "year");
			}
			else {
				// this should never happen due to the fact that the page event is already matched against earlier.
				throw new UnexpectedValueException("Error: Invalid page event.");
			}
			$sql .= " AND NOT numeric_score=0 ORDER BY numeric_score DESC LIMIT :limit OFFSET 0";

			//filter images by score != 0 + date > limit to max images on one page > order from highest to lowest score

			$result = $database->get_col($sql, $args);
			$images = array();
			foreach($result as $id) { $images[] = Image::by_id($id); }

			$this->theme->view_popular($images, $dte);
		}
	}

	public function onNumericScoreSet(NumericScoreSetEvent $event) {
		global $user;
		log_debug("numeric_score", "Rated Image #{$event->image_id} as {$event->score}", true, array("image_id"=>$event->image_id));
		$this->add_vote($event->image_id, $user->id, $event->score);
	}

	public function onImageDeletion(ImageDeletionEvent $event) {
		global $database;
		$database->execute("DELETE FROM numeric_score_votes WHERE image_id=:id", array("id" => $event->image->id));
	}

	public function onUserDeletion(UserDeletionEvent $event) {
		$this->delete_votes_by($event->id);
	}

	/**
	 * @param int $user_id
	 */
	public function delete_votes_by($user_id) {
		global $database;

		$image_ids = $database->get_col("SELECT image_id FROM numeric_score_votes WHERE user_id=?", array($user_id));

		if(count($image_ids) == 0) return;

		// vote recounting is pretty heavy, and often hits statement timeouts
		// if you try to recount all the images in one go
		foreach(array_chunk($image_ids, 20) as $chunk) {
			$id_list = implode(",", $chunk);
			$database->execute(
				"DELETE FROM numeric_score_votes WHERE user_id=? AND image_id IN (".$id_list.")",
				array($user_id));
			$database->execute("
				UPDATE images
				SET numeric_score=COALESCE(
					(
						SELECT SUM(score)
						FROM numeric_score_votes
						WHERE image_id=images.id
					),
					0
				)
				WHERE images.id IN (".$id_list.")");
		}
	}

	public function onParseLinkTemplate(ParseLinkTemplateEvent $event) {
		$event->replace('$score', $event->image->numeric_score);
	}

	public function onSearchTermParse(SearchTermParseEvent $event) {
		$matches = array();
		if(preg_match("/^score([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(-?\d+)$/i", $event->term, $matches)) {
			$cmp = ltrim($matches[1], ":") ?: "=";
			$score = $matches[2];
			$event->add_querylet(new Querylet("numeric_score $cmp $score"));
		}
		else if(preg_match("/^upvoted_by[=|:](.*)$/i", $event->term, $matches)) {
			$duser = User::by_name($matches[1]);
			if(is_null($duser)) {
				throw new SearchTermParseException(
						"Can't find the user named ".html_escape($matches[1]));
			}
			$event->add_querylet(new Querylet(
				"images.id in (SELECT image_id FROM numeric_score_votes WHERE user_id=:ns_user_id AND score=1)",
				array("ns_user_id"=>$duser->id)));
		}
		else if(preg_match("/^downvoted_by[=|:](.*)$/i", $event->term, $matches)) {
			$duser = User::by_name($matches[1]);
			if(is_null($duser)) {
				throw new SearchTermParseException(
						"Can't find the user named ".html_escape($matches[1]));
			}
			$event->add_querylet(new Querylet(
				"images.id in (SELECT image_id FROM numeric_score_votes WHERE user_id=:ns_user_id AND score=-1)",
				array("ns_user_id"=>$duser->id)));
		}
		else if(preg_match("/^upvoted_by_id[=|:](\d+)$/i", $event->term, $matches)) {
			$iid = int_escape($matches[1]);
			$event->add_querylet(new Querylet(
				"images.id in (SELECT image_id FROM numeric_score_votes WHERE user_id=:ns_user_id AND score=1)",
				array("ns_user_id"=>$iid)));
		}
		else if(preg_match("/^downvoted_by_id[=|:](\d+)$/i", $event->term, $matches)) {
			$iid = int_escape($matches[1]);
			$event->add_querylet(new Querylet(
				"images.id in (SELECT image_id FROM numeric_score_votes WHERE user_id=:ns_user_id AND score=-1)",
				array("ns_user_id"=>$iid)));
		}
		else if(preg_match("/^order[=|:](numeric_)?(score)[_]?(desc|asc)?$/i", $event->term, $matches)){
			$default_order_for_column = "DESC";
			$sort = isset($matches[3]) ? strtoupper($matches[3]) : $default_order_for_column;
			Image::$order_sql = "images.numeric_score $sort";
			$event->add_querylet(new Querylet("1=1")); //small hack to avoid metatag being treated as normal tag
		}
	}

	public function onTagTermParse(TagTermParseEvent $event) {
		$matches = array();

		if(preg_match("/^vote[=|:](up|down|remove)$/", $event->term, $matches)) {
			global $user;
			$score = ($matches[1] == "up" ? 1 : ($matches[1] == "down" ? -1 : 0));
			if(!$user->is_anonymous()) {
				send_event(new NumericScoreSetEvent($event->id, $user, $score));
			}
		}

		if(!empty($matches)) $event->metatag = true;
	}

	private function install() {
		global $database;
		global $config;

		if($config->get_int("ext_numeric_score_version") < 1) {
			$database->execute("ALTER TABLE images ADD COLUMN numeric_score INTEGER NOT NULL DEFAULT 0");
			$database->execute("CREATE INDEX images__numeric_score ON images(numeric_score)");
			$database->create_table("numeric_score_votes", "
				image_id INTEGER NOT NULL,
				user_id INTEGER NOT NULL,
				score INTEGER NOT NULL,
				UNIQUE(image_id, user_id),
				FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
				FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
			");
			$database->execute("CREATE INDEX numeric_score_votes_image_id_idx ON numeric_score_votes(image_id)", array());
			$config->set_int("ext_numeric_score_version", 1);
		}
		if($config->get_int("ext_numeric_score_version") < 2) {
			$database->execute("CREATE INDEX numeric_score_votes__user_votes ON numeric_score_votes(user_id, score)");
			$config->set_int("ext_numeric_score_version", 2);
		}
	}

	/**
	 * @param int $image_id
	 * @param int $user_id
	 * @param int $score
	 */
	private function add_vote($image_id, $user_id, $score) {
		global $database;
		$database->execute(
			"DELETE FROM numeric_score_votes WHERE image_id=:imageid AND user_id=:userid",
			array("imageid" => $image_id, "userid" => $user_id));
		if($score != 0) {
			$database->execute(
				"INSERT INTO numeric_score_votes(image_id, user_id, score) VALUES(:imageid, :userid, :score)",
				array("imageid" => $image_id, "userid" => $user_id, "score" => $score));
		}
		$database->Execute(
			"UPDATE images SET numeric_score=(
				COALESCE(
					(SELECT SUM(score) FROM numeric_score_votes WHERE image_id=:imageid),
					0
				)
			) WHERE id=:id",
			array("imageid" => $image_id, "id" => $image_id));
	}
}

