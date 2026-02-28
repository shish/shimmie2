<?php

declare(strict_types=1);

namespace Shimmie2;

final class MetricsOTLP extends Extension
{
    public const KEY = "metrics_otlp";

    private string $type = "other";

    #[EventListener]
    public function onInitExt(InitExtEvent $event): void
    {
        $event->add_shutdown_handler(function () {
            Ctx::$tracer->logCounter(
                "shimmie.global.requests",
                1,
                unit: "requests",
                attributes: ["type" => $this->type],
            );
            Ctx::$tracer->logCounter(
                "shimmie.global.queries",
                Ctx::$database->query_count,
                unit: "queries",
                attributes: ["type" => $this->type],
            );
            Ctx::$tracer->logCounter(
                "shimmie.global.events",
                Ctx::$event_bus->event_count,
                unit: "events",
                attributes: ["type" => $this->type],
            );
            Ctx::$tracer->logCounter(
                "shimmie.global.cache_hits",
                Ctx::$cache->get("__etc_cache_hits", -1),
                unit: "hits",
                attributes: ["type" => $this->type],
            );
            Ctx::$tracer->logCounter(
                "shimmie.global.cache_misses",
                Ctx::$cache->get("__etc_cache_misses", -1),
                unit: "misses",
                attributes: ["type" => $this->type],
            );

            $durationBounds = [
                0.005, 0.010, 0.025,
                0.050, 0.100, 0.250,
                0.500, 1.000, 2.500,
                5.000, 10.000
            ];
            Ctx::$tracer->logHistogramValue(
                "shimmie.request.duration",
                microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],
                $durationBounds,
                unit: "seconds",
                attributes: ["type" => $this->type],
            );
            Ctx::$tracer->logHistogramValue(
                "shimmie.request.db_duration",
                Ctx::$database->dbtime,
                $durationBounds,
                unit: "seconds",
                attributes: ["type" => $this->type],
            );

            $memoryBounds = [
                1 << 20,   // 1MB
                2 << 20,   // 2MB
                4 << 20,
                8 << 20,
                16 << 20,
                32 << 20,
                64 << 20,
            ];
            Ctx::$tracer->logHistogramValue(
                "shimmie.request.memory",
                memory_get_peak_usage(true),
                $memoryBounds,
                unit: "bytes",
                attributes: ["type" => $this->type],
            );

            $countBounds = [
                0, 1, 2, 5, 10, 20, 50, 100, 200, 500
            ];
            Ctx::$tracer->logHistogramValue(
                "shimmie.request.queries",
                Ctx::$database->query_count,
                $countBounds,
                unit: "queries",
                attributes: ["type" => $this->type],
            );
            Ctx::$tracer->logHistogramValue(
                "shimmie.request.events",
                Ctx::$event_bus->event_count,
                $countBounds,
                unit: "events",
                attributes: ["type" => $this->type],
            );
            Ctx::$tracer->logHistogramValue(
                "shimmie.request.cache_hits",
                Ctx::$cache->get("__etc_cache_hits", -1),
                $countBounds,
                unit: "hits",
                attributes: ["type" => $this->type],
            );
            Ctx::$tracer->logHistogramValue(
                "shimmie.request.cache_misses",
                Ctx::$cache->get("__etc_cache_misses", -1),
                $countBounds,
                unit: "misses",
                attributes: ["type" => $this->type],
            );

            Ctx::$tracer->flushMetrics(Ctx::$config->get(OTLPCommonConfig::HOST));
        });
    }

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_starts_with("post/view")) {  # 40%
            $this->type = "post-view";
        } elseif ($event->page_starts_with("post/list")) {  # 30%
            $this->type = "post-list";
        } elseif ($event->page_starts_with("user")) {
            $this->type = "user";
        } elseif ($event->page_starts_with("upload")) {
            $this->type = "upload";
        } elseif ($event->page_starts_with("rss")) {
            $this->type = "rss";
        } elseif ($event->page_starts_with("api")) {
            $this->type = "api";
        }
    }
}
