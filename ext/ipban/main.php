<?php

use MicroCRUD\StringColumn;
use MicroCRUD\DateColumn;
use MicroCRUD\TextColumn;
use MicroCRUD\EnumColumn;
use MicroCRUD\Table;

class IPBanTable extends Table
{
    public function __construct(\PDO $db, $token=null)
    {
        parent::__construct($db, $token);

        $this->table = "bans";
        $this->base_query = "
			SELECT bans.*, users.name AS banner
			FROM bans JOIN users ON banner_id=users.id
		";

        $this->size = 10;
        $this->columns = [
            new StringColumn("ip", "IP"),
            new EnumColumn("mode", "Mode", ["Block"=>"block", "Firewall"=>"firewall"]),
            new TextColumn("reason", "Reason"),
            new StringColumn("banner", "Banner"),
            new DateColumn("added", "Added"),
            new DateColumn("expires", "Expires"),
        ];
        $this->order_by = ["expires", "id"];
        $this->flags = [
            "all" => ["((expires > CURRENT_TIMESTAMP) OR (expires IS NULL))", null],
        ];
        $this->create_url = "/ip_ban/create";
        $this->delete_url = "/ip_ban/remove";
    }
}

class RemoveIPBanEvent extends Event
{
    public $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }
}

class AddIPBanEvent extends Event
{
    public $ip;
    public $reason;
    public $expires;

    public function __construct(string $ip, string $reason, ?string $expires)
    {
        $this->ip = trim($ip);
        $this->reason = trim($reason);
        $this->expires = $expires;
    }
}

class IPBan extends Extension
{
    public function get_priority(): int
    {
        return 10;
    }

