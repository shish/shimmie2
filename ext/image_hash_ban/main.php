<?php declare(strict_types=1);

use MicroCRUD\ActionColumn;
use MicroCRUD\StringColumn;
use MicroCRUD\DateColumn;
use MicroCRUD\TextColumn;
use MicroCRUD\Table;

class HashBanTable extends Table
{
    public function __construct(\FFSPHP\PDO $db)
    {
        parent::__construct($db);
        $this->table = "image_bans";
        $this->base_query = "SELECT * FROM image_bans";
        $this->primary_key = "hash";
        $this->size = 100;
        $this->limit = 1000000;
        $this->set_columns([
            new StringColumn("hash", "Hash"),
            new TextColumn("reason", "Reason"),
            new DateColumn("date", "Date"),
            new ActionColumn("hash"),
        ]);
        $this->order_by = ["date DESC", "id"];
        $this->create_url = make_link("image_hash_ban/add");
        $this->delete_url = make_link("image_hash_ban/remove");
        $this->table_attrs = ["class" => "zebra"];
    }
}

class RemoveImageHashBanEvent extends Event
{
    public string $hash;

    public function __construct(string $hash)
    {
        parent::__construct();
        $this->hash = $hash;
    }
}

class AddImageHashBanEvent extends Event
{
    public string $hash;
    public string $reason;

    public function __construct(string $hash, string $reason)
    {
        parent::__construct();
        $this->hash = $hash;
        $this->reason = $reason;
    }
}

class ImageBan extends Extension
{
    /** @var ImageBanTheme */
    protected ?Themelet $theme;

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event)
    {
        global $database;
        if ($this->get_version("ext_imageban_version") < 1) {
            $database->create_table("image_bans", "
				id SCORE_AIPK,
				hash CHAR(32) NOT NULL,
				date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				reason TEXT NOT NULL
			");
            $this->set_version("ext_imageban_version", 1);
        }
    }

    public function onDataUpload(DataUploadEvent $event)
    {
        global $database;
        $row = $database->get_row("SELECT * FROM image_bans WHERE hash = :hash", ["hash"=>$event->hash]);
        if ($row) {
            log_info("image_hash_ban", "Attempted to upload a blocked image ({$event->hash} - {$row['reason']})");
            throw new UploadException("Post ".html_escape($row["hash"])." has been banned, reason: ".format_text($row["reason"]));
        }
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $database, $page, $user;

        if ($event->page_matches("image_hash_ban")) {
            if ($user->can(Permissions::BAN_IMAGE)) {
                if ($event->get_arg(0) == "add") {
                    $user->ensure_authed();
                    $input = validate_input(["c_hash"=>"optional,string", "c_reason"=>"string", "c_image_id"=>"optional,int"]);
                    $image = isset($input['c_image_id']) ? Image::by_id($input['c_image_id']) : null;
                    $hash = isset($input["c_hash"]) ? $input["c_hash"] : $image->hash;
                    $reason = isset($input['c_reason']) ? $input['c_reason'] : "DNP";

                    if ($hash) {
                        send_event(new AddImageHashBanEvent($hash, $reason));
                        $page->flash("Post ban added");

                        if ($image) {
                            send_event(new ImageDeletionEvent($image));
                            $page->flash("Post deleted");
                        }

                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(referer_or(make_link()));
                    }
                } elseif ($event->get_arg(0) == "remove") {
                    $user->ensure_authed();
                    $input = validate_input(["d_hash"=>"string"]);
                    send_event(new RemoveImageHashBanEvent($input['d_hash']));
                    $page->flash("Post ban removed");
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(referer_or(make_link()));
                } elseif ($event->get_arg(0) == "list") {
                    $t = new HashBanTable($database->raw_db());
                    $t->token = $user->get_auth_token();
                    $t->inputs = $_GET;
                    $this->theme->display_bans($page, $t->table($t->query()), $t->paginator());
                }
            }
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event)
    {
        global $user;
        if ($event->parent==="system") {
            if ($user->can(Permissions::BAN_IMAGE)) {
                $event->add_nav_link("image_bans", new Link('image_hash_ban/list/1'), "Post Bans", NavLink::is_active(["image_hash_ban"]));
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event)
    {
        global $user;
        if ($user->can(Permissions::BAN_IMAGE)) {
            $event->add_link("Post Bans", make_link("image_hash_ban/list/1"));
        }
    }

    public function onAddImageHashBan(AddImageHashBanEvent $event)
    {
        global $database;
        $database->execute(
            "INSERT INTO image_bans (hash, reason, date) VALUES (:hash, :reason, now())",
            ["hash"=>$event->hash, "reason"=>$event->reason]
        );
        log_info("image_hash_ban", "Banned hash {$event->hash} because '{$event->reason}'");
    }

    public function onRemoveImageHashBan(RemoveImageHashBanEvent $event)
    {
        global $database;
        $database->execute("DELETE FROM image_bans WHERE hash = :hash", ["hash"=>$event->hash]);
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event)
    {
        global $user;
        if ($user->can(Permissions::BAN_IMAGE)) {
            $event->add_part($this->theme->get_buttons_html($event->image));
        }
    }

    // in before resolution limit plugin
    public function get_priority(): int
    {
        return 30;
    }
}
