<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, emptyHTML};

final class FavoriteSetEvent extends Event
{
    public function __construct(
        public int $image_id,
        public User $user,
        public bool $do_set
    ) {
        parent::__construct();
    }
}

/** @extends Extension<FavoritesTheme> */
final class Favorites extends Extension
{
    public const KEY = "favorites";

    public function onInitExt(InitExtEvent $event): void
    {
        Image::$prop_types["favorites"] = ImagePropType::INT;
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        if (Ctx::$user->can(FavouritesPermission::EDIT_FAVOURITES)) {
            $user_id = Ctx::$user->id;
            $image_id = $event->image->id;

            $is_favorited = Ctx::$database->get_one(
                "SELECT COUNT(*) AS ct FROM user_favorites WHERE user_id = :user_id AND image_id = :image_id",
                ["user_id" => $user_id, "image_id" => $image_id]
            ) > 0;

            if ($is_favorited) {
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
        if ($event->page_matches("favourite/add/{image_id}", method: "POST", permission: FavouritesPermission::EDIT_FAVOURITES)) {
            $image_id = $event->get_iarg('image_id');
            send_event(new FavoriteSetEvent($image_id, Ctx::$user, true));
            Ctx::$page->set_redirect(make_link("post/view/$image_id"));
        }
        if ($event->page_matches("favourite/remove/{image_id}", method: "POST", permission: FavouritesPermission::EDIT_FAVOURITES)) {
            $image_id = $event->get_iarg('image_id');
            send_event(new FavoriteSetEvent($image_id, Ctx::$user, false));
            Ctx::$page->set_redirect(make_link("post/view/$image_id"));
        }
    }

    public function onUserPageBuilding(UserPageBuildingEvent $event): void
    {
        $i_favorites_count = Search::count_images(["favorited_by={$event->display_user->name}"]);
        $i_days_old = ((time() - \Safe\strtotime($event->display_user->join_date)) / 86400) + 1;
        $h_favorites_rate = sprintf("%.1f", ($i_favorites_count / $i_days_old));
        $favorites_link = search_link(["favorited_by={$event->display_user->name}"]);
        $event->add_part(emptyHTML(
            A(["href" => $favorites_link], "Posts favorited"),
            ": $i_favorites_count, $h_favorites_rate per day"
        ));
    }

    public function onImageInfoSet(ImageInfoSetEvent $event): void
    {
        $action = $event->get_param("favorite_action");
        if (
            Ctx::$user->can(FavouritesPermission::EDIT_FAVOURITES) &&
            !is_null($action) &&
            ($action === "set" || $action === "unset")
        ) {
            send_event(new FavoriteSetEvent($event->image->id, Ctx::$user, $action === "set"));
        }
    }

    public function onFavoriteSet(FavoriteSetEvent $event): void
    {
        $this->add_vote($event->image_id, Ctx::$user->id, $event->do_set);
    }

    // FIXME: this should be handled by the foreign key. Check that it
    // is, and then remove this
    public function onImageDeletion(ImageDeletionEvent $event): void
    {
        Ctx::$database->execute("DELETE FROM user_favorites WHERE image_id=:image_id", ["image_id" => $event->image->id]);
    }

    public function onParseLinkTemplate(ParseLinkTemplateEvent $event): void
    {
        $event->replace('$favorites', (string)$event->image['favorites']);
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        $event->add_link("My Favorites", search_link(["favorited_by=" . Ctx::$user->name]), 20);
    }

    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        if ($matches = $event->matches("/^favorites(:|<=|<|=|>|>=)(\d+)$/i")) {
            $cmp = ltrim($matches[1], ":") ?: "=";
            $favorites = $matches[2];
            $event->add_querylet(new Querylet("images.id IN (SELECT id FROM images WHERE favorites $cmp $favorites)"));
        } elseif ($matches = $event->matches("/^favorited_by[=:](.*)$/i")) {
            $user_id = User::name_to_id($matches[1]);
            $event->add_querylet(new Querylet("images.id IN (SELECT image_id FROM user_favorites WHERE user_id = $user_id)"));
        } elseif ($matches = $event->matches("/^favorited_by_userno[=:](\d+)$/i")) {
            $user_id = int_escape($matches[1]);
            $event->add_querylet(new Querylet("images.id IN (SELECT image_id FROM user_favorites WHERE user_id = $user_id)"));
        } elseif ($matches = $event->matches("/^order[=:](favorites)(?:_(desc|asc))?$/i")) {
            $default_order_for_column = "DESC";
            $sort = isset($matches[2]) ? strtoupper($matches[2]) : $default_order_for_column;
            $event->order = "images.favorites $sort";
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        if ($event->key === HelpPages::SEARCH) {
            $event->add_section("Favorites", $this->theme->get_help_html());
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "posts") {
            $event->add_nav_link(search_link(["favorited_by=" . Ctx::$user->name]), "My Favorites", "my_favorites");
        }
    }

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event): void
    {
        $event->add_action("favorite", "Favorite", permission: FavouritesPermission::EDIT_FAVOURITES);
        $event->add_action("unfavorite", "Un-Favorite", permission: FavouritesPermission::EDIT_FAVOURITES);
    }

    public function onBulkAction(BulkActionEvent $event): void
    {
        switch ($event->action) {
            case "favorite":
                if (Ctx::$user->can(FavouritesPermission::EDIT_FAVOURITES)) {
                    $total = 0;
                    foreach ($event->items as $image) {
                        send_event(new FavoriteSetEvent($image->id, Ctx::$user, true));
                        $total++;
                    }
                    $event->log_action("Added $total items to favorites");
                }
                break;
            case "unfavorite":
                if (Ctx::$user->can(FavouritesPermission::EDIT_FAVOURITES)) {
                    $total = 0;
                    foreach ($event->items as $image) {
                        send_event(new FavoriteSetEvent($image->id, Ctx::$user, false));
                        $total++;
                    }
                    $event->log_action("Removed $total items from favorites");
                }
                break;
        }
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        if ($this->get_version() < 1) {
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
            $this->set_version(2);
        }

        if ($this->get_version() < 2) {
            Log::info("favorites", "Cleaning user favourites");
            $database->execute("DELETE FROM user_favorites WHERE user_id NOT IN (SELECT id FROM users)");
            $database->execute("DELETE FROM user_favorites WHERE image_id NOT IN (SELECT id FROM images)");

            Log::info("favorites", "Adding foreign keys to user favourites");
            $database->execute("ALTER TABLE user_favorites ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;");
            $database->execute("ALTER TABLE user_favorites ADD FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE;");
            $this->set_version(2);
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
            "UPDATE images SET favorites=(SELECT COUNT(*) FROM user_favorites WHERE image_id=:image_id) WHERE id=:image_id",
            ["image_id" => $image_id]
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
