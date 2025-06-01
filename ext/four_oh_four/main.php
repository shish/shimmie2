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
            $err = new ObjectNotFound("No handler could be found for the page '{$event->path}'");
            $err->debug = \MicroHTML\PRE(\MicroHTML\rawHTML(\Safe\json_encode([
                "args" => $event->args,
                "theme" => get_theme(),
                "nice_urls" => Url::are_niceurls_enabled(),
                "base" => (string)Url::base(),
                "absolute_base" => (string)Url::base()->asAbsolute(),
                "base_link" => (string)make_link(""),
                "search_example" => (string)search_link(["AC/DC"]),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)));
            throw $err;
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
