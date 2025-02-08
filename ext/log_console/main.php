<?php

declare(strict_types=1);

namespace Shimmie2;

class LogConsole extends Extension
{
    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_bool(LogConsoleConfig::LOG_ACCESS, true);
        $config->set_default_bool(LogConsoleConfig::COLOUR, true);
        $config->set_default_int(LogConsoleConfig::LEVEL, SCORE_LOG_INFO);
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Logging (Console)");
        $sb->add_bool_option(LogConsoleConfig::LOG_ACCESS, "Log page requests: ");
        $sb->add_bool_option(LogConsoleConfig::COLOUR, "<br>Log with colour: ");
        $sb->add_choice_option(LogConsoleConfig::LEVEL, LOGGING_LEVEL_NAMES_TO_LEVELS, "<br>Log Level: ");
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page;

        if (
            $config->get_bool(LogConsoleConfig::LOG_ACCESS) &&
            isset($_SERVER['REQUEST_URI'])
        ) {
            $this->log(new LogEvent(
                "access",
                SCORE_LOG_INFO,
                "{$_SERVER['REQUEST_METHOD']} {$_SERVER['REQUEST_URI']}"
            ));
        }

        /*
        if ($event->page_matches("log_test")) {
            log_debug("log_console", "Hello debug!");
            log_info("log_console", "Hello info!");
            log_warning("log_console", "Hello warning!");
            $page->set_mode(PageMode::DATA);
            $page->set_data("You should see something in the log\n");
        }
        */
    }

    public function onLog(LogEvent $event): void
    {
        global $config;
        if ($event->priority >= $config->get_int(LogConsoleConfig::LEVEL)) {
            $this->log($event);
        }
    }

    private function log(LogEvent $event): void
    {
        if (defined("UNITTEST") || PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            return;
        }

        global $config, $user;
        $username = ($user && $user->name) ? $user->name : "Anonymous";

        $levelName = "[unknown]";
        $color = "\033[0;35m"; # purple for unknown levels
        if ($event->priority >= SCORE_LOG_CRITICAL) {
            $levelName = "[critical]";
            $color = "\033[0;31m"; # red
        } elseif ($event->priority >= SCORE_LOG_ERROR) {
            $levelName = "[error]";
            $color = "\033[0;91m"; # high intensity red
        } elseif ($event->priority >= SCORE_LOG_WARNING) {
            $levelName = "[warning]";
            $color = "\033[0;33m"; # yellow
        } elseif ($event->priority >= SCORE_LOG_INFO) {
            $levelName = "[info]";
            $color = ""; # none for info
        } elseif ($event->priority >= SCORE_LOG_DEBUG) {
            $levelName = "[debug]";
            $color = "\033[0;94m"; # high intensity blue
        }

        $str = join(" ", [
            date("Y-m-d H:i:s"),
            "[".$event->section."]",
            $levelName,
            "[".get_real_ip()." (".$username.")]",
            $event->message
        ]);

        if (strlen($color) > 0 && $config->get_bool(LogConsoleConfig::COLOUR)) {
            $str = "$color$str\033[0m\n";
        } else {
            $str = "$str\n";
        }

        $fp = @fopen("php://stdout", "w");
        if ($fp) {
            fwrite($fp, $str);
            fclose($fp);
        }
    }
}
