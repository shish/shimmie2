<?php

declare(strict_types=1);

namespace Shimmie2;

class Blotter extends Extension
{
    /** @var BlotterTheme */
    protected Themelet $theme;

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_int("blotter_recent", 5);
        $config->set_default_string("blotter_color", "FF0000");
        $config->set_default_string("blotter_position", "subheading");
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
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
                ["text" => "Installed the blotter extension!", "important" => true]
            );
            log_info("blotter", "Installed tables for blotter extension.");
            $this->set_version("blotter_version", 2);
        }
        if ($this->get_version("blotter_version") < 2) {
            $database->standardise_boolean("blotter", "important");
            $this->set_version("blotter_version", 2);
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Blotter");
        $sb->add_int_option("blotter_recent", "<br />Number of recent entries to display: ");
        $sb->add_text_option("blotter_color", "<br />Color of important updates: (ABCDEF format) ");
        $sb->add_choice_option("blotter_position", ["Top of page" => "subheading", "In navigation bar" => "left"], "<br>Position: ");
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        global $user;
        if ($event->parent === "system") {
            if ($user->can(Permissions::BLOTTER_ADMIN)) {
                $event->add_nav_link("blotter", new Link('blotter/editor'), "Blotter Editor");
            }
        }
    }


    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::BLOTTER_ADMIN)) {
            $event->add_link("Blotter Editor", make_link("blotter/editor"));
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $database, $user;
        if ($event->page_matches("blotter/editor", method: "GET", permission: Permissions::BLOTTER_ADMIN)) {
            $entries = $database->get_all("SELECT * FROM blotter ORDER BY id DESC");
            $this->theme->display_editor($entries);
        }
        if ($event->page_matches("blotter/add", method: "POST", permission: Permissions::BLOTTER_ADMIN)) {
            $entry_text = $event->req_POST('entry_text');
            $important = !is_null($event->get_POST('important'));
            // Now insert into db:
            $database->execute(
                "INSERT INTO blotter (entry_date, entry_text, important) VALUES (now(), :text, :important)",
                ["text" => $entry_text, "important" => $important]
            );
            log_info("blotter", "Added Message: $entry_text");
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("blotter/editor"));
        }
        if ($event->page_matches("blotter/remove", method: "POST", permission: Permissions::BLOTTER_ADMIN)) {
            $id = int_escape($event->req_POST('id'));
            $database->execute("DELETE FROM blotter WHERE id=:id", ["id" => $id]);
            log_info("blotter", "Removed Entry #$id");
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("blotter/editor"));
        }
        if ($event->page_matches("blotter/list", method: "GET")) {
            $entries = $database->get_all("SELECT * FROM blotter ORDER BY id DESC");
            $this->theme->display_blotter_page($entries);
        }
        /**
         * Finally, display the blotter on whatever page we're viewing.
         */
        $this->display_blotter();
    }

    private function display_blotter(): void
    {
        global $database, $config;
        $entries = $database->get_all(
            'SELECT * FROM blotter ORDER BY id DESC LIMIT :limit',
            ["limit" => $config->get_int("blotter_recent", 5)]
        );
        $this->theme->display_blotter($entries);
    }
}
