<?php

declare(strict_types=1);

namespace Shimmie2;

final class LogCli extends Extension
{
    public const KEY = "log_cli";
    private static int $logLevel = LogLevel::WARNING->value;

    #[EventListener]
    public function onCliRun(CliRunEvent $event): void
    {
        $log_level = LogLevel::WARNING->value;
        if (true === $event->input->hasParameterOption(['--quiet', '-q'], true)) {
            $log_level = LogLevel::ERROR->value;
        } else {
            if (
                $event->input->hasParameterOption('-vvv', true)
                || $event->input->hasParameterOption('--verbose=3', true)
                || 3 === $event->input->getParameterOption('--verbose', false, true)
            ) {
                $log_level = LogLevel::DEBUG->value;
            } elseif (
                $event->input->hasParameterOption('-vv', true)
                || $event->input->hasParameterOption('--verbose=2', true)
                || 2 === $event->input->getParameterOption('--verbose', false, true)
            ) {
                $log_level = LogLevel::DEBUG->value;
            } elseif (
                $event->input->hasParameterOption('-v', true)
                || $event->input->hasParameterOption('--verbose=1', true)
                || $event->input->hasParameterOption('--verbose', true)
                || $event->input->getParameterOption('--verbose', false, true)
            ) {
                $log_level = LogLevel::INFO->value;
            }
        }
        self::$logLevel = $log_level;
    }

    #[EventListener]
    public function onLog(LogEvent $event): void
    {
        if ((PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') && !defined("UNITTEST")) {
            if (($event->priority >= self::$logLevel)) {
                print date("c") . " {$event->section}: {$event->message}\n";
                if (ob_get_length() > 0) {
                    ob_flush();
                }
            }
        }
    }
}
