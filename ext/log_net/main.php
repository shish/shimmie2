<?php

declare(strict_types=1);

namespace Shimmie2;

final class LogNet extends Extension
{
    public const KEY = "log_net";
    private int $count = 0;

    public function onLog(LogEvent $event): void
    {
        global $user;

        if ($event->priority > 10) {
            $this->count++;
            if ($this->count < 10) {
                $username = ($user && $user->name) ? $user->name : "Anonymous";
                $str = sprintf("%-15s %-10s: %s", Network::get_real_ip(), $username, $event->message);
                $this->msg($str);
            } elseif ($this->count == 10) {
                $this->msg('suppressing flood, check the web log');
            }
        }
    }

    private function msg(string $data): void
    {
        global $config;
        $host = $config->get_string(LogNetConfig::HOST);

        if (!$host) {
            return;
        }

        try {
            $parts = explode(":", $host);
            $host = $parts[0];
            $port = (int)$parts[1];
            $fp = fsockopen("udp://$host", $port, $errno, $errstr);
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
