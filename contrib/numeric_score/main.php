<?php
/*
 * Name: Image Scores (Numeric)
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Allow users to score images
 * Documentation:
 *  Each registered user may vote an image +1 or -1, the
 *  image's score is the sum of all votes.
 */

class NumericScoreSetEvent extends Event {
	var $image_id, $user, $score;

	public function NumericScoreSetEvent($image_id, $user, $score) {
		$this->image_id = $image_id;
		$this->user = $user;
		$this->score = $score;
	}
}

class NumericScore implements Extension {
	var $theme;

	public function get_priority() {return 50;}

	public function receive_event(Event $event) {
		global $config, $database, $page, $user;
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if($event instanceof InitExtEvent) {
			if($config->get_int("ext_numeric_score_version", 0) < 1) {
				$this->install();
			}
		}

		if($event instanceof DisplayingImageEvent) {
			if(!$user->is_anonymous()) {
				$html = $this->theme->get_voter_html($event->image);
				$page->add_block(new Block("Image Score", $html, "left", 20));
			}
		}

		if($event instanceof UserPageBuildingEvent) {
			$html = $this->theme->get_nuller_html($event->display_user);
			$page->add_block(new Block("Votes", $html, "main", 60));
		}

		if($event instanceof PageRequestEvent) {
			if($event->page_matches("numeric_score_votes")) {
				$image_id = int_escape($event->get_arg(0));
				$x = $database->get_all(
					"SELECT users.name as username, user_id, score 
					FROM numeric_score_votes 
					JOIN users ON numeric_score_votes.user_id=users.id
					WHERE image_id=?",
					array($image_id));
				$html = "<table>";
				foreach($x as $vote) {
					$html .= "<tr><td>";
					$html .= "<a href='/user/{$vote['username']}'>{$vote['username']}</a>";
					$html .= "</td><td>";
					$html .= $vote['score'];
					$html .= "</td></tr>";
				}
				die($html);
			}
			if($event->page_matches("numeric_score_vote") && $user->check_auth_token()) {
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
			if($event->page_matches("numeric_score/remove_votes_on") && $user->check_auth_token()) {
				if($user->is_admin()) {
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
			if($event->page_matches("numeric_score/remove_votes_by") && $user->check_auth_token()) {
				if($user->is_admin()) {
					$user_id = int_escape($_POST['user_id']);
					$image_ids = $database->get_col("SELECT image_id FROM numeric_score_votes WHERE user_id=?", array($user_id));

					$database->execute(
							"DELETE FROM numeric_score_votes WHERE user_id=? AND image_id IN ?",
							array($user_id, $image_ids));
					$database->execute(
							"UPDATE images SET numeric_score=(SELECT SUM(score) FROM numeric_score_votes WHERE image_id=images.id) WHERE images.id IN ?",
							array($image_ids));
					$page->set_mode("redirect");
					$page->set_redirect(make_link());
				}
			}
			if($event->page_matches("popular_by_day") || $event->page_matches("popular_by_month") || $event->page_matches("popular_by_year")) {
				$t_images = $config->get_int("index_height") * $config->get_int("index_width");

				//TODO: Add Popular_by_week.

				//year
				if(empty($_GET['year'])){
					$year = date("Y");
				}else{
					$year = $_GET['year'];
				}
				//month
				if(empty($_GET['month']) || int_escape($_GET['month']) > 12){
					$month = date("m");
				}else{
					$month = $_GET['month'];
				}
				//day
				if(empty($_GET['day']) || int_escape($_GET['day']) > 31){
					$day = date("d");
				}else{
					$day = $_GET['day'];
				}
				$totaldate = $year."/".$month."/".$day;

				$sql =
					"SELECT * FROM images
					WHERE EXTRACT(YEAR FROM posted) = :year
					";

				$agrs = array("limit" => $t_images, "year" => $year);

				if($event->page_matches("popular_by_day")){
					$sql .=
						"AND EXTRACT(MONTH FROM posted) = :month
						AND EXTRACT(DAY FROM posted) = :day
						AND NOT numeric_score=0
						";
					//array_push doesn't seem to like using double arrows
					//this requires us to instead create two arrays and merge
					$sgra = array("month" => $month, "day" => $day);
					$args = array_merge($agrs, $sgra);

					$dte = array($totaldate, date("F jS, Y", (strtotime($totaldate))), "\\y\\e\\a\\r\\=Y\\&\\m\\o\\n\\t\\h\\=m\\&\\d\\a\\y\\=d", "day");
				}
				if($event->page_matches("popular_by_month")){
					$sql .=
						"AND EXTRACT(MONTH FROM posted) = :month
						AND NOT numeric_score=0
						";
					$sgra = array("month" => $month);
					$args = array_merge($agrs, $sgra);

					$title = date("F Y", (strtotime($totaldate)));
					$dte = array($totaldate, $title, "\\y\\e\\a\\r\\=Y\\&\\m\\o\\n\\t\\h\\=m", "month");
				}
				if($event->page_matches("popular_by_year")){
					$sql .= "AND NOT numeric_score=0";
					$dte = array($totaldate, $year, "\y\e\a\\r\=Y", "year");
					$args = $agrs;
				}
				$sql .= " ORDER BY numeric_score DESC LIMIT :limit OFFSET 0";

				//filter images by year/score != 0 > limit to max images on one page > order from highest to lowest score
				$result = $database->get_all($sql, $args);

				$images = array();
				foreach($result as $singleResult) {
					$images[] = Image::by_id($singleResult["id"]);
				}
				$this->theme->view_popular($images, $dte);
			}
		}

		if($event instanceof NumericScoreSetEvent) {
			log_info("numeric_score", "Rated Image #{$event->image_id} as {$event->score}");
			$this->add_vote($event->image_id, $user->id, $event->score);
		}

		if($event instanceof ImageDeletionEvent) {
			$database->execute("DELETE FROM numeric_score_votes WHERE image_id=:id", array("id" => $event->image->id));
		}

		if($event instanceof ParseLinkTemplateEvent) {
			$event->replace('$score', $event->image->numeric_score);
		}

		if($event instanceof SearchTermParseEvent) {
			$matches = array();
			if(preg_match("/^score(<|<=|=|>=|>)(\d+)$/", $event->term, $matches)) {
				$cmp = $matches[1];
				$score = $matches[2];
				$event->add_querylet(new Querylet("numeric_score $cmp $score"));
			}
			if(preg_match("/^upvoted_by=(.*)$/", $event->term, $matches)) {
				$duser = User::by_name($matches[1]);
				if(is_null($duser)) {
					throw new SearchTermParseException(
							"Can't find the user named ".html_escape($matches[1]));
				}
				$event->add_querylet(new Querylet(
					"images.id in (SELECT image_id FROM numeric_score_votes WHERE user_id=:ns_user_id AND score=1)",
					array("ns_user_id"=>$duser->id)));
			}
			if(preg_match("/^downvoted_by=(.*)$/", $event->term, $matches)) {
				$duser = User::by_name($matches[1]);
				if(is_null($duser)) {
					throw new SearchTermParseException(
							"Can't find the user named ".html_escape($matches[1]));
				}
				$event->add_querylet(new Querylet(
					"images.id in (SELECT image_id FROM numeric_score_votes WHERE user_id=:ns_user_id AND score=-1)",
					array("ns_user_id"=>$duser->id)));
			}
			if(preg_match("/^upvoted_by_id=(\d+)$/", $event->term, $matches)) {
				$iid = int_escape($matches[1]);
				$event->add_querylet(new Querylet(
					"images.id in (SELECT image_id FROM numeric_score_votes WHERE user_id=:ns_user_id AND score=1)",
					array("ns_user_id"=>$iid)));
			}
			if(preg_match("/^downvoted_by_id=(\d+)$/", $event->term, $matches)) {
				$iid = int_escape($matches[1]);
				$event->add_querylet(new Querylet(
					"images.id in (SELECT image_id FROM numeric_score_votes WHERE user_id=:ns_user_id AND score=-1)",
					array("ns_user_id"=>$iid)));
			}
		}
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
				INDEX(image_id),
				FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
				FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
			");
			$config->set_int("ext_numeric_score_version", 1);
		}
		if($config->get_int("ext_numeric_score_version") < 2) {
			$database->execute("CREATE INDEX numeric_score_votes__user_votes ON numeric_score_votes(user_id, score)");
			$config->set_int("ext_numeric_score_version", 2);
		}
	}

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
			"UPDATE images SET numeric_score=(SELECT SUM(score) FROM numeric_score_votes WHERE image_id=:imageid) WHERE id=:id",
			array("imageid" => $image_id, "id" => $image_id));
	}
}
?>
