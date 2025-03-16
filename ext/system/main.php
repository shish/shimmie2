<?php

declare(strict_types=1);

namespace Shimmie2;

final class System extends Extension
{
    public const KEY = "system";

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page;

        if ($event->page_matches("system")) {
            $e = send_event(new PageSubNavBuildingEvent("system"));
            usort($e->links, fn (NavLink $a, NavLink $b) => $a->order - $b->order);
            $link = $e->links[0]->link;

            $page->set_redirect($link);
            $page->set_mode(PageMode::REDIRECT);
        }
    }
    public function onPageNavBuilding(PageNavBuildingEvent $event): void
    {
        $event->add_nav_link(make_link('system'), "System", category: "system");
    }
}
