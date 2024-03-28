<?php

declare(strict_types=1);

namespace Shimmie2;

use GQLA\Type;
use GQLA\Field;
use GQLA\Mutation;

#[Type(name: "NumericScoreVote")]
class NumericScoreVote
{
    public int $image_id;
    public int $user_id;

    #[Field]
    public int $score;

    #[Field]
    public function post(): Image
    {
        return Image::by_id_ex($this->image_id);
    }

    #[Field]
    public function user(): User
    {
        return User::by_id($this->user_id);
    }

    #[Field(extends: "Post")]
    public static function score(Image $post): int
    {
        global $database;
        if ($post['score'] ?? null) {
            return $post['score'];
        }
        return $database->get_one(
            "SELECT sum(score) FROM numeric_score_votes WHERE image_id=:image_id",
            ['image_id' => $post->id]
        ) ?? 0;
    }

    /**
     * @return NumericScoreVote[]
     */
    #[Field(extends: "Post", type: "[NumericScoreVote!]!")]
    public static function votes(Image $post): array
    {
        global $database;
        $rows = $database->get_all(
            "SELECT * FROM numeric_score_votes WHERE image_id=:image_id",
            ['image_id' => $post->id]
        );
        $votes = [];
        foreach ($rows as $row) {
            $nsv = new NumericScoreVote();
            $nsv->image_id = $row["image_id"];
            $nsv->user_id = $row["user_id"];
            $nsv->score = $row["score"];
            $votes[] = $nsv;
        }
        return $votes;
    }

    #[Field(extends: "Post", type: "Int!")]
    public static function my_vote(Image $post): int
    {
        global $database, $user;
        return $database->get_one(
            "SELECT score FROM numeric_score_votes WHERE image_id=:image_id AND user_id=:user_id",
            ['image_id' => $post->id, "user_id" => $user->id]
        ) ?? 0;
    }

    #[Mutation]
    public static function create_vote(int $post_id, int $score): bool
    {
        global $user;
        if ($user->can(Permissions::CREATE_VOTE)) {
            assert($score == 0 || $score == -1 || $score == 1);
            send_event(new NumericScoreSetEvent($post_id, $user, $score));
            return true;
        }
        return false;
    }
}

class NumericScoreSetEvent extends Event
{
    public int $image_id;
    public User $user;
    public int $score;

    public function __construct(int $image_id, User $user, int $score)
    {
        parent::__construct();
        $this->image_id = $image_id;
        $this->user = $user;
        $this->score = $score;
    }
}

class NumericScore extends Extension
{
    /** @var NumericScoreTheme */
    protected Themelet $theme;

