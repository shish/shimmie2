<?php declare(strict_types=1);

use MicroCRUD\ActionColumn;
use MicroCRUD\TextColumn;
use MicroCRUD\Table;

class AliasTable extends Table
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
            new TextColumn("oldtag", "Old Tag"),
            new TextColumn("newtag", "New Tag"),
            new ActionColumn("oldtag"),
        ]);
        $this->order_by = ["oldtag"];
        $this->table_attrs = ["class" => "zebra"];
    }
}

class AddAliasEvent extends Event
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

class DeleteAliasEvent extends Event
{
    public string $oldtag;

    public function __construct(string $oldtag)
    {
        parent::__construct();
        $this->oldtag = $oldtag;
    }
}

class AddAliasException extends SCoreException
{
}

class AliasEditor extends Extension
{
    /** @var AliasEditorTheme */
    protected ?Themelet $theme;

    public function onPageRequest(PageRequestEvent $event)
    {
        global $config, $database, $page, $user;

        if ($event->page_matches("alias")) {
            if ($event->get_arg(0) == "add") {
                if ($user->can(Permissions::MANAGE_ALIAS_LIST)) {
                    $user->ensure_authed();
                    $input = validate_input(["c_oldtag"=>"string", "c_newtag"=>"string"]);
                    try {
                        send_event(new AddAliasEvent($input['c_oldtag'], $input['c_newtag']));
                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(make_link("alias/list"));
                    } catch (AddAliasException $ex) {
                        $this->theme->display_error(500, "Error adding alias", $ex->getMessage());
                    }
                }
            } elseif ($event->get_arg(0) == "remove") {
                if ($user->can(Permissions::MANAGE_ALIAS_LIST)) {
                    $user->ensure_authed();
                    $input = validate_input(["d_oldtag"=>"string"]);
                    send_event(new DeleteAliasEvent($input['d_oldtag']));
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(make_link("alias/list"));
                }
            } elseif ($event->get_arg(0) == "list") {
                $t = new AliasTable($database->raw_db());
                $t->token = $user->get_auth_token();
                $t->inputs = $_GET;
                $t->size = $config->get_int('alias_items_per_page', 30);
                if ($user->can(Permissions::MANAGE_ALIAS_LIST)) {
                    $t->create_url = make_link("alias/add");
                    $t->delete_url = make_link("alias/remove");
                }
                $this->theme->display_aliases($t->table($t->query()), $t->paginator());
            } elseif ($event->get_arg(0) == "export") {
                $page->set_mode(PageMode::DATA);
                $page->set_mime(MimeType::CSV);
                $page->set_filename("aliases.csv");
                $page->set_data($this->get_alias_csv($database));
            } elseif ($event->get_arg(0) == "import") {
                if ($user->can(Permissions::MANAGE_ALIAS_LIST)) {
                    if (count($_FILES) > 0) {
                        $tmp = $_FILES['alias_file']['tmp_name'];
                        $contents = file_get_contents($tmp);
                        $this->add_alias_csv($database, $contents);
                        log_info("alias_editor", "Imported aliases from file", "Imported aliases"); # FIXME: how many?
                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(make_link("alias/list"));
                    } else {
                        $this->theme->display_error(400, "No File Specified", "You have to upload a file");
                    }
                } else {
                    $this->theme->display_error(401, "Admins Only", "Only admins can edit the alias list");
                }
            }
        }
    }

    public function onAddAlias(AddAliasEvent $event)
    {
        global $database;

        $row = $database->get_row(
            "SELECT * FROM aliases WHERE lower(oldtag)=lower(:oldtag)",
            ["oldtag"=>$event->oldtag]
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
        log_info("alias_editor", "Added alias for {$event->oldtag} -> {$event->newtag}", "Added alias");
    }

    public function onDeleteAlias(DeleteAliasEvent $event)
    {
        global $database;
        $database->execute("DELETE FROM aliases WHERE oldtag=:oldtag", ["oldtag" => $event->oldtag]);
        log_info("alias_editor", "Deleted alias for {$event->oldtag}", "Deleted alias");
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event)
    {
        if ($event->parent=="tags") {
            $event->add_nav_link("aliases", new Link('alias/list'), "Aliases", NavLink::is_active(["alias"]));
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event)
    {
        global $user;
        if ($user->can(Permissions::MANAGE_ALIAS_LIST)) {
            $event->add_link("Alias Editor", make_link("alias/list"));
        }
    }

    private function get_alias_csv(Database $database): string
    {
        $csv = "";
        $aliases = $database->get_pairs("SELECT oldtag, newtag FROM aliases ORDER BY newtag");
        foreach ($aliases as $old => $new) {
            $csv .= "\"$old\",\"$new\"\n";
        }
        return $csv;
    }

    private function add_alias_csv(Database $database, string $csv): int
    {
        $csv = str_replace("\r", "\n", $csv);
        $i = 0;
        foreach (explode("\n", $csv) as $line) {
            $parts = str_getcsv($line);
            if (count($parts) == 2) {
                try {
                    send_event(new AddAliasEvent($parts[0], $parts[1]));
                    $i++;
                } catch (AddAliasException $ex) {
                    $this->theme->display_error(500, "Error adding alias", $ex->getMessage());
                }
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
