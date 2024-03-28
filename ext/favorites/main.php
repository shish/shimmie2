<?php

declare(strict_types=1);

namespace Shimmie2;

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
    protected Themelet $theme;

    public function onInitExt(InitExtEvent $event): void
    {
        Image::$prop_types["favorites"] = ImagePropType::INT;
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        global $database, $user;
        if (!$user->is_anonymous()) {
            $user_id = $user->id;
            $image_id = $event->image->id;

            $is_favorited = $database->get_one(
                "SELECT COUNT(*) AS ct FROM user_favorites WHERE user_id = :user_id AND image_id = :image_id",
                ["user_id" => $user_id, "image_id" => $image_id]
            ) > 0;

            if($is_favorited) {
                $event->add_button("Un-Favorite", "favourite/remove/{$event->image->id}");
            } else {
                $event->add_button("Favorite", "favourite/add/{$event->image->id}");
            }
        }
    }

    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        $people = $this->list_persons_who_have_favorited($event->image);
        if (count($people) > 0) {
            $this->theme->display_people($people);
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user;
        if ($user->is_anonymous()) {
            return;
        } // FIXME: proper permissions

        if ($event->page_matches("favourite/add/{image_id}", method: "POST")) {
            $image_id = $event->get_iarg('image_id');
            send_event(new FavoriteSetEvent($image_id, $user, true));
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/$image_id"));
        }
        if ($event->page_matches("favourite/remove/{image_id}", method: "POST")) {
            $image_id = $event->get_iarg('image_id');
            send_event(new FavoriteSetEvent($image_id, $user, false));
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/$image_id"));
        }
    }

    public function onUserPageBuilding(UserPageBuildingEvent $event): void
    {
        $i_favorites_count = Search::count_images(["favorited_by={$event->display_user->name}"]);
        $i_days_old = ((time() - \Safe\strtotime($event->display_user->join_date)) / 86400) + 1;
        $h_favorites_rate = sprintf("%.1f", ($i_favorites_count / $i_days_old));
        $favorites_link = search_link(["favorited_by={$event->display_user->name}"]);
        $event->add_part("<a href='$favorites_link'>Posts favorited</a>: $i_favorites_count, $h_favorites_rate per day");
    }

    public function onImageInfoSet(ImageInfoSetEvent $event): void
    {
        global $user;
        $action = $event->get_param("favorite_action");
        if (
            $user->can(Permissions::EDIT_FAVOURITES) &&
            !is_null($action) &&
            ($action == "set" || $action == "unset")
        ) {
            send_event(new FavoriteSetEvent($event->image->id, $user, $action == "set"));
        }
    }

    public function onFavoriteSet(FavoriteSetEvent $event): void
    {
        global $user;
        $this->add_vote($event->image_id, $user->id, $event->do_set);
    }

    // FIXME: this should be handled by the foreign key. Check that it
    // is, and then remove this
    public function onImageDeletion(ImageDeletionEvent $event): void
    {
        global $database;
        $database->execute("DELETE FROM user_favorites WHERE image_id=:image_id", ["image_id" => $event->image->id]);
    }

    public function onParseLinkTemplate(ParseLinkTemplateEvent $event): void
    {
        $event->replace('$favorites', (string)$event->image['favorites']);
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        global $user;

        $username = url_escape($user->name);
        $event->add_link("My Favorites", search_link(["favorited_by=$username"]), 20);
    }

    public function onSearchTermParse(SearchTermParseEvent $event): void
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
        } elseif (preg_match("/^order[=|:](favorites)(?:_(desc|asc))?$/i", $event->term, $matches)) {
            $default_order_for_column = "DESC";
            $sort = isset($matches[2]) ? strtoupper($matches[2]) : $default_order_for_column;
            $event->order = "images.favorites $sort";
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        if ($event->key === HelpPages::SEARCH) {
            $event->add_block(new Block("Favorites", $this->theme->get_help_html()));
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        global $user;
        if ($event->parent == "posts") {
            $event->add_nav_link("posts_favorites", new Link("post/list/favorited_by={$user->name}/1"), "My Favorites");
        }

        if ($event->parent === "user") {
            if ($user->can(Permissions::MANAGE_ADMINTOOLS)) {
                $username = url_escape($user->name);
                $event->add_nav_link("favorites", new Link("post/list/favorited_by=$username/1"), "My Favorites");
            }
        }
    }

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event): void
    {
        global $user;

        if (!$user->is_anonymous()) {
            $event->add_action("bulk_favorite", "Favorite");
            $event->add_action("bulk_unfavorite", "Un-Favorite");
        }
    }

    public function onBulkAction(BulkActionEvent $event): void
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

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
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

    private function add_vote(int $image_id, int $user_id, bool $do_set): void
    {
        global $database;
        if ($do_set) {
            if (!$database->get_row("select 1 from user_favorites where image_id=:image_id and user_id=:user_id", ["image_id" => $image_id, "user_id" => $user_id])) {
                $database->execute(
                    "INSERT INTO user_favorites(image_id, user_id, created_at) VALUES(:image_id, :user_id, NOW())",
                    ["image_id" => $image_id, "user_id" => $user_id]
                );
            }
        } else {
            $database->execute(
                "DELETE FROM user_favorites WHERE image_id = :image_id AND user_id = :user_id",
                ["image_id" => $image_id, "user_id" => $user_id]
            );
        }
        $database->execute(
            "UPDATE images SET favorites=(SELECT COUNT(*) FROM user_favorites WHERE image_id=:image_id) WHERE id=:user_id",
            ["image_id" => $image_id, "user_id" => $user_id]
        );
    }

    /**
     * @return string[]
     */
    private function list_persons_who_have_favorited(Image $image): array
    {
        global $database;

        return $database->get_col(
            "SELECT name FROM users WHERE id IN (SELECT user_id FROM user_favorites WHERE image_id = :image_id) ORDER BY name",
            ["image_id" => $image->id]
        );
    }
}
