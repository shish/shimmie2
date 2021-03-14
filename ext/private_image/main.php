<?php declare(strict_types=1);

abstract class PrivateImageConfig
{
    const VERSION = "ext_private_image_version";
    const USER_SET_DEFAULT = "user_private_image_set_default";
    const USER_VIEW_DEFAULT = "user_private_image_view_default";
}

class PrivateImage extends Extension
{
    /** @var PrivateImageTheme */
    protected ?Themelet $theme;

    public function onInitExt(InitExtEvent $event)
    {
        Image::$bool_props[] = "private ";
    }

    public function onInitUserConfig(InitUserConfigEvent $event)
    {
        $event->user_config->set_default_bool(PrivateImageConfig::USER_SET_DEFAULT, false);
        $event->user_config->set_default_bool(PrivateImageConfig::USER_VIEW_DEFAULT, true);
    }

    public function onUserOptionsBuilding(UserOptionsBuildingEvent $event)
    {
        $sb = $event->panel->create_new_block("Private Posts");
        $sb->start_table();
        $sb->add_bool_option(PrivateImageConfig::USER_SET_DEFAULT, "Mark posts private by default", true);
        $sb->add_bool_option(PrivateImageConfig::USER_VIEW_DEFAULT, "View private posts by default", true);
        $sb->end_table();
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $user, $user_config;

        if ($event->page_matches("privatize_image") && $user->can(Permissions::SET_PRIVATE_IMAGE)) {
            // Try to get the image ID
            $image_id = int_escape($event->get_arg(0));
            if (empty($image_id)) {
                $image_id = isset($_POST['image_id']) ? $_POST['image_id'] : null;
            }
            if (empty($image_id)) {
                throw new SCoreException("Can not make image private: No valid Post ID given.");
            }
            $image = Image::by_id($image_id);
            if ($image==null) {
                throw new SCoreException("Post not found.");
            }
            if ($image->owner_id!=$user->can(Permissions::SET_OTHERS_PRIVATE_IMAGES)) {
                throw new SCoreException("Cannot set another user's image to private.");
            }

            self::privatize_image($image_id);
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/" . $image_id));
        }

        if ($event->page_matches("publicize_image")) {
            // Try to get the image ID
            $image_id = int_escape($event->get_arg(0));
            if (empty($image_id)) {
                $image_id = isset($_POST['image_id']) ? $_POST['image_id'] : null;
            }
            if (empty($image_id)) {
                throw new SCoreException("Can not make image public: No valid Post ID given.");
            }
            $image = Image::by_id($image_id);
            if ($image==null) {
                throw new SCoreException("Post not found.");
            }
            if ($image->owner_id!=$user->can(Permissions::SET_OTHERS_PRIVATE_IMAGES)) {
                throw new SCoreException("Cannot set another user's image to private.");
            }

            self::publicize_image($image_id);
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/".$image_id));
        }

        if ($event->page_matches("user_admin")) {
            if (!$user->check_auth_token()) {
                return;
            }
            switch ($event->get_arg(0)) {
                case "private_image":
                    if (!array_key_exists("id", $_POST) || empty($_POST["id"])) {
                        return;
                    }
                    $id = intval($_POST["id"]);
                    if ($id != $user->id) {
                        throw new SCoreException("Cannot change another user's settings");
                    }
                    $set_default = array_key_exists("set_default", $_POST);
                    $view_default = array_key_exists("view_default", $_POST);

                    $user_config->set_bool(PrivateImageConfig::USER_SET_DEFAULT, $set_default);
                    $user_config->set_bool(PrivateImageConfig::USER_VIEW_DEFAULT, $view_default);

                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(make_link("user"));

                    break;
            }
        }
    }
    public function onDisplayingImage(DisplayingImageEvent $event)
    {
        global $user, $page;

        if ($event->image->private===true && $event->image->owner_id!=$user->id && !$user->can(Permissions::SET_OTHERS_PRIVATE_IMAGES)) {
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/list"));
        }
    }


    const SEARCH_REGEXP = "/^private:(yes|no|any)/";
    public function onSearchTermParse(SearchTermParseEvent $event)
    {
        global $user, $user_config;
        $show_private = $user_config->get_bool(PrivateImageConfig::USER_VIEW_DEFAULT);

        $matches = [];

        if (is_null($event->term) && $this->no_private_query($event->context)) {
            if ($show_private) {
                $event->add_querylet(
                    new Querylet(
                        "private != :true OR owner_id = :private_owner_id",
                        ["private_owner_id"=>$user->id, "true"=>true]
                    )
                );
            } else {
                $event->add_querylet(
                    new Querylet("private != :true", ["true"=>true])
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

    public function onHelpPageBuilding(HelpPageBuildingEvent $event)
    {
        if ($event->key===HelpPages::SEARCH) {
            $block = new Block();
            $block->header = "Private Posts";
            $block->body = $this->theme->get_help_html();
            $event->add_block($block);
        }
    }


    private function no_private_query(array $context): bool
    {
        foreach ($context as $term) {
            if (preg_match(self::SEARCH_REGEXP, $term)) {
                return false;
            }
        }
        return true;
    }

    public static function privatize_image($image_id)
    {
        global $database;

        $database->execute(
            "UPDATE images SET private = :true WHERE id = :id AND private = :false",
            ["id"=>$image_id, "true"=>true, "false"=>false]
        );
    }

    public static function publicize_image($image_id)
    {
        global $database;

        $database->execute(
            "UPDATE images SET private = :false WHERE id = :id AND private = :true",
            ["id"=>$image_id, "true"=>true, "false"=>false]
        );
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event)
    {
        global $user;
        if ($user->can(Permissions::SET_PRIVATE_IMAGE) && $user->id==$event->image->owner_id) {
            $event->add_part($this->theme->get_image_admin_html($event->image));
        }
    }

    public function onImageAddition(ImageAdditionEvent $event)
    {
        global $user_config;
        if ($user_config->get_bool(PrivateImageConfig::USER_SET_DEFAULT)) {
            self::privatize_image($event->image->id);
        }
    }

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event)
    {
        global $user;

        if ($user->can(Permissions::SET_PRIVATE_IMAGE)) {
            $event->add_action("bulk_privatize_image", "Make Private");
            $event->add_action("bulk_publicize_image", "Make Public");
        }
    }

    public function onBulkAction(BulkActionEvent $event)
    {
        global $page, $user;

        switch ($event->action) {
            case "bulk_privatize_image":
                if ($user->can(Permissions::SET_PRIVATE_IMAGE)) {
                    $total = 0;
                    foreach ($event->items as $image) {
                        if ($image->owner_id==$user->id ||
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
                    if ($image->owner_id==$user->id ||
                        $user->can(Permissions::SET_OTHERS_PRIVATE_IMAGES)) {
                        self::publicize_image($image->id);
                        $total++;
                    }
                }
                $page->flash("Made $total items public");
                break;
        }
    }
    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event)
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
