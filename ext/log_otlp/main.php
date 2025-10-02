<?php

declare(strict_types=1);

namespace Shimmie2;

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
            $otlpPacket = [
                "resourceLogs" => [
                    "resource" => [
                        "attributes" => [
                            [
                                "key" => "service.name",
                                "value" => ["stringValue" => "shimmie2"],
                            ],
                            [
                                "key" => "host.name",
                                "value" => ["stringValue" => gethostname() ?: "unknown"],
                            ],
                        ],
                    ],
                    "scopeLogs" => [
                        "scope" => [
                            "name" => "shimmie2",
                            "version" => SysConfig::getVersion(),
                        ],
                        "logRecords" => $this->logPackets,
                    ],
                ]
            ];

            $host = Ctx::$config->get(LogOTLPConfig::HOST);
            if (!$host) {
                return;
            }
            $data = \Safe\json_encode($otlpPacket);
            $ch = curl_init($host);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            curl_exec($ch);
            curl_close($ch);
        });
    }

    public function onLog(LogEvent $event): void
    {
        // TODO: get spanId from context if available
        $username = isset(Ctx::$user) ? Ctx::$user->name : "Anonymous";
        $this->logPackets[] = [
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
                    "value" => ["stringValue" => Network::get_real_ip()],
                ],
            ],
        ];
    }
}
