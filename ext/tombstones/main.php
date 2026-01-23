<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroCRUD\{DateColumn, IntegerColumn, StringColumn, Table, TextColumn};

use function MicroHTML\{P, emptyHTML};

class TombstoneTable extends Table
{
    public function __construct(\FFSPHP\PDO $db)
    {
        parent::__construct($db);
        $this->table = "tombstones";
        $this->base_query = "SELECT * FROM tombstones";
        $this->primary_key = "post_id";
        $this->size = 100;
        $this->limit = 1000000;
        $this->set_columns([
            new IntegerColumn("post_id", "Post"),
            new StringColumn("hash", "Hash"),
            new DateColumn("date", "Date"),
            new TextColumn("message", "Message"),
        ]);
        $this->order_by = ["date DESC", "post_id"];
        $this->table_attrs = ["class" => "zebra"];
    }
}

class Tombstones extends Extension
{
    public const KEY = "tombstones";

    #[EventListener]
    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;
        if ($this->get_version() < 1) {
            $database->create_table("tombstones", "
				post_id INTEGER NOT NULL,
				hash CHAR(32) NOT NULL,
				date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				message TEXT NOT NULL,
			");
            $this->set_version(1);
        }
    }

    #[EventListener(priority: 20)] // Before /post/view
    public function onPageRequest(PageRequestEvent $event): void
    {
        global $database;

        if ($event->page_matches("post/view/{post_id}")) {
            $post_id = $event->get_iarg('post_id');
            $tombstone = $database->get_row("SELECT * FROM tombstones WHERE post_id=:post_id", ["post_id" => $post_id]);
            if (!is_null($tombstone)) {
                if (!is_null(Image::by_id($post_id))) {
                    // SQLite can re-use IDs of deleted rows, so if the
                    // post exists, ignore the tombstone
                    return;
                }
                if (ImageBanInfo::is_enabled()) {
                    $ban = $database->get_row(
                        "SELECT * FROM image_bans WHERE hash=:hash",
                        ["hash" => $tombstone["hash"]]
                    );
                } else {
                    $ban = null;
                }

                $msg = $tombstone["message"];
                $html = emptyHTML(P($msg));
                if ($ban) {
                    $html->appendChild(P("Additionally, this post was banned for the following reason:"));
                    $html->appendChild(P($ban["reason"]));
                }
                throw new PostNotFound($msg, $html);
            }
        }
    }

    #[EventListener]
    public function onImageDeletion(ImageDeletionEvent $event): void
    {
        global $database;

        $hash = $event->image->hash;
        $date = date("Y-m-d H:i");
        $name = Ctx::$user->name;

        $database->execute(
            "INSERT INTO tombstones (post_id, hash, message) VALUES (:post_id, :hash, :message)",
            [
                "post_id" => $event->image->id,
                "hash" => $event->image->hash,
                "message" => "$hash was deleted on $date by $name",
            ]
        );
    }
}
