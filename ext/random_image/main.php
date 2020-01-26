<?php declare(strict_types=1);

class RandomImage extends Extension
{
    public function onPageRequest(PageRequestEvent $event)
    {
        global $page;

        if ($event->page_matches("random_image")) {
            if ($event->count_args() == 1) {
                $action = $event->get_arg(0);
                $search_terms = [];
            } elseif ($event->count_args() == 2) {
                $action = $event->get_arg(0);
                $search_terms = explode(' ', $event->get_arg(1));
            } else {
                throw new SCoreException("Error: too many arguments.");
            }
            $image = Image::by_random($search_terms);

            if ($action === "download") {
                if (!is_null($image)) {
                    $page->set_mode(PageMode::DATA);
                    $page->set_type($image->get_mime_type());
                    $page->set_data(file_get_contents($image->get_image_filename()));
                }
            } elseif ($action === "view") {
                if (!is_null($image)) {
                    send_event(new DisplayingImageEvent($image));
                }
            } elseif ($action === "widget") {
                if (!is_null($image)) {
                    $page->set_mode(PageMode::DATA);
                    $page->set_type("text/html");
                    $page->set_data($this->theme->build_thumb_html($image));
                }
            }
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = new SetupBlock("Random Image");
        $sb->add_bool_option("show_random_block", "Show Random Block: ");
        $event->panel->add_block($sb);
    }

    public function onPostListBuilding(PostListBuildingEvent $event)
    {
        global $config, $page;
        if ($config->get_bool("show_random_block")) {
            $image = Image::by_random($event->search_terms);
            if (!is_null($image)) {
                $this->theme->display_random($page, $image);
            }
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event)
    {
        if ($event->parent=="posts") {
            $event->add_nav_link("posts_random", new Link('random_image/view'), "Random Image");
        }
    }
}
