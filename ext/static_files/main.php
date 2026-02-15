<?php

declare(strict_types=1);

namespace Shimmie2;

final class StaticFiles extends Extension
{
    public const KEY = "static_files";

    #[EventListener(priority: 98)] // Before 404
    public function onPageRequest(PageRequestEvent $event): void
    {
        $page = Ctx::$page;
        // hax.
        if ($page->mode === PageMode::PAGE && $this->count_main($page->blocks) === 0) {
            $f_pagename = \Safe\preg_replace("/[^a-z_\-\.]+/", "_", implode('/', $event->args));
            $theme_name = Ctx::$config->get(SetupConfig::THEME);

            $theme_file = "themes/$theme_name/static/$f_pagename";
            $static_file = "ext/static_files/static/$f_pagename";

            if (file_exists($theme_file) || file_exists($static_file)) {
                $file = new Path(file_exists($theme_file) ? $theme_file : $static_file);

                $page->add_http_header("Cache-control: public, max-age=600");
                $page->add_http_header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 600) . ' GMT');
                $page->set_data(MimeType::get_for_file($file), $file->get_contents());
            }
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
}
