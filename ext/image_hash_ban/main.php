<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroCRUD\{ActionColumn, DateColumn, SelectColumn, StringColumn, Table};

use function MicroHTML\{INPUT,emptyHTML};

final class HashBanTable extends Table
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
            new SelectColumn("hash"),
            new StringColumn("hash", "Hash"),
            new BBCodeColumn("reason", "Reason"),
            new DateColumn("date", "Date"),
            new ActionColumn("hash"),
        ]);
        $this->order_by = ["date DESC", "id"];
        $this->create_url = make_link("image_hash_ban/add");
        $this->bulk_url = make_link("image_hash_ban/bulk");
        $this->delete_url = make_link("image_hash_ban/remove");
        $this->table_attrs = ["class" => "zebra form"];
    }
}

final class RemoveImageHashBanEvent extends Event
{
    public function __construct(
        public string $hash
    ) {
        parent::__construct();
    }
}

final class AddImageHashBanEvent extends Event
{
    public function __construct(
        public string $hash,
        public string $reason
    ) {
        parent::__construct();
    }
}

final class ImageBan extends Extension
{
    public const KEY = "image_hash_ban";
    public const VERSION_KEY = "ext_imageban_version";

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;
        if ($this->get_version() < 1) {
            $database->create_table("image_bans", "
				id SCORE_AIPK,
				hash CHAR(32) NOT NULL,
				date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				reason TEXT NOT NULL
			");
            $this->set_version(1);
        }
    }

    public function onDataUpload(DataUploadEvent $event): void
    {
        global $database;
        $row = $database->get_row("SELECT * FROM image_bans WHERE hash = :hash", ["hash" => $event->hash]);
        if ($row) {
            Log::info("image_hash_ban", "Attempted to upload a blocked image ({$event->hash} - {$row['reason']})");
            throw new UploadException("Post {$row["hash"]} has been banned, reason: {$row["reason"]}");
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        $page = Ctx::$page;
        if ($event->page_matches("image_hash_ban/add", method: "POST", permission: ImageHashBanPermission::BAN_IMAGE)) {
            $c_hash = $event->POST->get("c_hash");
            $c_reason = $event->POST->get("c_reason");
            $c_image_id = $event->POST->get("c_image_id");

            $image = !empty($c_image_id) ? Image::by_id_ex(int_escape($c_image_id)) : null;
            $hash = !empty($c_hash) ? $c_hash : $image?->hash;
            $reason = !empty($c_reason) ? $c_reason : "DNP";

            if ($hash) {
                send_event(new AddImageHashBanEvent($hash, $reason));
                $page->flash("Post ban added");

                if ($image) {
                    send_event(new ImageDeletionEvent($image));
                    $page->flash("Post deleted");
                }

                $page->set_redirect(Url::referer_or());
            }
        }
        if ($event->page_matches("image_hash_ban/bulk", method: "POST", permission: ImageHashBanPermission::BAN_IMAGE)) {
            $action = $event->POST->req("bulk_action");
            if ($action === "delete") {
                $hashes = $event->POST->getAll("hash");
                foreach ($hashes as $hash) {
                    send_event(new RemoveImageHashBanEvent($hash));
                }
                $page->flash(count($hashes) . " bans removed");
            }
            $page->set_redirect(Url::referer_or());
        }
        if ($event->page_matches("image_hash_ban/remove", method: "POST", permission: ImageHashBanPermission::BAN_IMAGE)) {
            send_event(new RemoveImageHashBanEvent($event->POST->req('d_hash')));
            $page->flash("Post ban removed");
            $page->set_redirect(Url::referer_or());
        }
        if ($event->page_matches("image_hash_ban/list", permission: ImageHashBanPermission::BAN_IMAGE)) {
            $t = new HashBanTable(Ctx::$database->raw_db());
            $t->token = Ctx::$user->get_auth_token();
            $t->inputs = $event->GET->toArray();
            $page->set_title("Post Bans");
            $page->add_block(new Block(null, emptyHTML($t->table($t->query()), $t->paginator())));
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "system") {
            if (Ctx::$user->can(ImageHashBanPermission::BAN_IMAGE)) {
                $event->add_nav_link(make_link('image_hash_ban/list'), "Post Bans", "hash_bans", ["image_hash_ban"]);
            }
        }
    }

    public function onAddImageHashBan(AddImageHashBanEvent $event): void
    {
        global $database;
        $database->execute(
            "INSERT INTO image_bans (hash, reason, date) VALUES (:hash, :reason, now())",
            ["hash" => $event->hash, "reason" => $event->reason]
        );
        Log::info("image_hash_ban", "Banned hash {$event->hash} because '{$event->reason}'");
    }

    public function onRemoveImageHashBan(RemoveImageHashBanEvent $event): void
    {
        global $database;
        $database->execute("DELETE FROM image_bans WHERE hash = :hash", ["hash" => $event->hash]);
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        if (Ctx::$user->can(ImageHashBanPermission::BAN_IMAGE)) {
            $event->add_part(SHM_SIMPLE_FORM(
                make_link("image_hash_ban/add"),
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
