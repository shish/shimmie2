<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroCRUD\{ActionColumn, Table};

final class AliasTable extends Table
{
    public function __construct(\FFSPHP\PDO $db)
    {
        parent::__construct($db);
        $this->table = "aliases";
        $this->base_query = "SELECT * FROM aliases";
        $this->primary_key = "oldtag";
        $this->size = 100;
        $this->limit = 1000000;
        $this->set_columns([
            new AutoCompleteColumn("oldtag", "Old Tag"),
            new AutoCompleteColumn("newtag", "New Tag"),
            new ActionColumn("oldtag"),
        ]);
        $this->order_by = ["oldtag"];
        $this->table_attrs = ["class" => "zebra form"];
    }
}

final class AddAliasEvent extends Event
{
    public string $oldtag;
    public string $newtag;

    public function __construct(string $oldtag, string $newtag)
    {
        parent::__construct();
        $this->oldtag = trim($oldtag);
        $this->newtag = trim($newtag);
    }
}

final class DeleteAliasEvent extends Event
{
    public function __construct(
        public string $oldtag
    ) {
        parent::__construct();
    }
}

final class AddAliasException extends UserError
{
}

/** @extends Extension<AliasEditorTheme> */
final class AliasEditor extends Extension
{
    public const KEY = "alias_editor";

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $database;
        $page = Ctx::$page;

        if ($event->page_matches("alias/add", method: "POST", permission: AliasEditorPermission::MANAGE_ALIAS_LIST)) {
            send_event(new AddAliasEvent($event->POST->req('c_oldtag'), $event->POST->req('c_newtag')));
            $page->set_redirect(make_link("alias/list"));
        }
        if ($event->page_matches("alias/remove", method: "POST", permission: AliasEditorPermission::MANAGE_ALIAS_LIST)) {
            send_event(new DeleteAliasEvent($event->POST->req('d_oldtag')));
            $page->set_redirect(make_link("alias/list"));
        }
        if ($event->page_matches("alias/list")) {
            $t = new AliasTable($database->raw_db());
            $t->token = Ctx::$user->get_auth_token();
            $t->inputs = $event->GET->toArray();
            $t->size = 100;
            if (Ctx::$user->can(AliasEditorPermission::MANAGE_ALIAS_LIST)) {
                $t->create_url = make_link("alias/add");
                $t->delete_url = make_link("alias/remove");
            }
            $this->theme->display_aliases($t->table($t->query()), $t->paginator());
        }
        if ($event->page_matches("alias/export/aliases.csv")) {
            $page->set_data(MimeType::CSV, $this->get_alias_csv($database), filename: "aliases.csv");
        }
        if ($event->page_matches("alias/import", method: "POST", permission: AliasEditorPermission::MANAGE_ALIAS_LIST)) {
            if (count($_FILES) > 0) {
                $tmp = $_FILES['alias_file']['tmp_name'];
                $contents = \Safe\file_get_contents($tmp);
                $this->add_alias_csv($contents);
                Log::info("alias_editor", "Imported aliases from file", "Imported aliases"); # FIXME: how many?
                $page->set_redirect(make_link("alias/list"));
            } else {
                throw new InvalidInput("No File Specified");
            }
        }
    }

    public function onAddAlias(AddAliasEvent $event): void
    {
        global $database;

        $row = $database->get_row(
            "SELECT * FROM aliases WHERE lower(oldtag)=lower(:oldtag)",
            ["oldtag" => $event->oldtag]
        );
        if ($row) {
            throw new AddAliasException("{$row['oldtag']} is already an alias for {$row['newtag']}");
        }

        $row = $database->get_row(
            "SELECT * FROM aliases WHERE lower(oldtag)=lower(:newtag)",
            ["newtag" => $event->newtag]
        );
        if ($row) {
            throw new AddAliasException("{$row['oldtag']} is itself an alias for {$row['newtag']}");
        }

        $database->execute(
            "INSERT INTO aliases(oldtag, newtag) VALUES(:oldtag, :newtag)",
            ["oldtag" => $event->oldtag, "newtag" => $event->newtag]
        );
        Log::info("alias_editor", "Added alias for {$event->oldtag} -> {$event->newtag}", "Added alias");
    }

    public function onDeleteAlias(DeleteAliasEvent $event): void
    {
        global $database;
        $database->execute("DELETE FROM aliases WHERE oldtag=:oldtag", ["oldtag" => $event->oldtag]);
        Log::info("alias_editor", "Deleted alias for {$event->oldtag}", "Deleted alias");
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "tags") {
            $event->add_nav_link(make_link('alias/list'), "Aliases", "aliases", ["alias"]);
        }
    }

    private function get_alias_csv(Database $database): string
    {
        $csv = "";
        $aliases = $database->get_pairs("SELECT oldtag, newtag FROM aliases ORDER BY newtag");
        foreach ($aliases as $old => $new) {
            assert(is_string($new));
            $csv .= "\"$old\",\"$new\"\n";
        }
        return $csv;
    }

    private function add_alias_csv(string $csv): int
    {
        $csv = str_replace("\r", "\n", $csv);
        $i = 0;
        foreach (explode("\n", $csv) as $line) {
            $parts = str_getcsv($line);
            if (count($parts) === 2) {
                assert(is_string($parts[0]));
                assert(is_string($parts[1]));
                send_event(new AddAliasEvent($parts[0], $parts[1]));
                $i++;
            }
        }
        return $i;
    }

    /**
     * Get the priority for this extension.
     *
     * Add alias *after* mass tag editing, else the MTE will
     * search for the images and be redirected to the alias,
     * missing out the images tagged with the old tag.
     */
    public function get_priority(): int
    {
        return 60;
    }
}
