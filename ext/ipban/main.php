<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroCRUD\ActionColumn;
use MicroCRUD\InetColumn;
use MicroCRUD\StringColumn;
use MicroCRUD\DateColumn;
use MicroCRUD\TextColumn;
use MicroCRUD\EnumColumn;
use MicroCRUD\Table;

class IPBanTable extends Table
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
            new InetColumn("ip", "IP"),
            new EnumColumn("mode", "Mode", [
                "Block" => "block",
                "Firewall" => "firewall",
                "Ghost" => "ghost",
                "Anon Ghost" => "anon-ghost"
            ]),
            new TextColumn("reason", "Reason"),
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
        $this->delete_url = make_link("ip_ban/delete");
        $this->table_attrs = ["class" => "zebra form"];
    }
}

class RemoveIPBanEvent extends Event
{
    public int $id;

    public function __construct(int $id)
    {
        parent::__construct();
        $this->id = $id;
    }
}

class AddIPBanEvent extends Event
{
    public string $ip;
    public string $mode;
    public string $reason;
    public ?string $expires;

    public function __construct(string $ip, string $mode, string $reason, ?string $expires)
    {
        parent::__construct();
        $this->ip = trim($ip);
        $this->mode = $mode;
        $this->reason = trim($reason);
        $this->expires = $expires;
    }
}

class IPBan extends Extension
{
    /** @var IPBanTheme */
    protected Themelet $theme;

