<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroCRUD\{ActionColumn, Column, Table, TextColumn};

use function MicroHTML\{A, BR, INPUT, OPTION, SELECT, SPAN, emptyHTML};

use MicroHTML\HTMLElement;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface};
use Symfony\Component\Console\Output\OutputInterface;

final class ActorColumn extends Column
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
            case DatabaseDriverID::PGSQL:
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
    // @phpstan-ignore-next-line
    public function display(array $row): HTMLElement
    {
        $ret = emptyHTML();
        if ($row['username'] !== "Anonymous") {
            $ret->appendChild(A(["href" => make_link("user/{$row['username']}"), "title" => $row['address']], $row['username']));
            $ret->appendChild(BR());
        }
        $ret->appendChild($row['address']);
        return $ret;
    }
}

final class MessageColumn extends Column
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
            case DatabaseDriverID::PGSQL:
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
            if ((string)$v === @$inputs["r_{$this->name}"][1]) {
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
            LogLevel::DEBUG->value => "debug",
            LogLevel::INFO->value => "info",
            LogLevel::WARNING->value => "warning",
            LogLevel::ERROR->value => "error",
            LogLevel::CRITICAL->value => "critical",
            default => "",
        };
        $line = $row[$this->name];
        $line = html_escape($line);
        $line = preg_replace_callback(
            "/(Image #|Post #|&gt;&gt;)(\d+)/s",
            $this->link_image(...),
            $line
        );
        assert(is_string($line));
        return SPAN(["class" => "level-$c"], \MicroHTML\rawHTML($line));
    }

    /**
     * @param array<string> $id
     */
    protected function link_image(array $id): string
    {
        $iid = int_escape($id[2]);
        return "<a href='".make_link("post/view/$iid")."'>&gt;&gt;$iid</a>";
    }
}

final class LogTable extends Table
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

final class LogDatabase extends Extension
{
    public const KEY = "log_db";
    public const VERSION_KEY = "ext_log_database_version";

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        $database = Ctx::$database;

        if ($this->get_version() < 1) {
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
            $this->set_version(1);
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        $database = Ctx::$database;
        $page = Ctx::$page;
        if ($event->page_matches("log/view", permission: LogDatabasePermission::VIEW_EVENTLOG)) {
            $t = new LogTable($database->raw_db());
            $t->inputs = $event->GET->toArray();
            $page->set_title("Event Log");
            $this->theme->display_navigation();
            $page->add_block(new Block(null, emptyHTML($t->table($t->query()), $t->paginator())));
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "system") {
            if (Ctx::$user->can(LogDatabasePermission::VIEW_EVENTLOG)) {
                $event->add_nav_link(make_link('log/view'), "Event Log", "event_log");
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        if (Ctx::$user->can(LogDatabasePermission::VIEW_EVENTLOG)) {
            $event->add_link("Event Log", make_link("log/view"));
        }
    }

    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('log:test')
            ->setDescription("Log a test message at each level")
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                Log::debug(self::KEY, "This is a debug message");
                Log::info(self::KEY, "This is an info message");
                Log::warning(self::KEY, "This is a warning message");
                Log::error(self::KEY, "This is an error message");
                Log::critical(self::KEY, "This is a critical message");
                return Command::SUCCESS;
            });
    }

    public function onLog(LogEvent $event): void
    {
        $username = isset(Ctx::$user) ? Ctx::$user->name : "Anonymous";

        // not installed yet...
        if ($this->get_version() < 1) {
            return;
        }

        if ($event->priority >= Ctx::$config->get(LogDatabaseConfig::LEVEL)) {
            Ctx::$database->execute("
				INSERT INTO score_log(date_sent, section, priority, username, address, message)
				VALUES(now(), :section, :priority, :username, :address, :message)
			", [
                "section" => $event->section, "priority" => $event->priority, "username" => $username,
                "address" => (string)Network::get_real_ip(), "message" => $event->message
            ]);
        }
    }
}
