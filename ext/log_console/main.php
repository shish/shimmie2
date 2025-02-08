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
        $fp = @fopen("/dev/tty", "w");
        if ($fp) {
            fclose($fp);
        } else {
            $sb = $event->panel->create_new_block("Logging (Console)");
            $sb->add_label("This extension requires a terminal (<code>/dev/tty</code>) to work.<br>If you're using docker, add the <code>-t</code> flag to your <code>docker run</code> command.");
            return;
        }
        $sb = $event->panel->create_new_block("Logging (Console)");
        $sb->add_bool_option(LogConsoleConfig::LOG_ACCESS, "Log page requests: ");
        $sb->add_bool_option(LogConsoleConfig::COLOUR, "<br>Log with colour: ");
        $sb->add_choice_option(LogConsoleConfig::LEVEL, LOGGING_LEVEL_NAMES_TO_LEVELS, "<br>Debug Level: ");
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
        global $config, $user;
        $username = ($user && $user->name) ? $user->name : "Anonymous";
        $str = join(" ", [
            date(DATE_ISO8601),
            get_real_ip(),
            $event->section,
            $username,
            $event->message
        ]);
        if ($config->get_bool(LogConsoleConfig::COLOUR)) {
            if ($event->priority >= SCORE_LOG_WARNING) {
                // red
                $COL = "\033[0;31m";
            } elseif ($event->priority >= SCORE_LOG_INFO) {
                // default
                $COL = "";
            } elseif ($event->priority >= SCORE_LOG_NOTSET) {
                // blue
                $COL = "\033[0;34m";
            } else {
                // priority < 0 ???
                // magenta
                $COL = "\033[0;35m";
            }
            $str = "$COL$str\033[0m\n";
        } else {
            $str = "$str\n";
        }
        if (!defined("UNITTEST") && PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
            $fp = @fopen("/dev/stdout", "w");
            if ($fp) {
                fwrite($fp, $str);
                fclose($fp);
            }
        }
    }
}
