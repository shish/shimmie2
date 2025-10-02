<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;

final class LogOTLP extends Extension
{
    public const KEY = "log_otlp";

    /** @var array<array<string, mixed>> $logPackets */
    private array $logPackets = [];
    private string $requestTraceId = "";
    private string $requestSpanId = "";

    public function onInitExt(InitExtEvent $event): void
    {
        [$version, $traceId, $spanId, $flags] = isset($_SERVER['HTTP_TRACEPARENT'])
            ? explode('-', $_SERVER['HTTP_TRACEPARENT'])
            : [null, null, null, null];

        $this->requestTraceId = $traceId ?: bin2hex(random_bytes(16));
        $this->requestSpanId = $spanId ?: bin2hex(random_bytes(8));

        $event->add_shutdown_handler(function () {
            $otlpPacket = $this->get_data($this->logPackets);
            $data = \Safe\json_encode($otlpPacket);
            $this->send_data($data);
        });
    }

    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('debug:log-otlp')
            ->addArgument('message', InputArgument::REQUIRED)
            ->setDescription('Send a log to OTLP collector')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $message = $input->getArgument('message');
                $entry = $this->log_event_to_packet(new LogEvent(
                    "cli",
                    LogLevel::INFO->value,
                    $message
                ));
                $data = \Safe\json_encode($this->get_data([$entry]));
                $this->send_data($data);
                $host = Ctx::$config->get(LogOTLPConfig::HOST);
                $output->writeln("Sent log to $host!");
                return Command::SUCCESS;
            });
    }
    /**
     * @param array<array<string, mixed>> $logPackets
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function get_data(array $logPackets): array
    {
        return [
            "resourceLogs" => [
                [
                    "resource" => [
                        "attributes" => [
                            [
                                "key" => "service.name",
                                "value" => ["stringValue" => "shimmie2"],
                            ],
                            [
                                "key" => "service.instance.id",
                                "value" => ["stringValue" => gethostname() ?: "unknown"],
                            ],
                        ],
                    ],
                    "scopeLogs" => [
                        [
                            "scope" => [
                                "name" => "shimmie2",
                                "version" => SysConfig::getVersion(),
                            ],
                            "logRecords" => $logPackets,
                        ],
                    ],
                ],
            ],
        ];
    }

    private function send_data(string $data): void
    {
        $host = Ctx::$config->get(LogOTLPConfig::HOST);
        $ch = \Safe\curl_init($host);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        \Safe\curl_exec($ch);
        curl_close($ch);
    }

    /**
     * @return array<string, mixed>
     */
    private function log_event_to_packet(LogEvent $event): array
    {
        // TODO: get spanId from context if available
        $username = isset(Ctx::$user) ? Ctx::$user->name : "Anonymous";
        return [
            "timeUnixNano" => (int)(ftime() * 1_000_000_000),
            "observedTimeUnixNano" => (int)(ftime() * 1_000_000_000),
            "severityNumber" => $event->priority,
            "severityText" => "Information",
            "traceId" => $this->requestTraceId,
            "spanId" => $this->requestSpanId,
            "body" => [
                "stringValue" => $event->message,
            ],
            "attributes" => [
                [
                    "key" => "username",
                    "value" => ["stringValue" => $username],
                ],
                [
                    "key" => "section",
                    "value" => ["stringValue" => $event->section],
                ],
                [
                    "key" => "remoteAddr",
                    "value" => ["stringValue" => (string)Network::get_real_ip()],
                ],
            ],
        ];
    }

    public function onLog(LogEvent $event): void
    {
        $this->logPackets[] = $this->log_event_to_packet($event);
    }
}
