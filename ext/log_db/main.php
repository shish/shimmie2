<?php

use function MicroHTML\{A,SPAN};
use MicroCRUD\Column;
use MicroCRUD\DateTimeColumn;
use MicroCRUD\TextColumn;
use MicroCRUD\Table;


class ActorColumn extends Column {
    public function __construct($name, $title)
    {
        parent::__construct($name, $title, "((username=:$name) OR (address=:$name))");
    }

    public function display($row)
    {
        if ($row['username'] == "Anonymous") {
            return $row["address"];
        } else {
            return A(["href"=>make_link("user/{$row['username']}"), "title"=>$row['address']], $row['username']);
        }
    }
}

class MessageColumn extends TextColumn {
    public function display($row)
    {
        $c = "#000";
        switch ($row['priority']) {
            case SCORE_LOG_DEBUG: $c = "#999"; break;
            case SCORE_LOG_INFO: $c = "#000"; break;
            case SCORE_LOG_WARNING: $c = "#800"; break;
            case SCORE_LOG_ERROR: $c = "#C00"; break;
            case SCORE_LOG_CRITICAL: $c = "#F00"; break;
        }
        return SPAN(["style"=>"color: $c"], $this->scan_entities($row[$this->name]));
    }

    protected function scan_entities($line)
    {
        return preg_replace_callback("/Image #(\d+)/s", [$this, "link_image"], $line);
    }

    protected function link_image($id)
    {
        $iid = int_escape($id[1]);
        return "<a href='".make_link("post/view/$iid")."'>Image #$iid</a>";
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
        $this->limit = 1000000;
        $this->columns = [
            new DateTimeColumn("date_sent", "Time"),
            new TextColumn("section", "Module"),
            new ActorColumn("username_or_address", "User"),
            new MessageColumn("message", "Message")
        ];
        $this->order_by = ["date_sent DESC"];
        $this->table_attrs = ["class" => "zebra"];
    }
}

class LogDatabase extends Extension
{
    public function onInitExt(InitExtEvent $event)
    {
        global $config;
        $config->set_default_int("log_db_priority", SCORE_LOG_INFO);
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event)
    {
        global $config, $database;

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

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = new SetupBlock("Logging (Database)");
        $sb->add_choice_option("log_db_priority", [
            "Debug" => SCORE_LOG_DEBUG,
            "Info" => SCORE_LOG_INFO,
            "Warning" => SCORE_LOG_WARNING,
            "Error" => SCORE_LOG_ERROR,
            "Critical" => SCORE_LOG_CRITICAL,
        ], "Debug Level: ");
        $event->panel->add_block($sb);
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $cache, $database, $user;
        if ($event->page_matches("log/view")) {
            if ($user->can(Permissions::VIEW_EVENTLOG)) {
                $wheres = [];
                $args = [];
                $page_num = $event->try_page_num(0);
                if (!empty($_GET["time-start"])) {
                    $wheres[] = "date_sent > :time_start";
                    $args["time_start"] = $_GET["time-start"];
                }
                if (!empty($_GET["time-end"])) {
                    $wheres[] = "date_sent < :time_end";
                    $args["time_end"] = $_GET["time-end"];
                }
                if (!empty($_GET["module"])) {
                    $wheres[] =  $database->scoreql_to_sql("SCORE_STRNORM(section) = SCORE_STRNORM(:module)");
                    $args["module"] = $_GET["module"];
                }
                if (!empty($_GET["user"])) {
                    if ($database->get_driver_name() == DatabaseDriver::PGSQL) {
                        if (preg_match("#\d+\.\d+\.\d+\.\d+(/\d+)?#", $_GET["user"])) {
                            # for some reason postgres won't use an index on lower(text(address)), but will text(address)?
                            $wheres[] =  $database->scoreql_to_sql("(SCORE_STRNORM(username) = SCORE_STRNORM(:user1) OR text(address) = :user2)");
                            $args["user1"] = $_GET["user"];
                            $args["user2"] = $_GET["user"] . "/32";
                        } else {
                            $wheres[] = $database->scoreql_to_sql("SCORE_STRNORM(username) = SCORE_STRNORM(:user)");
                            $args["user"] = $_GET["user"];
                        }
                    } else {
                        $wheres[] =  $database->scoreql_to_sql("(SCORE_STRNORM(username) = SCORE_STRNORM(:user1) OR SCORE_STRNORM(address) = SCORE_STRNORM(:user2))");
                        $args["user1"] = $_GET["user"];
                        $args["user2"] = $_GET["user"];
                    }
                }
                if (!empty($_GET["priority"])) {
                    $wheres[] = "priority >= :priority";
                    $args["priority"] = int_escape($_GET["priority"]);
                } else {
                    $wheres[] = "priority >= :priority";
                    $args["priority"] = 20;
                }
                if (!empty($_GET["message"])) {
                    $wheres[] = $database->scoreql_to_sql("SCORE_STRNORM(message) LIKE SCORE_STRNORM(:message)");
                    $args["message"] = "%" . $_GET["message"] . "%";
                }
                $where = "";
                if (count($wheres) > 0) {
                    $where = "WHERE ";
                    $where .= join(" AND ", $wheres);
                }

                $limit = 50;
                $offset = ($page_num-1) * $limit;
                $page_total = $cache->get("event_log_length");
                if (!$page_total) {
                    $page_total = $database->get_one("SELECT count(*) FROM score_log $where", $args);
                    // don't cache a length of zero when the extension is first installed
                    if ($page_total > 10) {
                        $cache->set("event_log_length", $page_total, 600);
                    }
                }

                $args["limit"] = $limit;
                $args["offset"] = $offset;
                $events = $database->get_all("SELECT * FROM score_log $where ORDER BY id DESC LIMIT :limit OFFSET :offset", $args);

                $this->theme->display_events($events, $page_num, 100);
            }
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event)
    {
        global $user;
        if ($event->parent==="system") {
            if ($user->can(Permissions::VIEW_EVENTLOG)) {
                $event->add_nav_link("event_log", new Link('log/view'), "Event Log");
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event)
    {
        global $user;
        if ($user->can(Permissions::VIEW_EVENTLOG)) {
            $event->add_link("Event Log", make_link("log/view"));
        }
    }

    public function onLog(LogEvent $event)
    {
        global $config, $database, $user;

        $username = ($user && $user->name) ? $user->name : "null";

        // not installed yet...
        if ($this->get_version("ext_log_database_version") < 1) {
            return;
        }

        if ($event->priority >= $config->get_int("log_db_priority")) {
            $database->execute("
				INSERT INTO score_log(date_sent, section, priority, username, address, message)
				VALUES(now(), :section, :priority, :username, :address, :message)
			", [
                "section"=>$event->section, "priority"=>$event->priority, "username"=>$username,
                "address"=>$_SERVER['REMOTE_ADDR'], "message"=>$event->message
            ]);
        }
    }
}
