<?php

declare(strict_types=1);

namespace Shimmie2;

final class RandomList extends Extension
{
    public const KEY = "random_list";
    /** @var RandomListTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page;

        if ($event->page_matches("random")) {
            if ($event->get_GET('search')) {
                // implode(explode()) to resolve aliases and sanitise
                $search = Tag::implode(Tag::explode($event->get_GET('search'), false));
                if (empty($search)) {
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(make_link("random"));
                } else {
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(make_link('random/'.url_escape($search)));
                }
                return;
            }

            $search_terms = [];
            if ($event->page_matches("random/{search}")) {
                $search_terms = explode(' ', $event->get_arg('search'));
            }

            $images_per_page = $config->get_int(RandomListConfig::LIST_COUNT, 12);
            $random_images = [];
            for ($i = 0; $i < $images_per_page; $i++) {
                $random_image = Image::by_random($search_terms);
                if (!$random_image) {
                    continue;
                }
                $random_images[] = $random_image;
            }

            $this->theme->display_page($page, $search_terms, $random_images);
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent == "posts") {
            $event->add_nav_link(make_link('random'), "Shuffle");
        }
    }
}
