<?php

declare(strict_types=1);

namespace Shimmie2;

final class RobotsBuildingEvent extends Event
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

final class RobotsTxt extends Extension
{
    public const KEY = "robots_txt";

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("robots.txt")) {
            $rbe = send_event(new RobotsBuildingEvent());
            Ctx::$page->set_data(MimeType::TEXT, join("\n", $rbe->parts) . "\n");
        }
    }


    #[EventListener]
    public function onRobotsBuilding(RobotsBuildingEvent $event): void
    {
        $domain = Ctx::$config->get(RobotsTxtConfig::CANONICAL_DOMAIN);
        if ($domain !== null && $_SERVER['HTTP_HOST'] !== $domain) {
            $event->add_disallow("");
        }
    }
}