    public function onInitExt(InitExtEvent $event): void
    {
        Image::$prop_types["numeric_score"] = ImagePropType::INT;
    }

    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::CREATE_VOTE)) {
            $this->theme->get_voter($event->image);
        }
    }

    public function onUserPageBuilding(UserPageBuildingEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::EDIT_OTHER_VOTE)) {
            $this->theme->get_nuller($event->display_user);
        }

        $n_up = Search::count_images(["upvoted_by={$event->display_user->name}"]);
        $link_up = search_link(["upvoted_by={$event->display_user->name}"]);
        $n_down = Search::count_images(["downvoted_by={$event->display_user->name}"]);
        $link_down = search_link(["downvoted_by={$event->display_user->name}"]);
        $event->add_part("<a href='$link_up'>$n_up Upvotes</a> / <a href='$link_down'>$n_down Downvotes</a>");
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $database, $user, $page;

        if ($event->page_matches("numeric_score_votes/{image_id}")) {
            $image_id = $event->get_iarg('image_id');
            $x = $database->get_all(
                "SELECT users.name as username, user_id, score
				FROM numeric_score_votes
				JOIN users ON numeric_score_votes.user_id=users.id
				WHERE image_id=:image_id",
                ['image_id' => $image_id]
            );
            $html = "<table style='width: 100%;'>";
            foreach ($x as $vote) {
                $html .= "<tr><td>";
                $html .= "<a href='".make_link("user/{$vote['username']}")."'>{$vote['username']}</a>";
                $html .= "</td><td width='10'>";
                $html .= $vote['score'];
                $html .= "</td></tr>";
            }
            die($html);
        } elseif ($event->page_matches("numeric_score_vote", method: "POST", permission: Permissions::CREATE_VOTE)) {
            $image_id = int_escape($event->req_POST("image_id"));
            $score = int_escape($event->req_POST("vote"));
            if (($score == -1 || $score == 0 || $score == 1) && $image_id > 0) {
                send_event(new NumericScoreSetEvent($image_id, $user, $score));
            }
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/$image_id"));
        } elseif ($event->page_matches("numeric_score/remove_votes_on", method: "POST", permission: Permissions::EDIT_OTHER_VOTE)) {
            $image_id = int_escape($event->req_POST("image_id"));
            $database->execute(
                "DELETE FROM numeric_score_votes WHERE image_id=:image_id",
                ['image_id' => $image_id]
            );
            $database->execute(
                "UPDATE images SET numeric_score=0 WHERE id=:id",
                ['id' => $image_id]
            );
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/$image_id"));
        } elseif ($event->page_matches("numeric_score/remove_votes_by", method: "POST", permission: Permissions::EDIT_OTHER_VOTE)) {
            $this->delete_votes_by(int_escape($event->req_POST('user_id')));
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link());
        } elseif ($event->page_matches("popular_by_day") || $event->page_matches("popular_by_month") || $event->page_matches("popular_by_year")) {
            //FIXME: popular_by isn't linked from anywhere
            list($day, $month, $year) = [date("d"), date("m"), date("Y")];

            if ($event->get_GET('day')) {
                $D = (int) $event->get_GET('day');
                $day = clamp($D, 1, 31);
            }
            if ($event->get_GET('month')) {
                $M = (int) $event->get_GET('month');
                $month = clamp($M, 1, 12);
            }
            if ($event->get_GET('year')) {
                $Y = (int) $event->get_GET('year');
                $year = clamp($Y, 1970, 2100);
            }

            $totaldate = $year."/".$month."/".$day;

            $sql = "SELECT id FROM images WHERE EXTRACT(YEAR FROM posted) = :year";
            $args = ["limit" => $config->get_int(IndexConfig::IMAGES), "year" => $year];

            if ($event->page_matches("popular_by_day")) {
                $sql .= " AND EXTRACT(MONTH FROM posted) = :month AND EXTRACT(DAY FROM posted) = :day";
                $args = array_merge($args, ["month" => $month, "day" => $day]);
                $current = date("F jS, Y", \Safe\strtotime($totaldate)).
                $name = "day";
                $fmt = "\\y\\e\\a\\r\\=Y\\&\\m\\o\\n\\t\\h\\=m\\&\\d\\a\\y\\=d";
            } elseif ($event->page_matches("popular_by_month")) {
                $sql .=	" AND EXTRACT(MONTH FROM posted) = :month";
                $args = array_merge($args, ["month" => $month]);
                $current = date("F Y", \Safe\strtotime($totaldate));
                $name = "month";
                $fmt = "\\y\\e\\a\\r\\=Y\\&\\m\\o\\n\\t\\h\\=m";
            } elseif ($event->page_matches("popular_by_year")) {
                $current = "$year";
                $name = "year";
                $fmt = "\\y\\e\\a\\r\=Y";
            } else {
                // this should never happen due to the fact that the page event is already matched against earlier.
                throw new \UnexpectedValueException("Error: Invalid page event.");
            }
            $sql .= " AND NOT numeric_score=0 ORDER BY numeric_score DESC LIMIT :limit OFFSET 0";

            //filter images by score != 0 + date > limit to max images on one page > order from highest to lowest score

            $ids = $database->get_col($sql, $args);
            $images = Search::get_images($ids);
            $this->theme->view_popular($images, $totaldate, $current, $name, $fmt);
        }
    }

    public function onNumericScoreSet(NumericScoreSetEvent $event): void
    {
        global $user;
        log_debug("numeric_score", "Rated >>{$event->image_id} as {$event->score}", "Rated Post");
        $this->add_vote($event->image_id, $user->id, $event->score);
    }

    public function onImageDeletion(ImageDeletionEvent $event): void
    {
        global $database;
        $database->execute("DELETE FROM numeric_score_votes WHERE image_id=:id", ["id" => $event->image->id]);
    }

    public function onUserDeletion(UserDeletionEvent $event): void
    {
        $this->delete_votes_by($event->id);
    }

    public function delete_votes_by(int $user_id): void
    {
        global $database;

        $image_ids = $database->get_col("SELECT image_id FROM numeric_score_votes WHERE user_id=:user_id", ['user_id' => $user_id]);

        if (count($image_ids) == 0) {
            return;
        }

        // vote recounting is pretty heavy, and often hits statement timeouts
        // if you try to recount all the images in one go
        foreach (array_chunk($image_ids, 20) as $chunk) {
            $id_list = implode(",", $chunk);
            $database->execute(
                "DELETE FROM numeric_score_votes WHERE user_id=:user_id AND image_id IN (".$id_list.")",
                ['user_id' => $user_id]
            );
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

    public function onParseLinkTemplate(ParseLinkTemplateEvent $event): void
    {
        $event->replace('$score', (string)$event->image['numeric_score']);
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        if ($event->key === HelpPages::SEARCH) {
            $block = new Block();
            $block->header = "Numeric Score";
            $block->body = $this->theme->get_help_html();
            $event->add_block($block);
        }
    }

    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        if (is_null($event->term)) {
            return;
        }

        $matches = [];
        if (preg_match("/^score([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(-?\d+)$/i", $event->term, $matches)) {
            $cmp = ltrim($matches[1], ":") ?: "=";
            $score = $matches[2];
            $event->add_querylet(new Querylet("numeric_score $cmp $score"));
        } elseif (preg_match("/^upvoted_by[=|:](.*)$/i", $event->term, $matches)) {
            $duser = User::by_name($matches[1]);
            if (is_null($duser)) {
                throw new SearchTermParseException(
                    "Can't find the user named ".html_escape($matches[1])
                );
            }
            $event->add_querylet(new Querylet(
                "images.id in (SELECT image_id FROM numeric_score_votes WHERE user_id=:ns_user_id AND score=1)",
                ["ns_user_id" => $duser->id]
            ));
        } elseif (preg_match("/^downvoted_by[=|:](.*)$/i", $event->term, $matches)) {
            $duser = User::by_name($matches[1]);
            if (is_null($duser)) {
                throw new SearchTermParseException(
                    "Can't find the user named ".html_escape($matches[1])
                );
            }
            $event->add_querylet(new Querylet(
                "images.id in (SELECT image_id FROM numeric_score_votes WHERE user_id=:ns_user_id AND score=-1)",
                ["ns_user_id" => $duser->id]
            ));
        } elseif (preg_match("/^upvoted_by_id[=|:](\d+)$/i", $event->term, $matches)) {
            $iid = int_escape($matches[1]);
            $event->add_querylet(new Querylet(
                "images.id in (SELECT image_id FROM numeric_score_votes WHERE user_id=:ns_user_id AND score=1)",
                ["ns_user_id" => $iid]
            ));
        } elseif (preg_match("/^downvoted_by_id[=|:](\d+)$/i", $event->term, $matches)) {
            $iid = int_escape($matches[1]);
            $event->add_querylet(new Querylet(
                "images.id in (SELECT image_id FROM numeric_score_votes WHERE user_id=:ns_user_id AND score=-1)",
                ["ns_user_id" => $iid]
            ));
        } elseif (preg_match("/^order[=|:](?:numeric_)?(score)(?:_(desc|asc))?$/i", $event->term, $matches)) {
            $default_order_for_column = "DESC";
            $sort = isset($matches[2]) ? strtoupper($matches[2]) : $default_order_for_column;
            $event->order = "images.numeric_score $sort";
        }
    }

    public function onTagTermCheck(TagTermCheckEvent $event): void
    {
        if (preg_match("/^vote[=|:](up|down|remove)$/i", $event->term)) {
            $event->metatag = true;
        }
    }

    public function onTagTermParse(TagTermParseEvent $event): void
    {
        $matches = [];

        if (preg_match("/^vote[=|:](up|down|remove)$/", $event->term, $matches)) {
            global $user;
            $score = ($matches[1] == "up" ? 1 : ($matches[1] == "down" ? -1 : 0));
            if ($user->can(Permissions::CREATE_VOTE)) {
                send_event(new NumericScoreSetEvent($event->image_id, $user, $score));
            }
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent == "posts") {
            $event->add_nav_link("numeric_score_day", new Link('popular_by_day'), "Popular by Day");
            $event->add_nav_link("numeric_score_month", new Link('popular_by_month'), "Popular by Month");
            $event->add_nav_link("numeric_score_year", new Link('popular_by_year'), "Popular by Year");
        }
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        if ($this->get_version("ext_numeric_score_version") < 1) {
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
            $database->execute("CREATE INDEX numeric_score_votes_image_id_idx ON numeric_score_votes(image_id)", []);
            $this->set_version("ext_numeric_score_version", 1);
        }
        if ($this->get_version("ext_numeric_score_version") < 2) {
            $database->execute("CREATE INDEX numeric_score_votes__user_votes ON numeric_score_votes(user_id, score)");
            $this->set_version("ext_numeric_score_version", 2);
        }
    }

    private function add_vote(int $image_id, int $user_id, int $score): void
    {
        global $database;
        $database->execute(
            "DELETE FROM numeric_score_votes WHERE image_id=:imageid AND user_id=:userid",
            ["imageid" => $image_id, "userid" => $user_id]
        );
        if ($score != 0) {
            $database->execute(
                "INSERT INTO numeric_score_votes(image_id, user_id, score) VALUES(:imageid, :userid, :score)",
                ["imageid" => $image_id, "userid" => $user_id, "score" => $score]
            );
        }
        $database->execute(
            "UPDATE images SET numeric_score=(
				COALESCE(
					(SELECT SUM(score) FROM numeric_score_votes WHERE image_id=:imageid),
					0
				)
			) WHERE id=:id",
            ["imageid" => $image_id, "id" => $image_id]
        );
    }
}
