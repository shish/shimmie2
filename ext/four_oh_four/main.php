<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\rawHTML;

class FourOhFour extends Extension
{
    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page;
        // hax.
        if ($page->mode == PageMode::PAGE && $this->count_main($page->blocks) == 0) {
            log_debug("four_oh_four", "Hit 404: {$event->path}");
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
            if ($block->section == "main" && $block->is_content) {
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
