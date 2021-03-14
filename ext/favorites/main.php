<?php declare(strict_types=1);

class FavoriteSetEvent extends Event
{
    public int $image_id;
    public User $user;
    public bool $do_set;

    public function __construct(int $image_id, User $user, bool $do_set)
    {
        parent::__construct();
        assert(is_int($image_id));
        assert(is_bool($do_set));

        $this->image_id = $image_id;
        $this->user = $user;
        $this->do_set = $do_set;
    }
}

class Favorites extends Extension
{
    /** @var FavoritesTheme */
    protected ?Themelet $theme;

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event)
    {
        global $database, $user;
        if (!$user->is_anonymous()) {
            $user_id = $user->id;
            $image_id = $event->image->id;

            $is_favorited = $database->get_one(
                "SELECT COUNT(*) AS ct FROM user_favorites WHERE user_id = :user_id AND image_id = :image_id",
                ["user_id"=>$user_id, "image_id"=>$image_id]
            ) > 0;

            $event->add_part((string)$this->theme->get_voter_html($event->image, $is_favorited));
        }
    }

    public function onDisplayingImage(DisplayingImageEvent $event)
    {
        $people = $this->list_persons_who_have_favorited($event->image);
        if (count($people) > 0) {
            $this->theme->display_people($people);
        }
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $user;
        if ($event->page_matches("change_favorite") && !$user->is_anonymous() && $user->check_auth_token()) {
            $image_id = int_escape($_POST['image_id']);
            if ((($_POST['favorite_action'] == "set") || ($_POST['favorite_action'] == "unset")) && ($image_id > 0)) {
                if ($_POST['favorite_action'] == "set") {
                    send_event(new FavoriteSetEvent($image_id, $user, true));
                    log_debug("favourite", "Favourite set for $image_id", "Favourite added");
                } else {
                    send_event(new FavoriteSetEvent($image_id, $user, false));
                    log_debug("favourite", "Favourite removed for $image_id", "Favourite removed");
                }
            }
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/$image_id"));
        }
    }

    public function onUserPageBuilding(UserPageBuildingEvent $event)
    {
        $i_favorites_count = Image::count_images(["favorited_by={$event->display_user->name}"]);
        $i_days_old = ((time() - strtotime($event->display_user->join_date)) / 86400) + 1;
        $h_favorites_rate = sprintf("%.1f", ($i_favorites_count / $i_days_old));
        $favorites_link = make_link("post/list/favorited_by={$event->display_user->name}/1");
        $event->add_stats("<a href='$favorites_link'>Posts favorited</a>: $i_favorites_count, $h_favorites_rate per day");
    }

    public function onImageInfoSet(ImageInfoSetEvent $event)
    {
        global $user;
        if (
            $user->can(Permissions::EDIT_FAVOURITES) &&
            in_array('favorite_action', $_POST) &&
            (($_POST['favorite_action'] == "set") || ($_POST['favorite_action'] == "unset"))
        ) {
            send_event(new FavoriteSetEvent($event->image->id, $user, ($_POST['favorite_action'] == "set")));
        }
    }

    public function onFavoriteSet(FavoriteSetEvent $event)
    {
        global $user;
        $this->add_vote($event->image_id, $user->id, $event->do_set);
    }

    // FIXME: this should be handled by the foreign key. Check that it
    // is, and then remove this
    public function onImageDeletion(ImageDeletionEvent $event)
    {
        global $database;
        $database->execute("DELETE FROM user_favorites WHERE image_id=:image_id", ["image_id"=>$event->image->id]);
    }

    public function onParseLinkTemplate(ParseLinkTemplateEvent $event)
    {
        $event->replace('$favorites', (string)$event->image->favorites);
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event)
    {
        global $user;

        $username = url_escape($user->name);
        $event->add_link("My Favorites", make_link("post/list/favorited_by=$username/1"), 20);
    }

    public function onSearchTermParse(SearchTermParseEvent $event)
    {
        if (is_null($event->term)) {
            return;
        }

        $matches = [];
        if (preg_match("/^favorites([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(\d+)$/i", $event->term, $matches)) {
            $cmp = ltrim($matches[1], ":") ?: "=";
            $favorites = $matches[2];
            $event->add_querylet(new Querylet("images.id IN (SELECT id FROM images WHERE favorites $cmp $favorites)"));
        } elseif (preg_match("/^favorited_by[=|:](.*)$/i", $event->term, $matches)) {
            $user_id = User::name_to_id($matches[1]);
            $event->add_querylet(new Querylet("images.id IN (SELECT image_id FROM user_favorites WHERE user_id = $user_id)"));
        } elseif (preg_match("/^favorited_by_userno[=|:](\d+)$/i", $event->term, $matches)) {
            $user_id = int_escape($matches[1]);
            $event->add_querylet(new Querylet("images.id IN (SELECT image_id FROM user_favorites WHERE user_id = $user_id)"));
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event)
    {
        if ($event->key===HelpPages::SEARCH) {
            $event->add_block(new Block("Favorites", (string)$this->theme->get_help_html()));
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event)
    {
        global $user;
        if ($event->parent=="posts") {
            $event->add_nav_link("posts_favorites", new Link("post/list/favorited_by={$user->name}/1"), "My Favorites");
        }

        if ($event->parent==="user") {
            if ($user->can(Permissions::MANAGE_ADMINTOOLS)) {
                $username = url_escape($user->name);
                $event->add_nav_link("favorites", new Link("post/list/favorited_by=$username/1"), "My Favorites");
            }
        }
    }

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event)
    {
        global $user;

        if (!$user->is_anonymous()) {
            $event->add_action("bulk_favorite", "Favorite");
            $event->add_action("bulk_unfavorite", "Un-Favorite");
        }
    }

    public function onBulkAction(BulkActionEvent $event)
    {
        global $page, $user;

        switch ($event->action) {
            case "bulk_favorite":
                if (!$user->is_anonymous()) {
                    $total = 0;
                    foreach ($event->items as $image) {
                        send_event(new FavoriteSetEvent($image->id, $user, true));
                        $total++;
                    }
                    $page->flash("Added $total items to favorites");
                }
                break;
            case "bulk_unfavorite":
                if (!$user->is_anonymous()) {
                    $total = 0;
                    foreach ($event->items as $image) {
                        send_event(new FavoriteSetEvent($image->id, $user, false));
                        $total++;
                    }
                    $page->flash("Removed $total items from favorites");
                }
                break;
        }
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event)
    {
        global $database;

        if ($this->get_version("ext_favorites_version") < 1) {
            $database->execute("ALTER TABLE images ADD COLUMN favorites INTEGER NOT NULL DEFAULT 0");
            $database->execute("CREATE INDEX images__favorites ON images(favorites)");
            $database->create_table("user_favorites", "
					image_id INTEGER NOT NULL,
					user_id INTEGER NOT NULL,
					created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
					UNIQUE(image_id, user_id),
					FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
					FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE
					");
            $database->execute("CREATE INDEX user_favorites_image_id_idx ON user_favorites(image_id)", []);
            $this->set_version("ext_favorites_version", 2);
        }

        if ($this->get_version("ext_favorites_version") < 2) {
            log_info("favorites", "Cleaning user favourites");
            $database->execute("DELETE FROM user_favorites WHERE user_id NOT IN (SELECT id FROM users)");
            $database->execute("DELETE FROM user_favorites WHERE image_id NOT IN (SELECT id FROM images)");

            log_info("favorites", "Adding foreign keys to user favourites");
            $database->execute("ALTER TABLE user_favorites ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;");
            $database->execute("ALTER TABLE user_favorites ADD FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE;");
            $this->set_version("ext_favorites_version", 2);
        }
    }

    private function add_vote(int $image_id, int $user_id, bool $do_set)
    {
        global $database;
        if ($do_set) {
            if (!$database->get_row("select 1 from user_favorites where image_id=:image_id and user_id=:user_id", ["image_id"=>$image_id, "user_id"=>$user_id])) {
                $database->execute(
                    "INSERT INTO user_favorites(image_id, user_id, created_at) VALUES(:image_id, :user_id, NOW())",
                    ["image_id"=>$image_id, "user_id"=>$user_id]
                );
            }
        } else {
            $database->execute(
                "DELETE FROM user_favorites WHERE image_id = :image_id AND user_id = :user_id",
                ["image_id"=>$image_id, "user_id"=>$user_id]
            );
        }
        $database->execute(
            "UPDATE images SET favorites=(SELECT COUNT(*) FROM user_favorites WHERE image_id=:image_id) WHERE id=:user_id",
            ["image_id"=>$image_id, "user_id"=>$user_id]
        );
    }

    /**
     * #return string[]
     */
    private function list_persons_who_have_favorited(Image $image): array
    {
        global $database;

        return $database->get_col(
            "SELECT name FROM users WHERE id IN (SELECT user_id FROM user_favorites WHERE image_id = :image_id) ORDER BY name",
            ["image_id"=>$image->id]
        );
    }
}
