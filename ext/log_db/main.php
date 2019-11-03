<?php

class LogDatabase extends Extension
{
    public function onInitExt(InitExtEvent $event)
    {
        global $database;
        global $config;

        if ($config->get_int("ext_log_database_version") < 1) {
            $database->create_table("score_log", "
				id SCORE_AIPK,
				date_sent SCORE_DATETIME NOT NULL,
				section VARCHAR(32) NOT NULL,
				username VARCHAR(32) NOT NULL,
				address SCORE_INET NOT NULL,
				priority INT NOT NULL,
				message TEXT NOT NULL
			");
            //INDEX(section)
            $config->set_int("ext_log_database_version", 1);
        }

        $config->set_default_int("log_db_priority", SCORE_LOG_INFO);
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
                $page_num = int_escape($event->get_arg(0));
                if ($page_num <= 0) {
                    $page_num = 1;
                }
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
                            $wheres[] =  $database->scoreql_to_sql("(SCORE_STRNORM(username) = SCORE_STRNORM(:user1) OR SCORE_STRNORM(text(address)) = SCORE_STRNORM(:user2))");
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
        if ($config->get_int("ext_log_database_version") < 1) {
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
