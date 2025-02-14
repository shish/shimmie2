<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;
use MicroCRUD\ActionColumn;
use MicroCRUD\Column;
use MicroCRUD\TextColumn;
use MicroCRUD\Table;

use function MicroHTML\A;
use function MicroHTML\SPAN;
use function MicroHTML\emptyHTML;
use function MicroHTML\INPUT;
use function MicroHTML\BR;
use function MicroHTML\SELECT;
use function MicroHTML\OPTION;
use function MicroHTML\rawHTML;

class ActorColumn extends Column
{
    public function __construct(string $name, string $title)
    {
        parent::__construct($name, $title);
        $this->sortable = false;
    }

    public function get_sql_filter(): string
    {
        $driver = $this->table->db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        switch ($driver) {
            case "pgsql":
                return "((LOWER(username) = LOWER(:{$this->name}_0)) OR (address && cast(:{$this->name}_1 as inet)))";
            default:
                return "((username = :{$this->name}_0) OR (address = :{$this->name}_1))";
        }
    }

    public function read_input(array $inputs): HTMLElement
    {
        return emptyHTML(
            INPUT([
                "type" => "text",
                "name" => "r_{$this->name}[]",
                "placeholder" => "Username",
                "value" => @$inputs["r_{$this->name}"][0]
            ]),
            BR(),
            INPUT([
                "type" => "text",
                "name" => "r_{$this->name}[]",
                "placeholder" => "IP Address",
                "value" => @$inputs["r_{$this->name}"][1]
            ])
        );
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    public function modify_input_for_read(string|array $input): array
    {
        assert(is_array($input));
        list($un, $ip) = $input;
        if (empty($un)) {
            $un = null;
        }
        if (empty($ip)) {
            $ip = null;
        }
        return [$un, $ip];
    }

    /**
     * @param array{username: string, address: string} $row
     */
    public function display(array $row): HTMLElement
    {
        $ret = emptyHTML();
        if ($row['username'] != "Anonymous") {
            $ret->appendChild(A(["href" => make_link("user/{$row['username']}"), "title" => $row['address']], $row['username']));
            $ret->appendChild(BR());
        }
        $ret->appendChild($row['address']);
        return $ret;
    }
}

class MessageColumn extends Column
{
    public function __construct(string $name, string $title)
    {
        parent::__construct($name, $title);
        $this->sortable = false;
    }

    public function get_sql_filter(): string
    {
        $driver = $this->table->db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        switch ($driver) {
            case "pgsql":
                return "(LOWER({$this->name}) LIKE LOWER(:{$this->name}_0) AND priority >= :{$this->name}_1)";
            default:
                return "({$this->name} LIKE :{$this->name}_0 AND priority >= :{$this->name}_1)";
        }
    }

    public function read_input(array $inputs): HTMLElement
    {
        $ret = emptyHTML(
            INPUT([
                "type" => "text",
                "name" => "r_{$this->name}[]",
                "placeholder" => $this->title,
                "value" => @$inputs["r_{$this->name}"][0]
            ])
        );

        $s = SELECT(["name" => "r_{$this->name}[]"]);
        $s->appendChild(OPTION(["value" => ""], '-'));
        foreach (LogLevel::names_to_levels() as $k => $v) {
            $attrs = ["value" => $v];
            if ($v == @$inputs["r_{$this->name}"][1]) {
                $attrs["selected"] = true;
            }
            $s->appendChild(OPTION($attrs, $k));
        }
        $ret->appendChild($s);
        return $ret;
    }

    public function modify_input_for_read(array|string $input): mixed
    {
        assert(is_array($input));
        list($m, $l) = $input;
        if (empty($m)) {
            $m = "%";
        } else {
            $m = "%$m%";
        }
        if (empty($l)) {
            $l = 0;
        }
        return [$m, $l];
    }

    public function display(array $row): HTMLElement
    {
        $c = match ($row['priority']) {
            LogLevel::DEBUG->value => "#999",
            LogLevel::INFO->value => "#000",
            LogLevel::WARNING->value => "#800",
            LogLevel::ERROR->value => "#C00",
            LogLevel::CRITICAL->value => "#F00",
            default => "#000",
        };
        return SPAN(["style" => "color: $c"], rawHTML($this->scan_entities($row[$this->name])));
    }

    protected function scan_entities(string $line): string
    {
        $line = preg_replace_callback("/Image #(\d+)/s", [$this, "link_image"], $line);
        assert(is_string($line));
        $line = preg_replace_callback("/Post #(\d+)/s", [$this, "link_image"], $line);
        assert(is_string($line));
        $line = preg_replace_callback("/>>(\d+)/s", [$this, "link_image"], $line);
        assert(is_string($line));
        return $line;
    }

    /**
     * @param array{1: string} $id
     */
    protected function link_image(array $id): string
    {
        $iid = int_escape($id[1]);
        return "<a href='".make_link("post/view/$iid")."'>&gt;&gt;$iid</a>";
    }
}

class LogTable extends Table
{
    public function __construct(\FFSPHP\PDO $db)
    {
        parent::__construct($db);
        $this->table = "score_log";
        $this->base_query = "SELECT * FROM score_log";
        $this->size = 100;
        $this->limit = 100000;
        $this->set_columns([
            new ShortDateTimeColumn("date_sent", "Time"),
            new TextColumn("section", "Module"),
            new ActorColumn("username_or_address", "User"),
            new MessageColumn("message", "Message"),
            new ActionColumn("id"),
        ]);
        $this->order_by = ["date_sent DESC"];
        $this->table_attrs = ["class" => "zebra form"];
    }
}

class LogDatabase extends Extension
{
    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_int(LogDatabaseConfig::LEVEL, LogLevel::INFO->value);
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        if ($this->get_version("ext_log_database_version") < 1) {
            $database->create_table("score_log", "
				id SCORE_AIPK,
				date_sent TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				section VARCHAR(32) NOT NULL,
				username VARCHAR(32) NOT NULL,
				address SCORE_INET NOT NULL,
				priority INT NOT NULL,
				message TEXT NOT NULL
			");
            //INDEX(section)
            $this->set_version("ext_log_database_version", 1);
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $event->panel->add_config_group(new LogDatabaseConfig());
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $database, $page, $user;
        if ($event->page_matches("log/view", permission: Permissions::VIEW_EVENTLOG)) {
            $t = new LogTable($database->raw_db());
            $t->inputs = $event->GET;
            $page->set_title("Event Log");
            $page->add_block(new NavBlock());
            $page->add_block(new Block(null, emptyHTML($t->table($t->query()), $t->paginator())));
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        global $user;
        if ($event->parent === "system") {
            if ($user->can(Permissions::VIEW_EVENTLOG)) {
                $event->add_nav_link("event_log", new Link('log/view'), "Event Log");
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::VIEW_EVENTLOG)) {
            $event->add_link("Event Log", make_link("log/view"));
        }
    }

    public function onLog(LogEvent $event): void
    {
        global $config, $database, $user;

        $username = ($user && $user->name) ? $user->name : "null";

        // not installed yet...
        if ($this->get_version("ext_log_database_version") < 1) {
            return;
        }

        if ($event->priority >= $config->get_int(LogDatabaseConfig::LEVEL)) {
            $database->execute("
				INSERT INTO score_log(date_sent, section, priority, username, address, message)
				VALUES(now(), :section, :priority, :username, :address, :message)
			", [
                "section" => $event->section, "priority" => $event->priority, "username" => $username,
                "address" => get_real_ip(), "message" => $event->message
            ]);
        }
    }
}
