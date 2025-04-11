<?php

declare(strict_types=1);

namespace Shimmie2;

final class FourOhFour extends Extension
{
    public const KEY = "four_oh_four";

    public function onPageRequest(PageRequestEvent $event): void
    {
        // hax.
        if (Ctx::$page->mode === PageMode::PAGE && $this->count_main(Ctx::$page->blocks) === 0) {
            Log::debug("four_oh_four", "Hit 404: {$event->path}");
            throw new ObjectNotFound("No handler could be found for the page '{$event->path}'");
        }
    }

    /**
     * @param Block[] $blocks
     */
    private function count_main(array $blocks): int
    {
        $n = 0;
        foreach ($blocks as $block) {
            if ($block->section === "main" && $block->is_content) {
                $n++;
            } // more hax.
        }
        return $n;
    }

    public function get_priority(): int
    {
        return 99;
    }
}
