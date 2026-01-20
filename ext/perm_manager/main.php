<?php

declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<PermManagerTheme> */
final class PermManager extends Extension
{
    public const KEY = "perm_manager";

    public function onInitExt(InitExtEvent $event): void
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
        // Overview
        if ($event->page_matches("perm_manager", method: "GET")) {
            Ctx::$page->set_title("Permission Manager");
            $this->theme->display_navigation();
            $this->theme->display_user_classes(
                UserClass::$known_classes,
                PermissionGroup::get_all_metas_grouped(),
            );
            if (Ctx::$user->can(PermManagerPermission::MANAGE_USER_PERMISSIONS)) {
                $counts = Ctx::$database->get_pairs("SELECT class, COUNT(*) FROM users GROUP BY class");
                $this->theme->display_create_delete(UserClass::$known_classes, $counts);
            }
        }

        // Create from scratch
        if ($event->page_matches("user_class", method: "POST", permission: PermManagerPermission::MANAGE_USER_PERMISSIONS)) {
            $class_name = $event->POST->req("name");
            $parent = $event->POST->req("parent");
            $description = $event->POST->req("description");
            Ctx::$database->execute(
                "INSERT INTO user_classes (name, parent, description) VALUES (:name, :parent, :description)",
                ["name" => $class_name, "parent" => $parent, "description" => $description]
            );
            Ctx::$page->flash("User Class $class_name created");
            Ctx::$page->set_redirect(make_link("user_class/$class_name"));
        }

        // Create from existing
        // (copy settings from defaults into the DB)
        if ($event->page_matches("user_class/{class}/migrate", method: "POST", permission: PermManagerPermission::MANAGE_USER_PERMISSIONS)) {
            $class_name = $event->get_arg("class");
            $class = UserClass::$known_classes[$class_name];
            $parent = $class->get_parent();
            assert($parent !== null);
            Ctx::$database->execute(
                "INSERT INTO user_classes (name, parent, description) VALUES (:name, :parent, :description)",
                ["name" => $class->name, "parent" => $parent->name, "description" => $class->description]
            );
            $class_id = Ctx::$database->get_one(
                "SELECT id FROM user_classes WHERE name = :class_name",
                ["class_name" => $class_name]
            );
            assert($class_id !== null);
            foreach (PermissionGroup::get_all_metas_grouped() as $ext => $group) {
                foreach ($group as $perm => $meta) {
                    if ($class->has_own_permission($perm)) {
                        Ctx::$database->execute(
                            "INSERT INTO user_class_permissions (user_class_id, permission, value) VALUES (:id, :permission, :value)",
                            ["id" => $class_id, "permission" => $perm, "value" => $class->can($perm)]
                        );
                    }
                }
            }
            Ctx::$page->flash("Migrated '$class_name' to database");
            Ctx::$page->set_redirect(make_link("user_class/$class_name"));
        }

        // Read
        if ($event->page_matches("user_class/{class}", method: "GET", permission: PermManagerPermission::MANAGE_USER_PERMISSIONS)) {
            $class_name = $event->get_arg("class");
            $class = UserClass::$known_classes[$class_name];
            Ctx::$page->set_title("Permission Manager");
            $this->theme->display_navigation();
            $this->theme->display_edit_class($class);
            $this->theme->display_edit_permissions($class, PermissionGroup::get_all_metas_grouped());
        }

        // Update (General)
        if ($event->page_matches("user_class/{class}", method: "POST", permission: PermManagerPermission::MANAGE_USER_PERMISSIONS)) {
            $class_name = $event->get_arg("class");
            $parent = $event->POST->req("parent");
            $description = $event->POST->req("description");
            Ctx::$database->execute(
                "UPDATE user_classes SET parent = :parent, description = :description WHERE name = :name",
                ["name" => $class_name, "parent" => $parent, "description" => $description]
            );
            Ctx::$page->flash("Updated settings for '$class_name'");
            Ctx::$page->set_redirect(make_link("user_class/$class_name"));
        }

        // Update (Permissions)
        if ($event->page_matches("user_class/{class}/permissions", method: "POST", permission: PermManagerPermission::MANAGE_USER_PERMISSIONS)) {
            $class_name = $event->get_arg("class");
            $class_id = Ctx::$database->get_one("SELECT id FROM user_classes WHERE name = :class_name", ["class_name" => $class_name]);
            assert($class_id !== null);
            $perms = $event->POST->getAll("permissions");
            Ctx::$database->execute(
                "DELETE FROM user_class_permissions WHERE user_class_id = :id",
                ["id" => $class_id]
            );
            foreach ($perms as $perm => $value) {
                $sql_value = match($value) {
                    "null" => null,
                    "true" => true,
                    "false" => false,
                    default => throw new UserError("Invalid value for permission '$perm': $value"),
                };
                if ($sql_value !== null) {
                    Ctx::$database->execute(
                        "INSERT INTO user_class_permissions (user_class_id, permission, value) VALUES (:id, :permission, :value)",
                        ["id" => $class_id, "permission" => $perm, "value" => $sql_value]
                    );
                }
            }
            Ctx::$page->flash("Updated permissions for '$class_name'");
            Ctx::$page->set_redirect(make_link("user_class/$class_name"));
        }

        // Delete
        if ($event->page_matches("user_class/{class}/delete", method: "POST", permission: PermManagerPermission::MANAGE_USER_PERMISSIONS)) {
            $class_name = $event->get_arg("class");
            Ctx::$database->execute("DELETE FROM user_classes WHERE name = :name", ["name" => $class_name]);
            Ctx::$page->flash("Deleted '$class_name'");
            Ctx::$page->set_redirect(Url::referer_or());
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "system") {
            if (Ctx::$user->can(PermManagerPermission::MANAGE_USER_PERMISSIONS)) {
                $event->add_nav_link(make_link('perm_manager'), "Permission Manager", "perm_manager");
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
