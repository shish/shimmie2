<?php declare(strict_types=1);

class FourOhFour extends Extension
{
    public function onPageRequest(PageRequestEvent $event)
    {
        global $page;
        // hax.
        if ($page->mode == PageMode::PAGE && (!isset($page->blocks) || $this->count_main($page->blocks) == 0)) {
            $h_pagename = html_escape(implode('/', $event->args));
            log_debug("four_oh_four", "Hit 404: $h_pagename");
            $page->set_code(404);
            $page->set_title("404");
            $page->set_heading("404 - No Handler Found");
            $page->add_block(new NavBlock());
            $page->add_block(new Block("Explanation", "No handler could be found for the page '$h_pagename'"));
        }
    }

    private function count_main($blocks): int
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
