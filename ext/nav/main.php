<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

/**
 * @extends PartListBuildingEvent<HTMLElement|string>
 */
final class NavBuildingEvent extends PartListBuildingEvent
{
}

/** @extends Extension<NavTheme> */
final class Nav extends Extension
{
    public const string KEY = NavInfo::KEY;

    public function get_priority(): int
    {
        // Before 404
        return 98;
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        $nbe = send_event(new NavBuildingEvent());

        $nav = Ctx::$page->get_navigation();
        foreach ($nav->get_parts() as $part) {
            $nbe->add_part($part["html"], $part["position"]);
        }

        $this->theme->display_nav_pagination($nbe, $nav->prev, $nav->index ?? make_link(), $nav->next);

        [$main_links, $sub_links] = $this->get_nav_links();

        $this->theme->display_main_links($nbe, $main_links);
        $this->theme->display_sub_links($nbe, $sub_links);
        $this->theme->display_navigation_block($nbe);
    }

    /**
     * @return array{0: NavLink[], 1: NavLink[]}
     */
    public function get_nav_links(): array
    {
        $pnbe = send_event(new PageNavBuildingEvent());
        $nav_links = $pnbe->links;

        $sub_links = [];
        // To save on event calls, we check if one of the top-level links has already been marked as active
        // If one is, we just query for sub-menu options under that one tab
        if ($pnbe->active_link !== null) {
            $psnbe = send_event(new PageSubNavBuildingEvent($pnbe->active_link->key));
            $sub_links = $psnbe->links;
        } else {
            // Otherwise we query for the sub-items under each of the tabs
            foreach ($nav_links as $link) {
                $psnbe = send_event(new PageSubNavBuildingEvent($link->key));

                // If the active link has been detected, we break out
                if ($psnbe->active_link !== null) {
                    $sub_links = $psnbe->links;
                    $link->active = true;
                    break;
                }
            }
        }

        usort($nav_links, fn (NavLink $a, NavLink $b) => $a->order - $b->order);
        usort($sub_links, fn (NavLink $a, NavLink $b) => $a->order - $b->order);

        return [$nav_links, $sub_links];
    }
}
