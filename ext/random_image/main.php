<?php

declare(strict_types=1);

namespace Shimmie2;

class RandomImage extends Extension
{
    /** @var RandomImageTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page;

        if (
            $event->page_matches("random_image/{action}")
            || $event->page_matches("random_image/{action}/{search}")
        ) {
            $action = $event->get_arg('action');
            $search_terms = Tag::explode($event->get_arg('search', ""), false);
            $image = Image::by_random($search_terms);
            if (!$image) {
                throw new ImageNotFound("Couldn't find any posts randomly");
            }

            if ($action === "download") {
                send_event(new ImageDownloadingEvent($image, $image->get_image_filename(), $image->get_mime(), $event->GET));
            } elseif ($action === "view") {
                send_event(new DisplayingImageEvent($image));
            } elseif ($action === "widget") {
                $page->set_mode(PageMode::DATA);
                $page->set_mime(MimeType::HTML);
                $page->set_data($this->theme->build_thumb_html($image));
            }
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Random Post");
        $sb->add_bool_option("show_random_block", "Show Random Block: ");
    }

    public function onPostListBuilding(PostListBuildingEvent $event): void
    {
        global $config, $page;
        if ($config->get_bool("show_random_block")) {
            $image = Image::by_random($event->search_terms);
            if (!is_null($image)) {
                $this->theme->display_random($page, $image);
            }
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent == "posts") {
            $event->add_nav_link("posts_random", new Link('random_image/view'), "Random Post");
        }
    }
}
