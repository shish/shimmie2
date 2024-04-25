<?php

declare(strict_types=1);

namespace Shimmie2;

_d("STATSD_HOST", null);

class StatsDInterface extends Extension
{
    /** @var array<string, string> */
    public static array $stats = [];

    private function _stats(string $type): void
    {
        global $_shm_event_count, $cache, $database, $_shm_load_start;
        $time = ftime() - $_shm_load_start;
        StatsDInterface::$stats["shimmie.$type.hits"] = "1|c";
        StatsDInterface::$stats["shimmie.$type.time"] = "$time|ms";
        StatsDInterface::$stats["shimmie.$type.time-db"] = "{$database->dbtime}|ms";
        StatsDInterface::$stats["shimmie.$type.memory"] = memory_get_peak_usage(true)."|c";
        StatsDInterface::$stats["shimmie.$type.files"] = count(get_included_files())."|c";
        StatsDInterface::$stats["shimmie.$type.queries"] = $database->query_count."|c";
        StatsDInterface::$stats["shimmie.$type.events"] = $_shm_event_count."|c";
        StatsDInterface::$stats["shimmie.$type.cache-hits"] = $cache->get("__etc_cache_hits", -1)."|c";
        StatsDInterface::$stats["shimmie.$type.cache-misses"] = $cache->get("__etc_cache_misses", -1)."|c";
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        $this->_stats("overall");

        if ($event->page_starts_with("post/view")) {  # 40%
            $this->_stats("post-view");
        } elseif ($event->page_starts_with("post/list")) {  # 30%
            $this->_stats("post-list");
        } elseif ($event->page_starts_with("user")) {
            $this->_stats("user");
        } elseif ($event->page_starts_with("upload")) {
            $this->_stats("upload");
        } elseif ($event->page_starts_with("rss")) {
            $this->_stats("rss");
        } elseif ($event->page_starts_with("api")) {
            $this->_stats("api");
        } else {
            $this->_stats("other");
        }

        // @phpstan-ignore-next-line
        if (STATSD_HOST) {
            $this->send(STATSD_HOST, StatsDInterface::$stats, 1.0);
        }

        StatsDInterface::$stats = [];
    }

    public function onUserCreation(UserCreationEvent $event): void
    {
        StatsDInterface::$stats["shimmie_events.user_creations"] = "1|c";
    }

    public function onDataUpload(DataUploadEvent $event): void
    {
        StatsDInterface::$stats["shimmie_events.uploads"] = "1|c";
    }

    public function onCommentPosting(CommentPostingEvent $event): void
    {
        StatsDInterface::$stats["shimmie_events.comments"] = "1|c";
    }

    public function onImageInfoSet(ImageInfoSetEvent $event): void
    {
        StatsDInterface::$stats["shimmie_events.info-sets"] = "1|c";
    }

    public function get_priority(): int
    {
        return 99;
    }

    /**
     * @param array<string, string> $data
     */
    private function send(string $host, array $data, float $sampleRate = 1): void
    {
        // sampling
        $sampledData = [];

        if ($sampleRate < 1) {
            foreach ($data as $stat => $value) {
                if ((mt_rand() / mt_getrandmax()) <= $sampleRate) {
                    $sampledData[$stat] = "$value|@$sampleRate";
                }
            }
        } else {
            $sampledData = $data;
        }

        if (empty($sampledData)) {
            return;
        }

        // Wrap this in a try/catch - failures in any of this should be silently ignored
        try {
            $parts = explode(":", $host);
            $host = $parts[0];
            $port = (int)$parts[1];
            $fp = fsockopen("udp://$host", $port, $errno, $errstr);
            if (!$fp) {
                return;
            }
            foreach ($sampledData as $stat => $value) {
                fwrite($fp, "$stat:$value");
            }
            fclose($fp);
        } catch (\Exception $e) {
            // ignore any failures.
        }
    }
}
