<?php

declare(strict_types=1);

namespace Shimmie2;

class LinkScan extends Extension
{
    public function get_priority(): int
    {
        return 10; // be able to intercept post/list
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page;

        $search = $event->get_GET('search') ?? $event->get_POST('search') ?? "";
        if ($event->page_matches("post/list") && !empty($search)) {
            $trigger = $config->get_string("link_scan_trigger", "https?://");
            if (preg_match("#.*{$trigger}.*#", $search)) {
                $ids = $this->scan($search);
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(search_link(["id=".implode(",", $ids)]));
                $event->stop_processing = true;
            }
        }
    }

    /**
     * @return int[]
     */
    private function scan(string $text): array
    {
        $ids = [];
        $matches = [];
        preg_match_all("/post\/view\/(\d+)/", $text, $matches);
        foreach($matches[1] as $match) {
            $img = Image::by_id((int)$match);
            if ($img) {
                $ids[] = $img->id;
            }
        }
        preg_match_all("/\b([0-9a-fA-F]{32})\b/", $text, $matches);
        foreach($matches[1] as $match) {
            $img = Image::by_hash($match);
            if ($img) {
                $ids[] = $img->id;
            }
        }
        return array_unique($ids);
    }
}
