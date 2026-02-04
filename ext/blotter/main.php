<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * @phpstan-type BlotterEntry array{id:int,entry_date:string,entry_text:string,important:bool}
 * @extends Extension<BlotterTheme>
 */
final class Blotter extends Extension
{
    public const KEY = "blotter";
    public const VERSION_KEY = "blotter_version";

    /** @var BlotterTheme */
    protected Themelet $theme;

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        $database = Ctx::$database;

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
        if ($event->parent === "system") {
            if (Ctx::$user->can(BlotterPermission::ADMIN)) {
                $event->add_nav_link(make_link('blotter/editor'), "Blotter Editor", "blotter_editor");
            }
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        $database = Ctx::$database;
        if ($event->page_matches("blotter/editor", method: "GET", permission: BlotterPermission::ADMIN)) {
            /** @var BlotterEntry[] $entries */
            $entries = $database->get_all("SELECT * FROM blotter ORDER BY id DESC");
            $this->theme->display_editor($entries);
        }
        if ($event->page_matches("blotter/add", method: "POST", permission: BlotterPermission::ADMIN)) {
            $entry_text = $event->POST->req('entry_text');
            $important = !is_null($event->POST->get('important'));
            // Now insert into db:
            $database->execute(
                "INSERT INTO blotter (entry_date, entry_text, important) VALUES (now(), :text, :important)",
                ["text" => $entry_text, "important" => $important]
            );
            Log::info("blotter", "Added Message: $entry_text");
            Ctx::$page->set_redirect(make_link("blotter/editor"));
        }
        if ($event->page_matches("blotter/remove", method: "POST", permission: BlotterPermission::ADMIN)) {
            $id = int_escape($event->POST->req('id'));
            $database->execute("DELETE FROM blotter WHERE id=:id", ["id" => $id]);
            Log::info("blotter", "Removed Entry #$id");
            Ctx::$page->set_redirect(make_link("blotter/editor"));
        }
        if ($event->page_matches("blotter/list", method: "GET")) {
            /** @var BlotterEntry[] $entries */
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
        /** @var BlotterEntry[] $entries */
        $entries = Ctx::$database->get_all(
            'SELECT * FROM blotter ORDER BY id DESC LIMIT :limit',
            ["limit" => Ctx::$config->get(BlotterConfig::RECENT)]
        );
        $this->theme->display_blotter($entries);
    }
}
