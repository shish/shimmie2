<?php

declare(strict_types=1);

namespace Shimmie2;

final class PrivateImage extends Extension
{
    public const KEY = "private_image";
    /** @var PrivateImageTheme */
    protected Themelet $theme;

    public function onInitExt(InitExtEvent $event): void
    {
        Image::$prop_types["private"] = ImagePropType::BOOL;
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user;

        if ($event->page_matches("privatize_image/{image_id}", method: "POST", permission: PrivateImagePermission::SET_PRIVATE_IMAGE)) {
            $image_id = $event->get_iarg('image_id');
            $image = Image::by_id_ex($image_id);
            if ($image->owner_id !== $user->id && !$user->can(PrivateImagePermission::SET_OTHERS_PRIVATE_IMAGES)) {
                throw new PermissionDenied("Cannot set another user's image to private.");
            }

            self::privatize_image($image_id);
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/" . $image_id));
        }

        if ($event->page_matches("publicize_image/{image_id}", method: "POST")) {
            $image_id = $event->get_iarg('image_id');
            $image = Image::by_id_ex($image_id);
            if ($image->owner_id !== $user->id && !$user->can(PrivateImagePermission::SET_OTHERS_PRIVATE_IMAGES)) {
                throw new PermissionDenied("Cannot set another user's image to public.");
            }

            self::publicize_image($image_id);
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/".$image_id));
        }

        if ($event->page_matches("user_admin/private_image", method: "POST")) {
            $id = int_escape($event->req_POST('id'));
            if ($id !== $user->id) {
                throw new PermissionDenied("Cannot change another user's settings");
            }
            $set_default = array_key_exists("set_default", $event->POST);
            $view_default = array_key_exists("view_default", $event->POST);

            $user->get_config()->set_bool(PrivateImageUserConfig::SET_DEFAULT, $set_default);
            $user->get_config()->set_bool(PrivateImageUserConfig::VIEW_DEFAULT, $view_default);

            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("user"));
        }
    }

    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        global $user, $page;

        if ($event->image['private'] === true && $event->image->owner_id !== $user->id && !$user->can(PrivateImagePermission::SET_OTHERS_PRIVATE_IMAGES)) {
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link());
        }
    }

    public const SEARCH_REGEXP = "/^private:(yes|no|any)/i";
    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        global $user;
        $show_private = $user->get_config()->get_bool(PrivateImageUserConfig::VIEW_DEFAULT);

        if (is_null($event->term) && $this->no_private_query($event->context)) {
            if ($show_private) {
                $event->add_querylet(
                    new Querylet(
                        "private != :true OR owner_id = :private_owner_id",
                        ["private_owner_id" => $user->id, "true" => true]
                    )
                );
            } else {
                $event->add_querylet(
                    new Querylet("private != :true", ["true" => true])
                );
            }
        }

        if ($matches = $event->matches(self::SEARCH_REGEXP)) {
            $params = [];
            $query = "";
            switch (strtolower($matches[1])) {
                case "no":
                    $query .= "private != :true";
                    $params["true"] = true;
                    break;
                case "yes":
                    $query .= "private = :true";
                    $params["true"] = true;

                    // Admins can view others private images, but they have to specify the user
                    if (!$user->can(PrivateImagePermission::SET_OTHERS_PRIVATE_IMAGES) ||
                        !UserPage::has_user_query($event->context)) {
                        $query .= " AND owner_id = :private_owner_id";
                        $params["private_owner_id"] = $user->id;
                    }
                    break;
                case "any":
                    $query .= "private != :true OR owner_id = :private_owner_id";
                    $params["true"] = true;
                    $params["private_owner_id"] = $user->id;
                    break;
            }
            $event->add_querylet(new Querylet($query, $params));
        }
    }

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
            "UPDATE images SET private = :true WHERE id = :id AND private = :false",
            ["id" => $image_id, "true" => true, "false" => false]
        );
    }

    public static function publicize_image(int $image_id): void
    {
        global $database;

        $database->execute(
            "UPDATE images SET private = :false WHERE id = :id AND private = :true",
            ["id" => $image_id, "true" => true, "false" => false]
        );
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        global $user;
        if (($user->can(PrivateImagePermission::SET_PRIVATE_IMAGE) && $user->id == $event->image->owner_id) || $user->can(PrivateImagePermission::SET_OTHERS_PRIVATE_IMAGES)) {
            if ($event->image['private'] === false) {
                $event->add_button("Make Private", "privatize_image/".$event->image->id);
            } else {
                $event->add_button("Make Public", "publicize_image/".$event->image->id);
            }
        }
    }

    public function onImageAddition(ImageAdditionEvent $event): void
    {
        global $user;
        if ($user->get_config()->get_bool(PrivateImageUserConfig::SET_DEFAULT) && $user->can(PrivateImagePermission::SET_PRIVATE_IMAGE)) {
            self::privatize_image($event->image->id);
        }
    }

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event): void
    {
        global $user;

        if ($user->can(PrivateImagePermission::SET_PRIVATE_IMAGE)) {
            $event->add_action("bulk_privatize_image", "Make Private");
            $event->add_action("bulk_publicize_image", "Make Public");
        }
    }

    public function onBulkAction(BulkActionEvent $event): void
    {
        global $page, $user;

        switch ($event->action) {
            case "bulk_privatize_image":
                if ($user->can(PrivateImagePermission::SET_PRIVATE_IMAGE)) {
                    $total = 0;
                    foreach ($event->items as $image) {
                        if ($image->owner_id == $user->id ||
                            $user->can(PrivateImagePermission::SET_OTHERS_PRIVATE_IMAGES)) {
                            self::privatize_image($image->id);
                            $total++;
                        }
                    }
                    $page->flash("Made $total items private");
                }
                break;
            case "bulk_publicize_image":
                $total = 0;
                foreach ($event->items as $image) {
                    if ($image->owner_id == $user->id ||
                        $user->can(PrivateImagePermission::SET_OTHERS_PRIVATE_IMAGES)) {
                        self::publicize_image($image->id);
                        $total++;
                    }
                }
                $page->flash("Made $total items public");
                break;
        }
    }
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