    public function onInitExt(InitExtEvent $event)
    {
        global $config;
        $config->set_default_string(
            "ipban_message",
            '<p>IP <b>$IP</b> has been banned until <b>$DATE</b> by <b>$ADMIN</b> because of <b>$REASON</b>
<p>If you couldn\'t possibly be guilty of what you\'re banned for, the person we banned probably had a dynamic IP address and so do you.
<p>See <a href="http://whatismyipaddress.com/dynamic-static">http://whatismyipaddress.com/dynamic-static</a> for more information.
<p>$CONTACT'
        );
        $this->check_ip_ban();
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        if ($event->page_matches("ip_ban")) {
            global $database, $page, $user;
            if ($user->can(Permissions::BAN_IP)) {
                if ($event->get_arg(0) == "create" && $user->check_auth_token()) {
                    if (isset($_POST['c_ip']) && isset($_POST['c_reason']) && isset($_POST['c_expires'])) {
                        if (empty($_POST['c_expires'])) {
                            $end = null;
                        } else {
                            $end = date("Y-m-d H:i:s", strtotime(trim($_POST['c_expires'])));
                        }
                        send_event(new AddIPBanEvent($_POST['c_ip'], $_POST['c_reason'], $end));

                        flash_message("Ban for {$_POST['c_ip']} added");
                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(make_link("ip_ban/list"));
                    }
                } elseif ($event->get_arg(0) == "delete" && $user->check_auth_token()) {
                    if (isset($_POST['d_id'])) {
                        send_event(new RemoveIPBanEvent($_POST['d_id']));

                        flash_message("Ban removed");
                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(make_link("ip_ban/list"));
                    }
                } elseif ($event->get_arg(0) == "list") {
                    $t = new IPBanTable($database->raw_db(), $user->get_auth_token());
                    $table = $t->table($t->query());
                    $this->theme->display_bans($page, $table, $t->paginator());
                }
            } else {
                $this->theme->display_permission_denied();
            }
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = new SetupBlock("IP Ban");
        $sb->add_longtext_option("ipban_message", 'Message to show to banned users:<br>(with $IP, $DATE, $ADMIN, $REASON, and $CONTACT)');
        $event->panel->add_block($sb);
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event)
    {
        global $user;
        if ($event->parent==="system") {
            if ($user->can(Permissions::BAN_IP)) {
                $event->add_nav_link("ip_bans", new Link('ip_ban/list'), "IP Bans", NavLink::is_active(["ip_ban"]));
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event)
    {
        global $user;
        if ($user->can(Permissions::BAN_IP)) {
            $event->add_link("IP Bans", make_link("ip_ban/list"));
        }
    }

    public function onAddIPBan(AddIPBanEvent $event)
    {
        global $cache, $user, $database;
        $sql = "INSERT INTO bans (ip, reason, expires, banner_id) VALUES (:ip, :reason, :expires, :admin_id)";
        $database->Execute($sql, ["ip"=>$event->ip, "reason"=>$event->reason, "expires"=>$event->expires, "admin_id"=>$user->id]);
        $cache->delete("ip_bans_sorted");
        log_info("ipban", "Banned {$event->ip} because '{$event->reason}' until {$event->expires}");
    }

    public function onRemoveIPBan(RemoveIPBanEvent $event)
    {
        global $cache, $database;
        $ban = $database->get_row("SELECT * FROM bans WHERE id = :id", ["id"=>$event->id]);
        if ($ban) {
            $database->Execute("DELETE FROM bans WHERE id = :id", ["id"=>$event->id]);
            $cache->delete("ip_bans_sorted");
            log_info("ipban", "Removed {$ban['ip']}'s ban");
        }
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event)
    {
        global $database;
        global $config;

        // shortcut to latest
        if ($this->get_version("ext_ipban_version") < 1) {
            $database->create_table("bans", "
				id SCORE_AIPK,
				banner_id INTEGER NOT NULL,
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
            $database->Execute("CREATE TABLE bans (
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
            $database->Execute("ALTER TABLE bans ADD FOREIGN KEY (banner_id) REFERENCES users(id) ON DELETE CASCADE");
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

    private function check_ip_ban()
    {
        $remote = $_SERVER['REMOTE_ADDR'];
        $bans = $this->get_active_bans_sorted();

        // bans[0] = IPs
        if (isset($bans[0][$remote])) {
            $this->block($remote);  // never returns
        }

        // bans[1] = CIDR nets
        foreach ($bans[1] as $ip => $true) {
            if (ip_in_range($remote, $ip)) {
                $this->block($remote);  // never returns
            }
        }
    }

    private function block(string $remote)
    {
        global $config, $database;

        $prefix = ($database->get_driver_name() == DatabaseDriver::SQLITE ? "bans." : "");

        $bans = $this->get_bans(false, null);

        foreach ($bans as $row) {
            $ip = $row[$prefix."ip"];
            if (
                (strstr($ip, '/') && ip_in_range($remote, $ip)) ||
                ($ip == $remote)
            ) {
                $reason = $row[$prefix.'reason'];
                $admin = User::by_id($row[$prefix.'banner_id']);
                $date = $row['expires'];
                $msg = $config->get_string("ipban_message");
                $msg = str_replace('$IP', $ip, $msg);
                $msg = str_replace('$DATE', $date, $msg);
                $msg = str_replace('$ADMIN', $admin->name, $msg);
                $msg = str_replace('$REASON', $reason, $msg);
                $contact_link = contact_link();
                if (!empty($contact_link)) {
                    $msg = str_replace('$CONTACT', "<a href='$contact_link'>Contact the staff (be sure to include this message)</a>", $msg);
                } else {
                    $msg = str_replace('$CONTACT', "", $msg);
                }
                header("HTTP/1.0 403 Forbidden");
                print "$msg";

                exit;
            }
        }
        log_error("ipban", "block($remote) called but no bans matched");
        exit;
    }

    private function get_bans(bool $all, ?int $page)
    {
        global $database;

        $size = 100;
        if (@$_GET['limit']) {
            $size = int_escape($_GET['limit']);
        }
        $filters = ["1=1"];
        $args = [];

        if (!$all) {
            $filters[] = "((expires > CURRENT_TIMESTAMP) OR (expires IS NULL))";
        }
        if (@$_GET['s_ip']) {
            $filters[] = "(ip = :ip)";
            $args['ip'] = $_GET['s_ip'];
        }
        if (@$_GET['s_reason']) {
            $filters[] = "(reason LIKE :reason)";
            $args['reason'] = '%' . $_GET['s_reason'] . "%";
        }
        if (@$_GET['s_banner']) {
            $filters[] = "(banner_id = :banner_id)";
            $args['banner_id'] = User::by_name($_GET['s_banner'])->id;
        }
        if (@$_GET['s_added']) {
            $filters[] = "(added LIKE :added)";
            $args['added'] = '%' . $_GET['s_added'] . "%";
        }
        if (@$_GET['s_expires']) {
            $filters[] = "(expires LIKE :expires)";
            $args['expires'] = '%' . $_GET['s_expires'] . "%";
        }
        if (@$_GET['s_mode']) {
            $filters[] = "(mode = :mode)";
            $args['mode'] = $_GET['s_mode'];
        }
        $filter = implode(" AND ", $filters);

        if (is_null($page)) {
            $pager = "";
        } else {
            $pager = "LIMIT :limit OFFSET :offset";
            $args["offset"] = ($page-1)*$size;
            $args['limit'] = $size;
        }

        return $database->get_all("
			SELECT bans.*, users.name as banner_name
			FROM bans
			JOIN users ON banner_id = users.id
			WHERE $filter
			ORDER BY expires, bans.id
			$pager
		", $args);
    }

    // returns [ips, nets]
    private function get_active_bans_sorted()
    {
        global $cache;

        $cached = $cache->get("ip_bans_sorted");
        if ($cached) {
            return $cached;
        }

        $bans = $this->get_bans(false, null);
        $ips = []; # "0.0.0.0" => false);
        $nets = []; # "0.0.0.0/32" => false);
        foreach ($bans as $row) {
            if (strstr($row['ip'], '/')) {
                $nets[$row['ip']] = true;
            } else {
                $ips[$row['ip']] = true;
            }
        }

        $sorted = [$ips, $nets];
        $cache->set("ip_bans_sorted", $sorted, 600);
        return $sorted;
    }
}
