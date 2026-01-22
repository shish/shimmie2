<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroCRUD\{ActionColumn, DateColumn, EnumColumn, InetColumn, SelectColumn, StringColumn, Table};

final class IPBanTable extends Table
{
    public function __construct(\FFSPHP\PDO $db)
    {
        parent::__construct($db);
        $this->table = "bans";
        $this->base_query = "
			SELECT * FROM (
				SELECT bans.*, users.name AS banner
				FROM bans JOIN users ON banner_id=users.id
			) AS tbl1
		";
        $this->size = 100;
        $this->limit = 1000000;
        $this->set_columns([
            new SelectColumn("id"),
            new InetColumn("ip", "IP"),
            new EnumColumn("mode", "Mode", [
                "Block" => "block",
                "Firewall" => "firewall",
                "Ghost" => "ghost",
                "Anon Ghost" => "anon-ghost"
            ]),
            new BBCodeColumn("reason", "Reason"),
            new StringColumn("banner", "Banner"),
            new DateColumn("added", "Added"),
            new DateColumn("expires", "Expires"),
            new ActionColumn("id"),
        ]);
        $this->order_by = ["expires", "id"];
        $this->flags = [
            "all" => ["((expires > CURRENT_TIMESTAMP) OR (expires IS NULL))", null],
        ];
        $this->create_url = make_link("ip_ban/create");
        $this->bulk_url = make_link("ip_ban/bulk");
        $this->delete_url = make_link("ip_ban/delete");
        $this->table_attrs = ["class" => "zebra form"];
    }
}

final class RemoveIPBanEvent extends Event
{
    public function __construct(
        public int $id
    ) {
        parent::__construct();
    }
}

final class AddIPBanEvent extends Event
{
    public function __construct(
        public IPAddress $ip,
        public string $mode,
        public string $reason,
        public ?string $expires
    ) {
        parent::__construct();
        $this->reason = trim($reason);
    }
}

/** @extends Extension<IPBanTheme> */
final class IPBan extends Extension
{
    public const KEY = "ipban";

    public function get_priority(): int
    {
        return 10;
    }

    public function onInitExt(InitExtEvent $event): void
    {
        UserClass::$loading = UserClassSource::DEFAULT;
        new UserClass(
            "ghost",
            "base",
            [PrivMsgPermission::READ_PM => true],
            description: "Ghost users can log in and do read-only stuff with their own account (eg. reading their PMs to find out why they have been ghosted), but no writing",
        );
        UserClass::$loading = UserClassSource::UNKNOWN;
    }

