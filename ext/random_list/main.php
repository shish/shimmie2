<?php

declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<RandomListTheme> */
final class RandomList extends Extension
{
    public const KEY = "random_list";

    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("random")) {
            if ($event->GET->get('search')) {
                // implode(explode()) to resolve aliases and sanitise
                $search = SearchTerm::implode(SearchTerm::explode($event->GET->get('search')));
                if (empty($search)) {
                    Ctx::$page->set_redirect(make_link("random"));
                } else {
                    Ctx::$page->set_redirect(make_link('random/'.url_escape($search)));
                }
                return;
            }

            $search_terms = [];
            if ($event->page_matches("random/{search}")) {
                $search_terms = SearchTerm::explode($event->get_arg('search'));
            }

            $images_per_page = Ctx::$config->get(RandomListConfig::LIST_COUNT);
            $random_images = [];
            for ($i = 0; $i < $images_per_page; $i++) {
                $random_image = Image::by_random($search_terms);
                if (!$random_image) {
                    continue;
                }
                $random_images[] = $random_image;
            }

            $this->theme->display_page($search_terms, $random_images);
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "posts") {
            $event->add_nav_link(make_link('random'), "Shuffle", "random_list");
        }
    }
}
