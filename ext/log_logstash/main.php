<?php

declare(strict_types=1);

namespace Shimmie2;

final class LogLogstash extends Extension
{
    public const KEY = "log_logstash";

    #[EventListener]
    public function onLog(LogEvent $event): void
    {
        $username = isset(Ctx::$user) ? Ctx::$user->name : "Anonymous";

        try {
            $data = [
                "@type" => "shimmie",
                "@message" => $event->message,
                "@fields" => [
                    "username" => $username,
                    "section" => $event->section,
                    "priority" => $event->priority,
                    "time" => time(),
                ],
                #"@request" => $_SERVER,
                "@request" => [
                    "UID" => Log::get_request_id(),
                    "REMOTE_ADDR" => Network::get_real_ip(),
                ],
            ];

            $this->send_data($data);
        } catch (\Exception $e) {
            // we can't log that logging is broken
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function send_data(array $data): void
    {
        $host = Ctx::$config->get(LogLogstashConfig::HOST);
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
            fwrite($fp, \Safe\json_encode($data));
            fclose($fp);
        } catch (\Exception $e) {
            // we can't log that logging is broken
        }
    }
}
