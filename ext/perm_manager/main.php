<?php

declare(strict_types=1);

namespace Shimmie2;

final class PermManager extends Extension
{
    public const KEY = "perm_manager";
    /** @var PermManagerTheme */
    protected Themelet $theme;

    public function onInitExt(InitExtEvent $event): void
    {
        $_all_false = [];
        $_all_true = [];
        foreach (PermissionGroup::get_subclasses(all: true) as $class) {
            foreach ($class->getConstants() as $k => $v) {
                assert(is_string($v));
                $_all_false[$v] = false;
                $_all_true[$v] = true;
            }
        }
        new UserClass("base", null, $_all_false);
        new UserClass("admin", null, $_all_true);

        // Ghost users can log in and do read-only stuff
        // with their own account, but no writing
        new UserClass("ghost", "base", [
            PrivMsgPermission::READ_PM => true,
        ]);

        // Anonymous users can't do anything except sign
        // up to become regular users
        new UserClass("anonymous", "base", [
            UserAccountsPermission::CREATE_USER => true,
        ]);

        // Users can control themselves, upload new content,
        // and do basic edits (tags, source, title) on other
        // people's content
        new UserClass("user", "base", [
            IndexPermission::BIG_SEARCH => true,
            ImagePermission::CREATE_IMAGE => true,
            CommentPermission::CREATE_COMMENT => true,
            PostTagsPermission::EDIT_IMAGE_TAG => true,
            PostSourcePermission::EDIT_IMAGE_SOURCE => true,
            PostTitlesPermission::EDIT_IMAGE_TITLE => true,
            RelationshipsPermission::EDIT_IMAGE_RELATIONSHIPS => true,
            ArtistsPermission::EDIT_IMAGE_ARTIST => true,
            ReportImagePermission::CREATE_IMAGE_REPORT => true,
            RatingsPermission::EDIT_IMAGE_RATING => true,
            FavouritesPermission::EDIT_FAVOURITES => true,
            NumericScorePermission::CREATE_VOTE => true,
            PrivMsgPermission::SEND_PM => true,
            PrivMsgPermission::READ_PM => true,
            PrivateImagePermission::SET_PRIVATE_IMAGE => true,
            BulkActionsPermission::PERFORM_BULK_ACTIONS => true,
            BulkDownloadPermission::BULK_DOWNLOAD => true,
            UserAccountsPermission::CHANGE_USER_SETTING => true,
            ForumPermission::FORUM_CREATE => true,
            NotesPermission::CREATE => true,
            NotesPermission::EDIT => true,
            NotesPermission::REQUEST => true,
            PoolsPermission::CREATE => true,
            PoolsPermission::UPDATE => true,
        ]);

        @include_once "data/config/user-classes.conf.php";
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page;

        if ($event->page_matches("perm_manager", method: "GET")) {
            $permissions = [];
            foreach (PermissionGroup::get_subclasses() as $class) {
                $group = $class->newInstance();
                if (!$group::is_enabled()) {
                    continue;
                }
                foreach ($class->getConstants() as $const => $key) {
                    $refl_const = $class->getReflectionConstant($const);
                    if (!$refl_const) {
                        continue;
                    }
                    $attributes = $refl_const->getAttributes(PermissionMeta::class);
                    if (count($attributes) == 0) {
                        continue;
                    }
                    $meta = $attributes[0]->newInstance();
                    $permissions[$key] = $meta;
                }
            }
            $this->theme->display_user_classes(
                UserClass::$known_classes,
                $permissions
            );
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        global $user;
        if ($event->parent === "system") {
            if ($user->can(PermManagerPermission::MANAGE_USER_PERMISSIONS)) {
                $event->add_nav_link(make_link('perm_manager'), "Permission Manager");
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        global $user;
        if ($user->can(PermManagerPermission::MANAGE_USER_PERMISSIONS)) {
            $event->add_link("Permission Manager", make_link("perm_manager"), 88);
        }
    }

}
