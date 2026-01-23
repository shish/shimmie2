<?php

declare(strict_types=1);

namespace Shimmie2;

final class LinkScan extends Extension
{
    public const KEY = "link_scan";

    #[EventListener(priority: 10)] // be able to intercept post/list
    public function onPageRequest(PageRequestEvent $event): void
    {
        $search = $event->GET->get('search') ?? $event->POST->get('search');
        if ($event->page_matches("post/list") && $search !== null) {
            $trigger = Ctx::$config->get(LinkScanConfig::TRIGGER);
            if (\Safe\preg_match("#.*{$trigger}.*#", $search)) {
                $ids = $this->scan($search);
                Ctx::$page->set_redirect(search_link(["id=".implode(",", $ids)]));
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
        \Safe\preg_match_all("/post\/view\/(\d+)/", $text, $matches);
        foreach ($matches[1] as $match) {
            $img = Image::by_id((int)$match);
            if ($img) {
                $ids[] = $img->id;
            }
        }
        \Safe\preg_match_all("/\b([0-9a-fA-F]{32})\b/", $text, $matches);
        foreach ($matches[1] as $match) {
            $img = Image::by_hash($match);
            if ($img) {
                $ids[] = $img->id;
            }
        }
        return array_unique($ids);
    }
}