    public function get_priority(): int
    {
        return 10;
    }

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_string(
            "ipban_message",
            '<p>IP <b>$IP</b> has been banned until <b>$DATE</b> by <b>$ADMIN</b> because of <b>$REASON</b>
<p>If you couldn\'t possibly be guilty of what you\'re banned for, the person we banned probably had a dynamic IP address and so do you.
<p>See <a href="http://whatismyipaddress.com/dynamic-static">http://whatismyipaddress.com/dynamic-static</a> for more information.
<p>$CONTACT'
        );
    }

    public function onUserLogin(UserLoginEvent $event): void
    {
        global $cache, $config, $database, $page;

        // Get lists of banned IPs and banned networks
        $ips = $cache->get("ip_bans");
        $networks = $cache->get("network_bans");
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

            $cache->set("ip_bans", $ips, 60);
            $cache->set("network_bans", $networks, 60);
        }

        // Check if our current IP is in either of the ban lists
        $active_ban_id = (
            $this->find_active_ban(get_real_ip(), $ips, $networks)
        );

        // If an active ban is found, act on it
        if (!is_null($active_ban_id)) {
            $row = $database->get_row("SELECT * FROM bans WHERE id=:id", ["id" => $active_ban_id]);
            if (empty($row)) {
                return;
            }

            $row_banner_id_int = intval($row['banner_id']);

            $msg = $config->get_string("ipban_message_{$row['mode']}") ?? $config->get_string("ipban_message") ?? "(no message)";
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
            assert(is_string($msg));
            $msg .= "<!-- $active_ban_id / {$row["mode"]} -->";

            if ($row["mode"] == "ghost") {
                $b = new Block(null, $msg, "main", 0);
                $b->is_content = false;
                $page->add_block($b);
                $page->add_cookie("nocache", "Ghost Banned", time() + 60 * 60 * 2, "/");
                $event->user->class = UserClass::$known_classes["ghost"];
            } elseif ($row["mode"] == "anon-ghost") {
                if ($event->user->is_anonymous()) {
                    $b = new Block(null, $msg, "main", 0);
                    $b->is_content = false;
                    $page->add_block($b);
                    $page->add_cookie("nocache", "Ghost Banned", time() + 60 * 60 * 2, "/");
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
        global $database, $page, $user;
        if ($event->page_matches("ip_ban/create", method: "POST", permission: Permissions::BAN_IP)) {
            $input = validate_input(["c_ip" => "string", "c_mode" => "string", "c_reason" => "string", "c_expires" => "optional,date"]);
            send_event(new AddIPBanEvent($input['c_ip'], $input['c_mode'], $input['c_reason'], $input['c_expires']));
            $page->flash("Ban for {$input['c_ip']} added");
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("ip_ban/list"));
        }
        if ($event->page_matches("ip_ban/delete", method: "POST", permission: Permissions::BAN_IP)) {
            $input = validate_input(["d_id" => "int"]);
            send_event(new RemoveIPBanEvent($input['d_id']));
            $page->flash("Ban removed");
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("ip_ban/list"));
        }
        if ($event->page_matches("ip_ban/list", method: "GET", permission: Permissions::BAN_IP)) {
            $event->GET['c_banner'] = $user->name;
            $event->GET['c_added'] = date('Y-m-d');
            $t = new IPBanTable($database->raw_db());
            $t->token = $user->get_auth_token();
            $t->inputs = $event->GET;
            $this->theme->display_bans($page, $t->table($t->query()), $t->paginator());
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        global $config;

        $sb = $event->panel->create_new_block("IP Ban");
        $sb->add_longtext_option("ipban_message", 'Message to show to banned users:<br>(with $IP, $DATE, $ADMIN, $REASON, and $CONTACT)');
        if ($config->get_string("ipban_message_ghost")) {
            $sb->add_longtext_option("ipban_message_ghost", 'Message to show to ghost users:');
        }
        if ($config->get_string("ipban_message_anon-ghost")) {
            $sb->add_longtext_option("ipban_message_anon-ghost", 'Message to show to ghost anons:');
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        global $user;
        if ($event->parent === "system") {
            if ($user->can(Permissions::BAN_IP)) {
                $event->add_nav_link("ip_bans", new Link('ip_ban/list'), "IP Bans", NavLink::is_active(["ip_ban"]));
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::BAN_IP)) {
            $event->add_link("IP Bans", make_link("ip_ban/list"));
        }
    }

    public function onAddIPBan(AddIPBanEvent $event): void
    {
        global $cache, $user, $database;
        $sql = "INSERT INTO bans (ip, mode, reason, expires, banner_id) VALUES (:ip, :mode, :reason, :expires, :admin_id)";
        $database->execute($sql, ["ip" => $event->ip, "mode" => $event->mode, "reason" => $event->reason, "expires" => $event->expires, "admin_id" => $user->id]);
        $cache->delete("ip_bans");
        $cache->delete("network_bans");
        log_info("ipban", "Banned ({$event->mode}) {$event->ip} because '{$event->reason}' until {$event->expires}");
    }

    public function onRemoveIPBan(RemoveIPBanEvent $event): void
    {
        global $cache, $database;
        $ban = $database->get_row("SELECT * FROM bans WHERE id = :id", ["id" => $event->id]);
        if ($ban) {
            $database->execute("DELETE FROM bans WHERE id = :id", ["id" => $event->id]);
            $cache->delete("ip_bans");
            $cache->delete("network_bans");
            log_info("ipban", "Removed {$ban['ip']}'s ban");
        }
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        // shortcut to latest
        if ($this->get_version("ext_ipban_version") < 1) {
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
            $this->set_version("ext_ipban_version", 10);
        }

        // ===

        if ($this->get_version("ext_ipban_version") < 1) {
            $database->execute("CREATE TABLE bans (
				id int(11) NOT NULL auto_increment,
				ip char(15) default NULL,
				date TIMESTAMP default NULL,
				end TIMESTAMP default NULL,
				reason varchar(255) default NULL,
				PRIMARY KEY (id)
			)");
            $this->set_version("ext_ipban_version", 1);
        }

        if ($this->get_version("ext_ipban_version") == 1) {
            $database->execute("ALTER TABLE bans ADD COLUMN banner_id INTEGER NOT NULL AFTER id");
            $this->set_version("ext_ipban_version", 2);
        }

        if ($this->get_version("ext_ipban_version") == 2) {
            $database->execute("ALTER TABLE bans DROP COLUMN date");
            $database->execute("ALTER TABLE bans CHANGE ip ip CHAR(20) NOT NULL");
            $database->execute("ALTER TABLE bans CHANGE reason reason TEXT NOT NULL");
            $database->execute("CREATE INDEX bans__end ON bans(end)");
            $this->set_version("ext_ipban_version", 3);
        }

        if ($this->get_version("ext_ipban_version") == 3) {
            $database->execute("ALTER TABLE bans CHANGE end old_end DATE NOT NULL");
            $database->execute("ALTER TABLE bans ADD COLUMN end INTEGER");
            $database->execute("UPDATE bans SET end = UNIX_TIMESTAMP(old_end)");
            $database->execute("ALTER TABLE bans DROP COLUMN old_end");
            $database->execute("CREATE INDEX bans__end ON bans(end)");
            $this->set_version("ext_ipban_version", 4);
        }

        if ($this->get_version("ext_ipban_version") == 4) {
            $database->execute("ALTER TABLE bans CHANGE end end_timestamp INTEGER");
            $this->set_version("ext_ipban_version", 5);
        }

        if ($this->get_version("ext_ipban_version") == 5) {
            $database->execute("ALTER TABLE bans CHANGE ip ip VARCHAR(15)");
            $this->set_version("ext_ipban_version", 6);
        }

        if ($this->get_version("ext_ipban_version") == 6) {
            $database->execute("ALTER TABLE bans ADD FOREIGN KEY (banner_id) REFERENCES users(id) ON DELETE CASCADE");
            $this->set_version("ext_ipban_version", 7);
        }

        if ($this->get_version("ext_ipban_version") == 7) {
            $database->execute($database->scoreql_to_sql("ALTER TABLE bans CHANGE ip ip SCORE_INET"));
            $database->execute("ALTER TABLE bans ADD COLUMN added TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
            $this->set_version("ext_ipban_version", 8);
        }

        if ($this->get_version("ext_ipban_version") == 8) {
            $database->execute("ALTER TABLE bans ADD COLUMN mode VARCHAR(16) NOT NULL DEFAULT 'block'");
            $this->set_version("ext_ipban_version", 9);
        }

        if ($this->get_version("ext_ipban_version") == 9) {
            $database->execute("ALTER TABLE bans ADD COLUMN expires TIMESTAMP DEFAULT NULL");
            $database->execute("UPDATE bans SET expires = to_date('1970/01/01', 'YYYY/MM/DD') + (end_timestamp * interval '1 seconds')");
            $database->execute("ALTER TABLE bans DROP COLUMN end_timestamp");
            $database->execute("CREATE INDEX bans__expires ON bans(expires)");
            $this->set_version("ext_ipban_version", 10);
        }
    }

    /**
     * @param array<string,int> $ips
     * @param array<string,int> $networks
     */
    public function find_active_ban(string $remote, array $ips, array $networks): ?int
    {
        $active_ban_id = null;
        if (isset($ips[$remote])) {
            $active_ban_id = $ips[$remote];
        } else {
            foreach ($networks as $range => $ban_id) {
                if (ip_in_range($remote, $range)) {
                    $active_ban_id = $ban_id;
                }
            }
        }
        return $active_ban_id;
    }
}
