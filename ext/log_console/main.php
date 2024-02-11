<?php

declare(strict_types=1);

namespace Shimmie2;

class LogConsole extends Extension
{
    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_bool("log_console_access", true);
        $config->set_default_bool("log_console_colour", true);
        $config->set_default_int("log_console_level", SCORE_LOG_INFO);
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page;

        if (
            $config->get_bool("log_console_access") &&
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
        if ($event->priority >= $config->get_int("log_console_level")) {
            $this->log($event);
        }
    }

    private function log(LogEvent $event): void
    {
        global $config, $user;
        // TODO: colour based on event->priority
        $username = ($user && $user->name) ? $user->name : "Anonymous";
        $str = join(" ", [
            date(DATE_ISO8601),
            get_real_ip(),
            $event->section,
            $username,
            $event->message
        ]);
        if ($config->get_bool("log_console_colour")) {
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
            $fp = @fopen("/dev/tty", "w");
            if ($fp) {
                fwrite($fp, $str);
                fclose($fp);
            }
        }
    }
}
