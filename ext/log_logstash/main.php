<?php

declare(strict_types=1);

namespace Shimmie2;

class LogLogstash extends Extension
{
    public function onLog(LogEvent $event)
    {
        global $user;

        try {
            $data = [
                "@type" => "shimmie",
                "@message" => $event->message,
                "@fields" => [
                    "username" => ($user && $user->name) ? $user->name : "Anonymous",
                    "section" => $event->section,
                    "priority" => $event->priority,
                    "time" => $event->time,
                ],
                #"@request" => $_SERVER,
                "@request" => [
                    "UID" => get_request_id(),
                    "REMOTE_ADDR" => get_real_ip(),
                ],
            ];

            $this->send_data($data);
        } catch (\Exception $e) {
            // we can't log that logging is broken
        }
    }

    private function send_data($data)
    {
        global $config;

        $host = $config->get_string("log_logstash_host");
        if (!$host) {
            return;
        }

        try {
            $parts = explode(":", $host);
            $host = $parts[0];
            $port = (int)$parts[1];
            $fp = fsockopen("udp://$host", $port, $errno, $errstr);
            if (! $fp) {
                return;
            }
            fwrite($fp, json_encode($data));
            fclose($fp);
        } catch (\Exception $e) {
            // we can't log that logging is broken
        }
    }
}
