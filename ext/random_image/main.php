<?php

declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<RandomImageTheme> */
final class RandomImage extends Extension
{
    public const KEY = "random_image";

    public function onPageRequest(PageRequestEvent $event): void
    {
        if (
            $event->page_matches("random_image/{action}")
            || $event->page_matches("random_image/{action}/{search}")
        ) {
            $action = $event->get_arg('action');
            $search_terms = SearchTerm::explode($event->get_arg('search', ""));
            $image = Image::by_random($search_terms);
            if (!$image) {
                throw new PostNotFound("Couldn't find any posts randomly");
            }

            if ($action === "download") {
                send_event(new ImageDownloadingEvent($image, $image->get_image_filename(), $image->get_mime(), $event->GET));
            } elseif ($action === "view") {
                send_event(new DisplayingImageEvent($image));
            } elseif ($action === "widget") {
                $page = Ctx::$page;
                $page->set_data(MimeType::HTML, (string)$this->theme->build_thumb($image));
            }
        }
    }

    public function onPostListBuilding(PostListBuildingEvent $event): void
    {
        if (Ctx::$config->get(RandomImageConfig::SHOW_RANDOM_BLOCK)) {
            $image = Image::by_random($event->search_terms);
            if (!is_null($image)) {
                $this->theme->display_random($image);
            }
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "posts") {
            $event->add_nav_link(make_link('random_image/view'), "Random Post", "random_post");
        }
    }
}