    public function onUserLogin(UserLoginEvent $event): void
    {
        global $database;

        // Get lists of banned IPs and banned networks
        $ips = Ctx::$cache->get("ip_bans");
        $networks = Ctx::$cache->get("network_bans");
        if (is_null($ips) || is_null($networks)) {
            $rows = $database->get_pairs("
				SELECT ip, id
				FROM bans
				WHERE ((expires > CURRENT_TIMESTAMP) OR (expires IS NULL))
			");

            $ips = []; # "0.0.0.0" => 123;
            $networks = []; # "0.0.0.0/32" => 456;
            foreach ($rows as $ip => $id) {
                if (str_contains($ip, '/')) {
                    $networks[$ip] = $id;
                } else {
                    $ips[$ip] = $id;
                }
            }

            Ctx::$cache->set("ip_bans", $ips, 60);
            Ctx::$cache->set("network_bans", $networks, 60);
        }

        // Check if our current IP is in either of the ban lists
        $active_ban_id = (
            $this->find_active_ban(Network::get_real_ip(), $ips, $networks)
        );

        // If an active ban is found, act on it
        if (!is_null($active_ban_id)) {
            $row = $database->get_row("SELECT * FROM bans WHERE id=:id", ["id" => $active_ban_id]);
            if (empty($row)) {
                return;
            }

            $row_banner_id_int = intval($row['banner_id']);

            $msg = Ctx::$config->get("ipban_message_{$row['mode']}") ?? Ctx::$config->get(IPBanConfig::MESSAGE);
            assert(is_string($msg));
            $msg = str_replace('$IP', $row["ip"], $msg);
            $msg = str_replace('$DATE', $row['expires'] ?? 'the end of time', $msg);
            $msg = str_replace('$ADMIN', User::by_id($row_banner_id_int)->name, $msg);
            $msg = str_replace('$REASON', $row['reason'], $msg);
            $contact_link = contact_link();
            if (!empty($contact_link)) {
                $msg = str_replace('$CONTACT', "<a href='$contact_link'>Contact the staff (be sure to include this message)</a>", $msg);
            } else {
                $msg = str_replace('$CONTACT', "", $msg);
            }
            $msg .= "<!-- $active_ban_id / {$row["mode"]} -->";

            if ($row["mode"] === "ghost") {
                Ctx::$page->add_block(new Block(null, \MicroHTML\rawHTML($msg), "main", 0, is_content: false));
                $event->user->class = UserClass::$known_classes["ghost"];
            } elseif ($row["mode"] === "anon-ghost") {
                if ($event->user->is_anonymous()) {
                    Ctx::$page->add_block(new Block(null, \MicroHTML\rawHTML($msg), "main", 0, is_content: false));
                    $event->user->class = UserClass::$known_classes["ghost"];
                }
            } else {
                header("HTTP/1.1 403 Forbidden");
                print "$msg";
                exit;
            }
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        $page = Ctx::$page;
        if ($event->page_matches("ip_ban/create", method: "POST", permission: IPBanPermission::BAN_IP)) {
            $c_ip = $event->POST->req("c_ip");
            $c_mode = $event->POST->req("c_mode");
            $c_reason = $event->POST->req("c_reason");
            $c_expires = nullify($event->POST->get("c_expires"));
            if ($c_expires !== null) {
                $c_expires = date("Y-m-d H:i:s", \Safe\strtotime(trim($c_expires)));
            }
            send_event(new AddIPBanEvent(
                IPAddress::parse($c_ip),
                $c_mode,
                $c_reason,
                $c_expires
            ));
            $page->flash("Ban for {$c_ip} added");
            $page->set_redirect(make_link("ip_ban/list"));
        }
        if ($event->page_matches("ip_ban/delete", method: "POST", permission: IPBanPermission::BAN_IP)) {
            send_event(new RemoveIPBanEvent(int_escape($event->POST->req('d_id'))));
            $page->flash("Ban removed");
            $page->set_redirect(make_link("ip_ban/list"));
        }
        if ($event->page_matches("ip_ban/bulk", method: "POST", permission: IPBanPermission::BAN_IP)) {
            $action = $event->POST->req("bulk_action");
            if ($action === "delete") {
                $ids = $event->POST->getAll("id");
                foreach ($ids as $id) {
                    send_event(new RemoveIPBanEvent(int_escape($id)));
                }
                $page->flash(count($ids) . " bans removed");
            }
            $page->set_redirect(Url::referer_or());
        }
        if ($event->page_matches("ip_ban/list", method: "GET", permission: IPBanPermission::BAN_IP)) {
            $event->GET['c_banner'] = Ctx::$user->name;
            $event->GET['c_added'] = date('Y-m-d');
            $t = new IPBanTable(Ctx::$database->raw_db());
            $t->token = Ctx::$user->get_auth_token();
            $t->inputs = $event->GET->toArray();
            $this->theme->display_bans($t->table($t->query()), $t->paginator());
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "system") {
            if (Ctx::$user->can(IPBanPermission::BAN_IP)) {
                $event->add_nav_link(make_link('ip_ban/list'), "IP Bans", "ip_bans", ["ip_ban"]);
            }
        }
    }

    public function onAddIPBan(AddIPBanEvent $event): void
    {
        Ctx::$database->execute(
            "INSERT INTO bans (ip, mode, reason, expires, banner_id) VALUES (:ip, :mode, :reason, :expires, :admin_id)",
            ["ip" => (string)$event->ip, "mode" => $event->mode, "reason" => $event->reason, "expires" => $event->expires, "admin_id" => Ctx::$user->id]
        );
        Ctx::$cache->delete("ip_bans");
        Ctx::$cache->delete("network_bans");
        Log::info("ipban", "Banned ({$event->mode}) {$event->ip} because '{$event->reason}' until {$event->expires}");
    }

    public function onRemoveIPBan(RemoveIPBanEvent $event): void
    {
        global $database;
        $ban = $database->get_row("SELECT * FROM bans WHERE id = :id", ["id" => $event->id]);
        if ($ban) {
            $database->execute("DELETE FROM bans WHERE id = :id", ["id" => $event->id]);
            Ctx::$cache->delete("ip_bans");
            Ctx::$cache->delete("network_bans");
            Log::info("ipban", "Removed {$ban['ip']}'s ban");
        }
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        $database = Ctx::$database;

        // shortcut to latest
        if ($this->get_version() < 1) {
            $database->create_table("bans", "
				id SCORE_AIPK,
				banner_id INTEGER NOT NULL,
				mode VARCHAR(16) NOT NULL DEFAULT 'block',
				ip SCORE_INET NOT NULL,
				reason TEXT NOT NULL,
				added TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				expires TIMESTAMP NULL DEFAULT NULL,
				FOREIGN KEY (banner_id) REFERENCES users(id) ON DELETE CASCADE,
			");
            $database->execute("CREATE INDEX bans__expires ON bans(expires)");
            $this->set_version(10);
        }

        // ===

        if ($this->get_version() < 1) {
            $database->execute("CREATE TABLE bans (
				id int(11) NOT NULL auto_increment,
				ip char(15) default NULL,
				date TIMESTAMP default NULL,
				end TIMESTAMP default NULL,
				reason varchar(255) default NULL,
				PRIMARY KEY (id)
			)");
            $this->set_version(1);
        }

        if ($this->get_version() === 1) {
            $database->execute("ALTER TABLE bans ADD COLUMN banner_id INTEGER NOT NULL AFTER id");
            $this->set_version(2);
        }

        if ($this->get_version() === 2) {
            $database->execute("ALTER TABLE bans DROP COLUMN date");
            $database->execute("ALTER TABLE bans CHANGE ip ip CHAR(20) NOT NULL");
            $database->execute("ALTER TABLE bans CHANGE reason reason TEXT NOT NULL");
            $database->execute("CREATE INDEX bans__end ON bans(end)");
            $this->set_version(3);
        }

        if ($this->get_version() === 3) {
            $database->execute("ALTER TABLE bans CHANGE end old_end DATE NOT NULL");
            $database->execute("ALTER TABLE bans ADD COLUMN end INTEGER");
            if ($database->get_driver_id() === DatabaseDriverID::MYSQL) {
                $database->execute("UPDATE bans SET end = UNIX_TIMESTAMP(old_end)");
            } elseif ($database->get_driver_id() === DatabaseDriverID::PGSQL) {
                $database->execute("UPDATE bans SET end = EXTRACT(EPOCH FROM old_end)");
            } elseif ($database->get_driver_id() === DatabaseDriverID::SQLITE) {
                $database->execute("UPDATE bans SET end = unixepoch(old_end)");
            }
            $database->execute("ALTER TABLE bans DROP COLUMN old_end");
            $database->execute("CREATE INDEX bans__end ON bans(end)");
            $this->set_version(4);
        }

        if ($this->get_version() === 4) {
            $database->execute("ALTER TABLE bans CHANGE end end_timestamp INTEGER");
            $this->set_version(5);
        }

        if ($this->get_version() === 5) {
            $database->execute("ALTER TABLE bans CHANGE ip ip VARCHAR(15)");
            $this->set_version(6);
        }

        if ($this->get_version() === 6) {
            $database->execute("ALTER TABLE bans ADD FOREIGN KEY (banner_id) REFERENCES users(id) ON DELETE CASCADE");
            $this->set_version(7);
        }

        if ($this->get_version() === 7) {
            Ctx::$database->execute("ALTER TABLE bans CHANGE ip ip SCORE_INET");
            $database->execute("ALTER TABLE bans ADD COLUMN added TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
            $this->set_version(8);
        }

        if ($this->get_version() === 8) {
            $database->execute("ALTER TABLE bans ADD COLUMN mode VARCHAR(16) NOT NULL DEFAULT 'block'");
            $this->set_version(9);
        }

        if ($this->get_version() === 9) {
            $database->execute("ALTER TABLE bans ADD COLUMN expires TIMESTAMP DEFAULT NULL");
            $database->execute("UPDATE bans SET expires = to_date('1970/01/01', 'YYYY/MM/DD') + (end_timestamp * interval '1 seconds')");
            $database->execute("ALTER TABLE bans DROP COLUMN end_timestamp");
            $database->execute("CREATE INDEX bans__expires ON bans(expires)");
            $this->set_version(10);
        }
    }

    /**
     * @param array<string,int> $ips
     * @param array<string,int> $networks
     */
    public function find_active_ban(IPAddress $remote, array $ips, array $networks): ?int
    {
        $active_ban_id = null;
        $str = (string) $remote;
        if (isset($ips[$str])) {
            $active_ban_id = $ips[$str];
        } else {
            foreach ($networks as $range => $ban_id) {
                if (IPRange::parse($range)->contains($remote)) {
                    $active_ban_id = $ban_id;
                }
            }
        }
        return $active_ban_id;
    }
}
