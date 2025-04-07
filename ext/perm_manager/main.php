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
        $this->add_database_classes();
    }

    private function add_database_classes(): void
    {
        UserClass::$loading = UserClassSource::DATABASE;
        if ($this->get_version() >= 1) {
            foreach (Ctx::$database->get_all("SELECT * FROM user_classes") as $class_row) {
                $name = $class_row['name'];
                $parent = $class_row['parent'];
                $permissions = array_map(fn ($val) => bool_escape($val), Ctx::$database->get_pairs(
                    "SELECT permission, value FROM user_class_permissions WHERE user_class_id = :id",
                    ["id" => $class_row['id']]
                ));
                new UserClass(
                    $name,
                    $parent,
                    $permissions,
                    $class_row['description'],
                );
            }
        }
        UserClass::$loading = UserClassSource::UNKNOWN;
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        // Note - `users` should _not_ have a foreign key to `user_classes` because
        // there can be classes that are outside of the database.
        if ($this->get_version() < 1) {
            $database->create_table("user_classes", "
                id SCORE_AIPK,
                name VARCHAR(32) NOT NULL UNIQUE,
                parent VARCHAR(32) NOT NULL,
                description TEXT NOT NULL
            ");
            $database->create_table("user_class_permissions", "
                id SCORE_AIPK,
                user_class_id INTEGER NOT NULL,
                permission VARCHAR(32) NOT NULL,
                value BOOLEAN NOT NULL,
                UNIQUE(user_class_id, permission),
                FOREIGN KEY (user_class_id) REFERENCES user_classes(id) ON DELETE CASCADE
            ");
            $database->execute("CREATE INDEX user_class_permissions__user_class_id ON user_class_permissions(user_class_id)");
            $this->set_version(1);
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("perm_manager", method: "GET")) {
            $this->theme->display_user_classes(
                UserClass::$known_classes,
                PermissionGroup::get_all_metas_grouped(),
            );
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "system") {
            if (Ctx::$user->can(PermManagerPermission::MANAGE_USER_PERMISSIONS)) {
                $event->add_nav_link(make_link('perm_manager'), "Permission Manager");
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        if (Ctx::$user->can(PermManagerPermission::MANAGE_USER_PERMISSIONS)) {
            $event->add_link("Permission Manager", make_link("perm_manager"), 88);
        }
    }

    public function get_priority(): int
    {
        return 70; // After `user` loads default and `user_class_file` loads from file
    }
}
