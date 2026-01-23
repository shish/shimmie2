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
            Ctx::$tracer->logCounter("shimmie.requests", 1, metadata: ["type" => $this->type]);
            // $this->stats["shimmie.$type.time"] = (ftime() - $_SERVER["REQUEST_TIME_FLOAT"])."|ms";
            // $this->stats["shimmie.$type.time-db"] = Ctx::$database->dbtime."|ms";
            Ctx::$tracer->logGauge("shimmie.memory", memory_get_peak_usage(true), metadata: ["type" => $this->type]);
            Ctx::$tracer->logGauge("shimmie.files", count(get_included_files()), metadata: ["type" => $this->type]);
            Ctx::$tracer->logGauge("shimmie.queries", Ctx::$database->query_count, metadata: ["type" => $this->type]);
            Ctx::$tracer->logGauge("shimmie.events", Ctx::$event_bus->event_count, metadata: ["type" => $this->type]);
            Ctx::$tracer->logGauge("shimmie.cache_hits", Ctx::$cache->get("__etc_cache_hits", -1), metadata: ["type" => $this->type]);
            Ctx::$tracer->logGauge("shimmie.cache_misses", Ctx::$cache->get("__etc_cache_misses", -1), metadata: ["type" => $this->type]);
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
