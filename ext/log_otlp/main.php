<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroOTLP\LogSeverity;

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
            severity: $this->convertLogLevel($event->priority),
            attributes: [
                'username' => isset(Ctx::$user) ? Ctx::$user->name : 'Anonymous',
                'section' => $event->section,
                'remoteAddr' => (string)Network::get_real_ip(),
            ],
        );
    }

    /**
     * Convert Shimmie LogLevel to MicroOTLP LogSeverity
     */
    private function convertLogLevel(int $priority): LogSeverity
    {
        return match (true) {
            $priority >= LogLevel::CRITICAL->value => LogSeverity::FATAL,
            $priority >= LogLevel::ERROR->value => LogSeverity::ERROR,
            $priority >= LogLevel::WARNING->value => LogSeverity::WARN,
            $priority >= LogLevel::INFO->value => LogSeverity::INFO,
            $priority >= LogLevel::DEBUG->value => LogSeverity::DEBUG,
            default => LogSeverity::TRACE,
        };
    }
}
