<?php

declare(strict_types=1);

namespace Shimmie2;

class RobotsBuildingEvent extends Event
{
    /** @var string[] */
    public array $parts = [
        "User-agent: *",
        // Site is rate limited to 1 request / sec,
        // returns 503 for more than that
        "Crawl-delay: 3",
    ];

    public function add_disallow(string $path): void
    {
        $this->parts[] = "Disallow: /$path";
    }
}

class StaticFiles extends Extension
{
    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page;

        if ($event->page_matches("robots.txt")) {
            $rbe = send_event(new RobotsBuildingEvent());
            $page->set_mode(PageMode::DATA);
            $page->set_mime("text/plain");
            $page->set_data(join("\n", $rbe->parts));
        }

        // hax.
        if ($page->mode == PageMode::PAGE && (!isset($page->blocks) || $this->count_main($page->blocks) == 0)) {
            $h_pagename = html_escape(implode('/', $event->args));
            $f_pagename = preg_replace("/[^a-z_\-\.]+/", "_", $h_pagename);
            $theme_name = $config->get_string(SetupConfig::THEME, "default");

            $theme_file = "themes/$theme_name/static/$f_pagename";
            $static_file = "ext/static_files/static/$f_pagename";

            if (file_exists($theme_file) || file_exists($static_file)) {
                $filename = file_exists($theme_file) ? $theme_file : $static_file;

                $page->add_http_header("Cache-control: public, max-age=600");
                $page->add_http_header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 600) . ' GMT');
                $page->set_mode(PageMode::DATA);
                $page->set_data(\Safe\file_get_contents($filename));
                $page->set_mime(MimeType::get_for_file($filename));
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
            if ($block->section == "main" && $block->is_content) {
                $n++;
            } // more hax.
        }
        return $n;
    }

    public function get_priority(): int
    {
        return 98;
    }  // before 404
}
