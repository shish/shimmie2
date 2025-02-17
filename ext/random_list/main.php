<?php

declare(strict_types=1);

namespace Shimmie2;

class RandomList extends Extension
{
    /** @var RandomListTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page;

        if ($event->page_matches("random")) {
            if ($event->get_GET('search')) {
                // implode(explode()) to resolve aliases and sanitise
                $search = url_escape(Tag::implode(Tag::explode($event->get_GET('search'), false)));
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

            $this->theme->set_page($search_terms);
            $this->theme->display_page($page, $random_images);
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent == "posts") {
            $event->add_nav_link("posts_random", new Link('random'), "Shuffle");
        }
    }
}
