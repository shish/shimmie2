<?php

declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<RandomImageTheme> */
final class RandomImage extends Extension
{
    public const KEY = "random_image";

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if (
            $event->page_matches("random_image/{action}")
            || $event->page_matches("random_image/{action}/{search}")
        ) {
            $action = $event->get_arg('action');
            $search_terms = SearchTerm::explode($event->get_arg('search', ""));
            $image = Post::by_random($search_terms);
            if (!$image) {
                throw new PostNotFound("Couldn't find any posts randomly");
            }

            if ($action === "download") {
                send_event(new MediaDownloadingEvent($image, $image->get_media_filename(), $image->get_mime(), $event->GET));
            } elseif ($action === "view") {
                send_event(new DisplayingPostEvent($image));
            } elseif ($action === "widget") {
                $page = Ctx::$page;
                $page->set_data(MimeType::HTML, (string)$this->theme->build_thumb($image));
            }
        }
    }

    #[EventListener]
    public function onPostListBuilding(PostListBuildingEvent $event): void
    {
        if (Ctx::$config->get(RandomImageConfig::SHOW_RANDOM_BLOCK)) {
            $image = Post::by_random($event->search_terms);
            if (!is_null($image)) {
                $this->theme->display_random($image);
            }
        }
    }

    #[EventListener]
    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "posts") {
            $event->add_nav_link(make_link('random_image/view'), "Random Post");
        }
    }
}
