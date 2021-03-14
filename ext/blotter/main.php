<?php declare(strict_types=1);

class Blotter extends Extension
{
    /** @var BlotterTheme */
    protected ?Themelet $theme;

    public function onInitExt(InitExtEvent $event)
    {
        global $config;
        $config->set_default_int("blotter_recent", 5);
        $config->set_default_string("blotter_color", "FF0000");
        $config->set_default_string("blotter_position", "subheading");
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event)
    {
        global $database;

        if ($this->get_version("blotter_version") < 1) {
            $database->create_table("blotter", "
                id SCORE_AIPK,
                entry_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                entry_text TEXT NOT NULL,
                important BOOLEAN NOT NULL DEFAULT FALSE
            ");
            // Insert sample data:
            $database->execute(
                "INSERT INTO blotter (entry_date, entry_text, important) VALUES (now(), :text, :important)",
                ["text"=>"Installed the blotter extension!", "important"=>true]
            );
            log_info("blotter", "Installed tables for blotter extension.");
            $this->set_version("blotter_version", 2);
        }
        if ($this->get_version("blotter_version") < 2) {
            $database->standardise_boolean("blotter", "important");
            $this->set_version("blotter_version", 2);
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = $event->panel->create_new_block("Blotter");
        $sb->add_int_option("blotter_recent", "<br />Number of recent entries to display: ");
        $sb->add_text_option("blotter_color", "<br />Color of important updates: (ABCDEF format) ");
        $sb->add_choice_option("blotter_position", ["Top of page" => "subheading", "In navigation bar" => "left"], "<br>Position: ");
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event)
    {
        global $user;
        if ($event->parent==="system") {
            if ($user->can(Permissions::BLOTTER_ADMIN)) {
                $event->add_nav_link("blotter", new Link('blotter/editor'), "Blotter Editor");
            }
        }
    }


    public function onUserBlockBuilding(UserBlockBuildingEvent $event)
    {
        global $user;
        if ($user->can(Permissions::BLOTTER_ADMIN)) {
            $event->add_link("Blotter Editor", make_link("blotter/editor"));
        }
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $database, $user;
        if ($event->page_matches("blotter") && $event->count_args() > 0) {
            switch ($event->get_arg(0)) {
                case "editor":
                    /**
                     * Displays the blotter editor.
                     */
                    if (!$user->can(Permissions::BLOTTER_ADMIN)) {
                        $this->theme->display_permission_denied();
                    } else {
                        $entries = $database->get_all("SELECT * FROM blotter ORDER BY id DESC");
                        $this->theme->display_editor($entries);
                    }
                    break;
                case "add":
                    /**
                     * Adds an entry
                     */
                    if (!$user->can(Permissions::BLOTTER_ADMIN) || !$user->check_auth_token()) {
                        $this->theme->display_permission_denied();
                    } else {
                        $entry_text = $_POST['entry_text'];
                        if ($entry_text == "") {
                            die("No entry message!");
                        }
                        $important = isset($_POST['important']);
                        // Now insert into db:
                        $database->execute(
                            "INSERT INTO blotter (entry_date, entry_text, important) VALUES (now(), :text, :important)",
                            ["text"=>$entry_text, "important"=>$important]
                        );
                        log_info("blotter", "Added Message: $entry_text");
                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(make_link("blotter/editor"));
                    }
                    break;
                case "remove":
                    /**
                     * Removes an entry
                     */
                    if (!$user->can(Permissions::BLOTTER_ADMIN) || !$user->check_auth_token()) {
                        $this->theme->display_permission_denied();
                    } else {
                        $id = int_escape($_POST['id']);
                        if (!isset($id)) {
                            die("No ID!");
                        }
                        $database->execute("DELETE FROM blotter WHERE id=:id", ["id"=>$id]);
                        log_info("blotter", "Removed Entry #$id");
                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(make_link("blotter/editor"));
                    }
                    break;
                case "list":
                    /**
                     * Displays all blotter entries
                     */
                    $entries = $database->get_all("SELECT * FROM blotter ORDER BY id DESC");
                    $this->theme->display_blotter_page($entries);
                    break;
            }
        }
        /**
         * Finally, display the blotter on whatever page we're viewing.
         */
        $this->display_blotter();
    }

    private function display_blotter()
    {
        global $database, $config;
        $limit = $config->get_int("blotter_recent", 5);
        $sql = 'SELECT * FROM blotter ORDER BY id DESC LIMIT '.intval($limit);
        $entries = $database->get_all($sql);
        $this->theme->display_blotter($entries);
    }
}
