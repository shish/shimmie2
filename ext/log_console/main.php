<?php

declare(strict_types=1);

namespace Shimmie2;

final class LogConsole extends Extension
{
    public const KEY = "log_console";

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if (
            Ctx::$config->get(LogConsoleConfig::LOG_ACCESS) &&
            isset($_SERVER['REQUEST_URI'])
        ) {
            $this->log(new LogEvent(
                "access",
                LogLevel::INFO->value,
                "{$_SERVER['REQUEST_METHOD']} {$_SERVER['REQUEST_URI']}"
            ));
        }

        /*
        if ($event->page_matches("log_test")) {
            Log::debug("log_console", "Hello debug!");
            Log::info("log_console", "Hello info!");
            Log::warning("log_console", "Hello warning!");
            Ctx::$page->set_data(MimeType::TEXT, "You should see something in the log\n");
        }
        */
    }

    #[EventListener]
    public function onLog(LogEvent $event): void
    {
        if ($event->priority >= Ctx::$config->get(LogConsoleConfig::LEVEL)) {
            $this->log($event);
        }
    }

    private function log(LogEvent $event): void
    {
        if (defined("UNITTEST") || PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            return;
        }

        $username = isset(Ctx::$user) ? Ctx::$user->name : "Anonymous";

        $levelName = "[unknown]";
        $color = "\033[0;35m"; # purple for unknown levels
        if ($event->priority >= LogLevel::CRITICAL->value) {
            $levelName = "[critical]";
            $color = "\033[0;31m"; # red
        } elseif ($event->priority >= LogLevel::ERROR->value) {
            $levelName = "[error]";
            $color = "\033[0;91m"; # high intensity red
        } elseif ($event->priority >= LogLevel::WARNING->value) {
            $levelName = "[warning]";
            $color = "\033[0;33m"; # yellow
        } elseif ($event->priority >= LogLevel::INFO->value) {
            $levelName = "[info]";
            $color = ""; # none for info
        } elseif ($event->priority >= LogLevel::DEBUG->value) {
            $levelName = "[debug]";
            $color = "\033[0;94m"; # high intensity blue
        }

        $str = join(" ", [
            date("Y-m-d H:i:s"),
            "[".$event->section."]",
            $levelName,
            "[".Network::get_real_ip()." (".$username.")]",
            $event->message
        ]);

        if (strlen($color) > 0 && Ctx::$config->get(LogConsoleConfig::COLOUR)) {
            $str = "$color$str\033[0m\n";
        } else {
            $str = "$str\n";
        }

        $fp = @fopen(Ctx::$config->get(LogConsoleConfig::DEVICE), "w");
        if ($fp) {
            fwrite($fp, $str);
            fclose($fp);
        }
    }
}
