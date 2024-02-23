<?php declare(strict_types=1);

use MicroCRUD\ActionColumn;
use MicroCRUD\StringColumn;
use MicroCRUD\DateColumn;
use MicroCRUD\TextColumn;
use MicroCRUD\Table;

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
            new IntColumn("post_id", "Post"),
            new StringColumn("hash"),
            new DateColumn("date", "Date"),
            new TextColumn("message", "Message"),
        ]);
        $this->order_by = ["date DESC", "post_id"];
        $this->table_attrs = ["class" => "zebra"];
    }
}

class Tombstones extends Extension
{
    /** @var TombstoneTheme */
    protected $theme;

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event)
    {
        global $database;
        if ($this->get_version("ext_tombstone_version") < 1) {
            $database->create_table("tombstones", "
				post_id INTEGER NOT NULL,
				hash CHAR(32) NOT NULL,
				date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				message TEXT NOT NULL
			");
            $this->set_version("ext_tombstone_version", 1);
        }
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $database, $page, $user;

        if ($event->page_matches("post/view")) {
            $post_id = int_escape($event->get_arg(0));

            $tombstone = $database->get_one("SELECT * FROM tombstones WHERE post_id=:post_id", ["post_id"=>$post_id]);

            if (!is_null($tombstone)) {
                $this->theme->display_tombstone($tombstone);
            }
        }
    }

    public function onAddImageHashBan(AddImageHashBanEvent $event)
    {
        global $database;
        $database->execute(
            "UPDATE tombstones SET message=message || :reason WHERE hash=:hash",
            ["hash"=>$event->hash, "reason"=>"\n" . $event->reason]
        );
    }
}
