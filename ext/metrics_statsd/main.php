<?php

declare(strict_types=1);

namespace Shimmie2;

final class StatsDInterface extends Extension
{
    public const KEY = "metrics_statsd";
    /** @var array<string, string> */
    private array $stats = [];
    private string $type = "other";

    #[EventListener]
    public function onInitExt(InitExtEvent $event): void
    {
        $this->stats = [];

        $event->add_shutdown_handler(function () {
            $this->_stats("overall");
            $this->_stats($this->type);
            $host = Ctx::$config->get(StatsDInterfaceConfig::HOST);
            $this->send($host, $this->stats);
        });
    }

    private function _stats(string $type): void
    {
        $this->stats["shimmie.$type.hits"] = "1|c";
        $this->stats["shimmie.$type.time"] = (ftime() - $_SERVER["REQUEST_TIME_FLOAT"])."|ms";
        $this->stats["shimmie.$type.time-db"] = Ctx::$database->dbtime."|ms";
        $this->stats["shimmie.$type.memory"] = memory_get_peak_usage(true)."|c";
        $this->stats["shimmie.$type.files"] = count(get_included_files())."|c";
        $this->stats["shimmie.$type.queries"] = Ctx::$database->query_count."|c";
        $this->stats["shimmie.$type.events"] = Ctx::$event_bus->event_count."|c";
        $this->stats["shimmie.$type.cache-hits"] = Ctx::$cache->get("__etc_cache_hits", -1)."|c";
        $this->stats["shimmie.$type.cache-misses"] = Ctx::$cache->get("__etc_cache_misses", -1)."|c";
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

    #[EventListener(priority: 99)]
    public function onUserCreation(UserCreationEvent $event): void
    {
        $this->stats["shimmie_events.user_creations"] = "1|c";
    }

    #[EventListener(priority: 99)]
    public function onDataUpload(DataUploadEvent $event): void
    {
        $this->stats["shimmie_events.uploads"] = "1|c";
    }

    #[EventListener(priority: 99)]
    public function onCommentPosting(CommentPostingEvent $event): void
    {
        $this->stats["shimmie_events.comments"] = "1|c";
    }

    #[EventListener(priority: 99)]
    public function onImageInfoSet(ImageInfoSetEvent $event): void
    {
        $this->stats["shimmie_events.info-sets"] = "1|c";
    }

    /**
     * @param array<string, string> $data
     */
    private function send(string $host, array $data): void
    {
        if (count($data) === 0) {
            return;
        }

        $parts = explode(":", $host);
        $host = $parts[0];
        $port = (int)$parts[1];
        $fp = @fsockopen("udp://$host", $port);
        if (!$fp) {
            return;
        }
        foreach ($data as $stat => $value) {
            fwrite($fp, "$stat:$value");
        }
        fclose($fp);
    }
}
