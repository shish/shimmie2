<?php

declare(strict_types=1);

namespace Shimmie2;

final class LogOTLP extends Extension
{
    public const KEY = "log_otlp";

    public function onLog(LogEvent $event): void
    {
        if (!isset(OTLPCommon::$client)) {
            return;
        }
        OTLPCommon::$client->logMessage(
            $event->message,
            // level: $event->priority,
            attributes: [
                'username' => isset(Ctx::$user) ? Ctx::$user->name : 'Anonymous',
                'section' => $event->section,
                'remoteAddr' => (string)Network::get_real_ip(),
            ],
        );
    }
}
