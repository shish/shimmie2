<?php

declare(strict_types=1);

namespace Shimmie2;

abstract class PrivateImageConfig
{
    public const VERSION = "ext_private_image_version";
    public const USER_SET_DEFAULT = "user_private_image_set_default";
    public const USER_VIEW_DEFAULT = "user_private_image_view_default";
}

class PrivateImage extends Extension
{
    /** @var PrivateImageTheme */
    protected Themelet $theme;

    public function onInitExt(InitExtEvent $event): void
    {
        Image::$prop_types["private"] = ImagePropType::BOOL;
    }

    public function onInitUserConfig(InitUserConfigEvent $event): void
    {
        $event->user_config->set_default_bool(PrivateImageConfig::USER_SET_DEFAULT, false);
        $event->user_config->set_default_bool(PrivateImageConfig::USER_VIEW_DEFAULT, true);
    }

    public function onUserOptionsBuilding(UserOptionsBuildingEvent $event): void
    {
        global $user;
        $sb = $event->panel->create_new_block("Private Posts");
        $sb->start_table();
        if ($user->can(Permissions::SET_PRIVATE_IMAGE)) {
            $sb->add_bool_option(PrivateImageConfig::USER_SET_DEFAULT, "Mark posts private by default", true);
        }
        $sb->add_bool_option(PrivateImageConfig::USER_VIEW_DEFAULT, "View private posts by default", true);
        $sb->end_table();
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user, $user_config;

        if ($event->page_matches("privatize_image/{image_id}", method: "POST", permission: Permissions::SET_PRIVATE_IMAGE)) {
            $image_id = $event->get_iarg('image_id');
            $image = Image::by_id_ex($image_id);
            if ($image->owner_id != $user->can(Permissions::SET_OTHERS_PRIVATE_IMAGES)) {
                throw new PermissionDenied("Cannot set another user's image to private.");
            }

            self::privatize_image($image_id);
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/" . $image_id));
        }

        if ($event->page_matches("publicize_image/{image_id}", method: "POST")) {
            $image_id = $event->get_iarg('image_id');
            $image = Image::by_id_ex($image_id);
            if ($image->owner_id != $user->can(Permissions::SET_OTHERS_PRIVATE_IMAGES)) {
                throw new PermissionDenied("Cannot set another user's image to public.");
            }

            self::publicize_image($image_id);
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/".$image_id));
        }

        if ($event->page_matches("user_admin/private_image", method: "POST")) {
            $id = int_escape($event->req_POST('id'));
            if ($id != $user->id) {
                throw new PermissionDenied("Cannot change another user's settings");
            }
            $set_default = array_key_exists("set_default", $event->POST);
            $view_default = array_key_exists("view_default", $event->POST);

            $user_config->set_bool(PrivateImageConfig::USER_SET_DEFAULT, $set_default);
            $user_config->set_bool(PrivateImageConfig::USER_VIEW_DEFAULT, $view_default);

            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("user"));
        }
    }

    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        global $user, $page;

        if ($event->image['private'] === true && $event->image->owner_id != $user->id && !$user->can(Permissions::SET_OTHERS_PRIVATE_IMAGES)) {
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link());
        }
    }

    public const SEARCH_REGEXP = "/^private:(yes|no|any)/";
    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        global $user, $user_config;
        $show_private = $user_config->get_bool(PrivateImageConfig::USER_VIEW_DEFAULT);

        $matches = [];

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

        if (is_null($event->term)) {
            return;
        }

        if (preg_match(self::SEARCH_REGEXP, strtolower($event->term), $matches)) {
            $params = [];
            $query = "";
            switch ($matches[1]) {
                case "no":
                    $query .= "private != :true";
                    $params["true"] = true;
                    break;
                case "yes":
                    $query .= "private = :true";
                    $params["true"] = true;

                    // Admins can view others private images, but they have to specify the user
                    if (!$user->can(Permissions::SET_OTHERS_PRIVATE_IMAGES) ||
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
            $block = new Block();
            $block->header = "Private Posts";
            $block->body = $this->theme->get_help_html();
            $event->add_block($block);
        }
    }

    /**
     * @param string[] $context
     */
    private function no_private_query(array $context): bool
    {
        foreach ($context as $term) {
            if (preg_match(self::SEARCH_REGEXP, $term)) {
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
        if (($user->can(Permissions::SET_PRIVATE_IMAGE) && $user->id == $event->image->owner_id) || $user->can(Permissions::SET_OTHERS_PRIVATE_IMAGES)) {
            if ($event->image['private'] === false) {
                $event->add_button("Make Private", "privatize_image/".$event->image->id);
            } else {
                $event->add_button("Make Public", "publicize_image/".$event->image->id);
            }
        }
    }

    public function onImageAddition(ImageAdditionEvent $event): void
    {
        global $user, $user_config;
        if ($user_config->get_bool(PrivateImageConfig::USER_SET_DEFAULT) && $user->can(Permissions::SET_PRIVATE_IMAGE)) {
            self::privatize_image($event->image->id);
        }
    }

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event): void
    {
        global $user;

        if ($user->can(Permissions::SET_PRIVATE_IMAGE)) {
            $event->add_action("bulk_privatize_image", "Make Private");
            $event->add_action("bulk_publicize_image", "Make Public");
        }
    }

    public function onBulkAction(BulkActionEvent $event): void
    {
        global $page, $user;

        switch ($event->action) {
            case "bulk_privatize_image":
                if ($user->can(Permissions::SET_PRIVATE_IMAGE)) {
                    $total = 0;
                    foreach ($event->items as $image) {
                        if ($image->owner_id == $user->id ||
                            $user->can(Permissions::SET_OTHERS_PRIVATE_IMAGES)) {
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
                        $user->can(Permissions::SET_OTHERS_PRIVATE_IMAGES)) {
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

        if ($this->get_version(PrivateImageConfig::VERSION) < 1) {
            $database->execute("ALTER TABLE images ADD COLUMN private BOOLEAN NOT NULL DEFAULT FALSE");
            $database->execute("CREATE INDEX images_private_idx ON images(private)");
            $this->set_version(PrivateImageConfig::VERSION, 2);
        }
        if ($this->get_version(PrivateImageConfig::VERSION) < 2) {
            $database->standardise_boolean("images", "private");
            $this->set_version(PrivateImageConfig::VERSION, 2);
        }
    }
}
