<?php

declare(strict_types=1);

namespace Shimmie2;

final class Blotter extends Extension
{
    public const KEY = "blotter";
    public const VERSION_KEY = "blotter_version";

    /** @var BlotterTheme */
    protected Themelet $theme;

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        if ($this->get_version() < 1) {
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
            Log::info("blotter", "Installed tables for blotter extension.");
            $this->set_version(2);
        }
        if ($this->get_version() < 2) {
            $database->standardise_boolean("blotter", "important");
            $this->set_version(2);
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        global $user;
        if ($event->parent === "system") {
            if ($user->can(BlotterPermission::ADMIN)) {
                $event->add_nav_link(make_link('blotter/editor'), "Blotter Editor");
            }
        }
    }


    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        global $user;
        if ($user->can(BlotterPermission::ADMIN)) {
            $event->add_link("Blotter Editor", make_link("blotter/editor"));
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $database, $user;
        if ($event->page_matches("blotter/editor", method: "GET", permission: BlotterPermission::ADMIN)) {
            $entries = $database->get_all("SELECT * FROM blotter ORDER BY id DESC");
            $this->theme->display_editor($entries);
        }
        if ($event->page_matches("blotter/add", method: "POST", permission: BlotterPermission::ADMIN)) {
            $entry_text = $event->req_POST('entry_text');
            $important = !is_null($event->get_POST('important'));
            // Now insert into db:
            $database->execute(
                "INSERT INTO blotter (entry_date, entry_text, important) VALUES (now(), :text, :important)",
                ["text" => $entry_text, "important" => $important]
            );
            Log::info("blotter", "Added Message: $entry_text");
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("blotter/editor"));
        }
        if ($event->page_matches("blotter/remove", method: "POST", permission: BlotterPermission::ADMIN)) {
            $id = int_escape($event->req_POST('id'));
            $database->execute("DELETE FROM blotter WHERE id=:id", ["id" => $id]);
            Log::info("blotter", "Removed Entry #$id");
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
            ["limit" => $config->get_int(BlotterConfig::RECENT)]
        );
        $this->theme->display_blotter($entries);
    }
}
