<?php

declare(strict_types=1);

namespace Shimmie2;

class PermManager extends Extension
{
    /** @var PermManagerTheme */
    protected Themelet $theme;

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        // don't attempt to load classes until a database upgrade has been performed
        // this allows the permissions table to be created/upgraded before we begin using it
        if($this->get_version("ext_perm_version") < 1) {
            $this->set_version("ext_perm_version", 1);
            UserClass::loadClasses();
        }
    }

    public function __construct()
    {
        parent::__construct();
        if($this->get_version("ext_perm_version") >= 1) {
            UserClass::loadClasses();
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user;
        if ($event->page_matches("perm_manager/{class}/set_parent", method: "POST", permission: Permissions::MANAGE_PERMISSION_LIST)) {
            $class = $event->get_arg('class');
            $parent = only_strings($event->POST)["parent"];
            $this->set_parent($class, $parent);
            $log = "Changed parent of \"$class\" to \"$parent\"";
            log_warning("perm_manager", $log, $log);
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("perm_manager"));
        } elseif ($event->page_matches("perm_manager/{class}/set_perms", method: "POST", permission: Permissions::MANAGE_PERMISSION_LIST)) {
            $class = $event->get_arg('class');
            $this->set_permissions($class, only_strings($event->POST));
            $log = "Updated permissions of \"$class\"";
            log_warning("perm_manager", $log, $log);
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("perm_manager"));
        } elseif ($event->page_matches("perm_manager/{class}/delete", method: "POST", permission: Permissions::MANAGE_PERMISSION_LIST)) {
            $class = $event->get_arg('class');
            if ($class == only_strings($event->POST)["name"]) {
                $success = $this->remove_class($class);
                if ($success) {
                    $log = "Removed class \"$class\"";
                    log_warning("perm_manager", $log, $log);
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(make_link("perm_manager"));
                } else {
                    $this->theme->display_error(
                        422,
                        "Class deletion failed",
                        "Cannot delete a core class or a class which is a parent."
                    );
                }
            } else {
                $this->theme->display_error(
                    400,
                    "Class deletion failed",
                    "The class name did not match."
                );

            }
        } elseif ($event->page_matches("perm_manager/new", method: "POST", permission: Permissions::MANAGE_PERMISSION_LIST)) {
            $POST = only_strings($event->POST);
            $name = $POST["new_name"];
            $parent = $POST["new_parent"];
            $success = $this->create_class($name, $parent);
            if ($success) {
                $log = "Created class \"$name\"";
                log_warning("perm_manager", $log, $log);
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link("perm_manager/$name"));
            } else {
                $this->theme->display_error(
                    422,
                    "Class creation failed",
                    "The class name invalid."
                );

            }
        } elseif ($event->page_matches("perm_manager/{class}", method: "GET", permission: Permissions::MANAGE_PERMISSION_LIST)) {
            $class = $event->get_arg('class');
            $parent_options = $this->get_parent_options($class);
            $permissions = array_keys($this->get_class($class)->abilities);
            $this->theme->display_table($page, $this->is_parent($class), $parent_options, $permissions, $this->get_class($class));
        } elseif ($event->page_matches("perm_manager", method: "GET", permission: Permissions::MANAGE_PERMISSION_LIST)) {
            $this->theme->display_list($page, UserClass::$known_classes, $this->get_parent_options(""));
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        global $user;
        if ($event->parent === "system") {
            if ($user->can(Permissions::MANAGE_PERMISSION_LIST)) {
                $event->add_nav_link("perm_manager", new Link('perm_manager'), "Permission Manager");
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::MANAGE_EXTENSION_LIST)) {
            $event->add_link("Permission Manager", make_link("perm_manager"));
        }
    }

    /**
     * @return string[]
     */
    private function get_parent_options(string $class): array
    {
        $classes = array_keys(UserClass::$known_classes);
        // remove options greater or equal id to the current class
        // this prevents a situation on startup where this class trys to load a higher parent class before it exists
        if ($class) {
            while (array_pop($classes) != $class) {
                // do nothing
            }
        }
        $parent_options = [];
        foreach($classes as $c) {
            $parent_options[$c] = $c;
        }
        unset($parent_options["admin"]);
        return $parent_options;
    }

    private function is_parent(string $class): bool
    {
        foreach(UserClass::$known_classes as $c) {
            if ($c->parent && $c->parent->name == $class) {
                return true;
            }
        }
        return false;
    }

    private function get_class(string $class): UserClass
    {
        return UserClass::$known_classes[$class];
    }

    private function set_parent(string $class, string $parent): void
    {
        global $database;
        if (in_array($parent, $this->get_parent_options($class))) {
            $database->execute("UPDATE permissions SET parent=:parent WHERE class = :class", ["parent" => $parent, "class" => $class]);
            // reload all classes from database
            UserClass::loadClasses();
        }
    }

    /**
     * @param array<string,string> $POST
     */
    private function set_permissions(string $class, array $POST): void
    {
        global $database;

        $perm_list = array_keys(UserClass::$known_classes[$class]->abilities);
        $new_perms = [];
        foreach($perm_list as $k) {
            $new_perms[$k] = false;
        }

        foreach($POST as $k => $v) {
            if (str_starts_with($k, 'perm_') && array_key_exists(substr($k, 5), $new_perms)) {
                $new_perms[substr($k, 5)] = true;
            }
        }
        $perm_query = "";
        $first = true;
        foreach($perm_list as $k) {
            if (!$first) {
                $perm_query .= ",";
            } else {
                $first = false;
            }
            $perm_query .= "$k=:$k";
        }

        $new_perms["class"] = $class;
        $database->execute("UPDATE permissions SET ".$perm_query." WHERE class = :class", $new_perms);
        // reload all classes from database
        UserClass::loadClasses();
    }

    private function create_class(string $name, string $parent): bool
    {
        global $database;
        if ($name == "" || in_array($name, array_keys(UserClass::$known_classes))) {
            return false;
        }
        if (in_array($parent, $this->get_parent_options(""))) {
            $database->execute("INSERT INTO permissions (class, parent) VALUES (:class, :parent)", ["class" => $name, "parent" => $parent]);
            // reload all classes from database
            UserClass::loadClasses();
            return true;
        }
        return false;
    }

    private function remove_class(string $name): bool
    {
        global $database;
        // check if parent or a core class
        if (!$this->get_class($name)->core && !$this->is_parent($name)) {
            $database->execute("DELETE FROM permissions WHERE class=:class", ["class" => $name]);
            // reload all classes from database
            UserClass::loadClasses();
            return true;
        }
        return false;
    }
}
