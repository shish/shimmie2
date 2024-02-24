<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroCRUD\ActionColumn;
use MicroCRUD\StringColumn;
use MicroCRUD\DateColumn;
use MicroCRUD\TextColumn;
use MicroCRUD\Table;

use function MicroHTML\{INPUT,emptyHTML};

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
        $this->table_attrs = ["class" => "zebra form"];
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
    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
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

    public function onDataUpload(DataUploadEvent $event): void
    {
        global $database;
        $row = $database->get_row("SELECT * FROM image_bans WHERE hash = :hash", ["hash" => $event->hash]);
        if ($row) {
            log_info("image_hash_ban", "Attempted to upload a blocked image ({$event->hash} - {$row['reason']})");
            throw new UploadException("Post {$row["hash"]} has been banned, reason: {$row["reason"]}");
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $database, $page, $user;

        if ($event->page_matches("image_hash_ban/add", method: "POST", permission: Permissions::BAN_IMAGE)) {
            $input = validate_input(["c_hash" => "optional,string", "c_reason" => "string", "c_image_id" => "optional,int"]);
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
        }
        if ($event->page_matches("image_hash_ban/remove", method: "POST", permission: Permissions::BAN_IMAGE)) {
            $input = validate_input(["d_hash" => "string"]);
            send_event(new RemoveImageHashBanEvent($input['d_hash']));
            $page->flash("Post ban removed");
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(referer_or(make_link()));
        }
        if ($event->page_matches("image_hash_ban/list", permission: Permissions::BAN_IMAGE)) {
            $t = new HashBanTable($database->raw_db());
            $t->token = $user->get_auth_token();
            $t->inputs = $event->GET;
            $this->theme->display_crud("Post Bans", $t->table($t->query()), $t->paginator());
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        global $user;
        if ($event->parent === "system") {
            if ($user->can(Permissions::BAN_IMAGE)) {
                $event->add_nav_link("image_bans", new Link('image_hash_ban/list'), "Post Bans", NavLink::is_active(["image_hash_ban"]));
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::BAN_IMAGE)) {
            $event->add_link("Post Bans", make_link("image_hash_ban/list"));
        }
    }

    public function onAddImageHashBan(AddImageHashBanEvent $event): void
    {
        global $database;
        $database->execute(
            "INSERT INTO image_bans (hash, reason, date) VALUES (:hash, :reason, now())",
            ["hash" => $event->hash, "reason" => $event->reason]
        );
        log_info("image_hash_ban", "Banned hash {$event->hash} because '{$event->reason}'");
    }

    public function onRemoveImageHashBan(RemoveImageHashBanEvent $event): void
    {
        global $database;
        $database->execute("DELETE FROM image_bans WHERE hash = :hash", ["hash" => $event->hash]);
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::BAN_IMAGE)) {
            $event->add_part(SHM_SIMPLE_FORM(
                "image_hash_ban/add",
                INPUT(["type" => 'hidden', "name" => 'c_hash', "value" => $event->image->hash]),
                INPUT(["type" => 'hidden', "name" => 'c_image_id', "value" => $event->image->id]),
                INPUT(["type" => 'text', "name" => 'c_reason']),
                INPUT(["type" => 'submit', "value" => 'Ban Hash and Delete Post']),
            ));
        }
    }

    // in before resolution limit plugin
    public function get_priority(): int
    {
        return 30;
    }
}
