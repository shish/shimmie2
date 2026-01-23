<?php

declare(strict_types=1);

namespace Shimmie2;

final class LogNet extends Extension
{
    public const KEY = "log_net";
    private int $count = 0;

    #[EventListener]
    public function onLog(LogEvent $event): void
    {
        if ($event->priority > 10) {
            $this->count++;
            if ($this->count < 10) {
                $username = isset(Ctx::$user) ? Ctx::$user->name : "Anonymous";
                $str = sprintf("%-15s %-10s: %s", Network::get_real_ip(), $username, $event->message);
                $this->msg($str);
            } elseif ($this->count === 10) {
                $this->msg('suppressing flood, check the web log');
            }
        }
    }

    private function msg(string $data): void
    {
        $host = Ctx::$config->get(LogNetConfig::HOST);

        if (!$host) {
            return;
        }

        try {
            $parts = explode(":", $host);
            $host = $parts[0];
            $port = (int)$parts[1];
            $fp = fsockopen("udp://$host", $port);
            if (!$fp) {
                return;
            }
            fwrite($fp, "$data\n");
            fclose($fp);
        } catch (\Exception $e) {
            /* logging errors shouldn't break everything */
        }
    }
}
