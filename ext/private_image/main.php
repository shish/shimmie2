<?php

declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<PrivateImageTheme> */
final class PrivateImage extends Extension
{
    public const KEY = "private_image";

    #[EventListener]
    public function onInitExt(InitExtEvent $event): void
    {
        Image::$prop_types["private"] = ImagePropType::BOOL;
    }

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        $user = Ctx::$user;

        if ($event->page_matches("privatize_image/{image_id}", method: "POST", permission: PrivateImagePermission::SET_PRIVATE_IMAGE)) {
            $image_id = $event->get_iarg('image_id');
            $image = Image::by_id_ex($image_id);
            if ($image->owner_id !== $user->id && !$user->can(PrivateImagePermission::SET_OTHERS_PRIVATE_IMAGES)) {
                throw new PermissionDenied("Cannot set another user's image to private.");
            }

            self::privatize_image($image_id);
            Ctx::$page->set_redirect(make_link("post/view/" . $image_id));
        }

        if ($event->page_matches("publicize_image/{image_id}", method: "POST")) {
            $image_id = $event->get_iarg('image_id');
            $image = Image::by_id_ex($image_id);
            if ($image->owner_id !== $user->id && !$user->can(PrivateImagePermission::SET_OTHERS_PRIVATE_IMAGES)) {
                throw new PermissionDenied("Cannot set another user's image to public.");
            }

            self::publicize_image($image_id);
            Ctx::$page->set_redirect(make_link("post/view/".$image_id));
        }

        if ($event->page_matches("user_admin/private_image", method: "POST")) {
            $id = int_escape($event->POST->req('id'));
            if ($id !== $user->id) {
                throw new PermissionDenied("Cannot change another user's settings");
            }

            $user->get_config()->set(PrivateImageUserConfig::SET_DEFAULT, $event->POST->offsetExists("set_default"));
            $user->get_config()->set(PrivateImageUserConfig::VIEW_DEFAULT, $event->POST->offsetExists("view_default"));

            Ctx::$page->set_redirect(make_link("user"));
        }
    }

    #[EventListener]
    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        if (
            $event->image['private'] === true
            && $event->image->owner_id !== Ctx::$user->id
            && !Ctx::$user->can(PrivateImagePermission::SET_OTHERS_PRIVATE_IMAGES)
        ) {
            Ctx::$page->set_redirect(make_link());
        }
    }

    public const SEARCH_REGEXP = "/^private[=:](yes|no|any)/i";

    #[EventListener]
    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        $show_private = Ctx::$user->get_config()->get(PrivateImageUserConfig::VIEW_DEFAULT);

        if (is_null($event->term) && $this->no_private_query($event->context)) {
            if ($show_private) {
                $event->add_querylet(
                    new Querylet(
                        "private != TRUE OR owner_id = :private_owner_id",
                        ["private_owner_id" => Ctx::$user->id]
                    )
                );
            } else {
                $event->add_querylet(
                    new Querylet("private != TRUE")
                );
            }
        }

        if ($matches = $event->matches(self::SEARCH_REGEXP)) {
            $params = [];
            $query = "";
            switch (strtolower($matches[1])) {
                case "no":
                    $query .= "private != TRUE";
                    break;
                case "yes":
                    $query .= "private = TRUE";

                    // Admins can view others private images, but they have to specify the user
                    if (!Ctx::$user->can(PrivateImagePermission::SET_OTHERS_PRIVATE_IMAGES) ||
                        !UserPage::has_user_query($event->context)) {
                        $query .= " AND owner_id = :private_owner_id";
                        $params["private_owner_id"] = Ctx::$user->id;
                    }
                    break;
                case "any":
                    $query .= "private != TRUE OR owner_id = :private_owner_id";
                    $params["private_owner_id"] = Ctx::$user->id;
                    break;
            }
            $event->add_querylet(new Querylet($query, $params));
        }
    }

    #[EventListener]
    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        if ($event->key === HelpPages::SEARCH) {
            $event->add_section("Private Posts", $this->theme->get_help_html());
        }
    }

    /**
     * @param string[] $context
     */
    private function no_private_query(array $context): bool
    {
        foreach ($context as $term) {
            if (\Safe\preg_match(self::SEARCH_REGEXP, $term)) {
                return false;
            }
        }
        return true;
    }

    public static function privatize_image(int $image_id): void
    {
        global $database;

        $database->execute(
            "UPDATE images SET private = TRUE WHERE id = :id AND private = FALSE",
            ["id" => $image_id]
        );
    }

    public static function publicize_image(int $image_id): void
    {
        global $database;

        $database->execute(
            "UPDATE images SET private = FALSE WHERE id = :id AND private = TRUE",
            ["id" => $image_id]
        );
    }

    #[EventListener]
    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        if ((Ctx::$user->can(PrivateImagePermission::SET_PRIVATE_IMAGE) && Ctx::$user->id === $event->image->owner_id) || Ctx::$user->can(PrivateImagePermission::SET_OTHERS_PRIVATE_IMAGES)) {
            if ($event->image['private'] === false) {
                $event->add_button("Make Private", "privatize_image/".$event->image->id);
            } else {
                $event->add_button("Make Public", "publicize_image/".$event->image->id);
            }
        }
    }

    #[EventListener]
    public function onImageAddition(ImageAdditionEvent $event): void
    {
        if (Ctx::$user->get_config()->get(PrivateImageUserConfig::SET_DEFAULT) && Ctx::$user->can(PrivateImagePermission::SET_PRIVATE_IMAGE)) {
            self::privatize_image($event->image->id);
        }
    }

    #[EventListener]
    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event): void
    {
        $event->add_action("privatize-post", "Make Private", permission: PrivateImagePermission::SET_PRIVATE_IMAGE);
        $event->add_action("publicize-post", "Make Public", permission: PrivateImagePermission::SET_PRIVATE_IMAGE);
    }

    #[EventListener]
    public function onBulkAction(BulkActionEvent $event): void
    {
        switch ($event->action) {
            case "privatize-post":
                if (Ctx::$user->can(PrivateImagePermission::SET_PRIVATE_IMAGE)) {
                    $total = 0;
                    foreach ($event->items as $image) {
                        if (
                            $image->owner_id === Ctx::$user->id ||
                            Ctx::$user->can(PrivateImagePermission::SET_OTHERS_PRIVATE_IMAGES)
                        ) {
                            self::privatize_image($image->id);
                            $total++;
                        }
                    }
                    $event->log_action("Made $total items private");
                }
                break;
            case "publicize-post":
                $total = 0;
                foreach ($event->items as $image) {
                    if (
                        $image->owner_id === Ctx::$user->id ||
                        Ctx::$user->can(PrivateImagePermission::SET_OTHERS_PRIVATE_IMAGES)
                    ) {
                        self::publicize_image($image->id);
                        $total++;
                    }
                }
                $event->log_action("Made $total items public");
                break;
        }
    }
    #[EventListener]
    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        if ($this->get_version() < 1) {
            $database->execute("ALTER TABLE images ADD COLUMN private BOOLEAN NOT NULL DEFAULT FALSE");
            $database->execute("CREATE INDEX images_private_idx ON images(private)");
            $this->set_version(2);
        }
        if ($this->get_version() < 2) {
            $database->standardise_boolean("images", "private");
            $this->set_version(2);
        }
    }
}
