<?php

declare(strict_types=1);

namespace Shimmie2;

final class LogOTLP extends Extension
{
    public const KEY = "log_otlp";

    #[EventListener]
    public function onInitExt(InitExtEvent $event): void
    {
        $event->add_shutdown_handler(function () {
            Ctx::$tracer->flushLogs(Ctx::$config->get(OTLPCommonConfig::HOST));
        });
    }

    #[EventListener]
    public function onLog(LogEvent $event): void
    {
        Ctx::$tracer->logMessage(
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
