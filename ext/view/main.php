<?php

declare(strict_types=1);

namespace Shimmie2;

require_once "events/displaying_image_event.php";
require_once "events/image_info_box_building_event.php";
require_once "events/image_info_set_event.php";
require_once "events/image_admin_block_building_event.php";

use function MicroHTML\TR;
use function MicroHTML\TH;
use function MicroHTML\TD;

class ViewPost extends Extension
{
    /** @var ViewPostTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user;

        if ($event->page_matches("post/prev/{image_id}") || $event->page_matches("post/next/{image_id}")) {
            $image_id = $event->get_iarg('image_id');

            $search = $event->get_GET('search');
            if ($search) {
                $search_terms = Tag::explode($search);
                $query = "#search=".url_escape($search);
            } else {
                $search_terms = [];
                $query = null;
            }

            $image = Image::by_id_ex($image_id);

            if ($event->page_matches("post/next/{image_id}")) {
                $image = $image->get_next($search_terms);
            } else {
                $image = $image->get_prev($search_terms);
            }

            if (is_null($image)) {
                $this->theme->display_error(404, "Post not found", "No more posts");
                return;
            }

            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/{$image->id}", $query));
        } elseif ($event->page_matches("post/view/{image_id}")) {
            if (!is_numeric($event->get_arg('image_id'))) {
                // For some reason there exists some very broken mobile client
                // who follows up every request to '/post/view/123' with
                // '/post/view/12300000000000Image 123: tags' which spams the
                // database log with 'integer out of range'
                $this->theme->display_error(404, "Post not found", "Invalid post ID");
                return;
            }

            $image_id = $event->get_iarg('image_id');
            $image = Image::by_id_ex($image_id);
            send_event(new DisplayingImageEvent($image));
        } elseif ($event->page_matches("post/set", method: "POST")) {
            $image_id = int_escape($event->req_POST('image_id'));
            $image = Image::by_id_ex($image_id);
            if (!$image->is_locked() || $user->can(Permissions::EDIT_IMAGE_LOCK)) {
                send_event(new ImageInfoSetEvent($image, 0, only_strings($event->POST)));
                $page->set_mode(PageMode::REDIRECT);

                if ($event->get_GET('search')) {
                    $query = "search=" . url_escape($event->get_GET('search'));
                } else {
                    $query = null;
                }
                $page->set_redirect(make_link("post/view/$image_id", null, $query));
            } else {
                $this->theme->display_error(403, "Post Locked", "An admin has locked this post");
            }
        }
    }

    public function onRobotsBuilding(RobotsBuildingEvent $event): void
    {
        // next and prev are just CPU-heavier ways of getting
        // to the same images that the index shows
        $event->add_disallow("post/next");
        $event->add_disallow("post/prev");
    }

    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        global $page, $user;
        $image = $event->get_image();

        $this->theme->display_meta_headers($image);

        $iibbe = send_event(new ImageInfoBoxBuildingEvent($image, $user));
        $this->theme->display_page($image, $iibbe->get_parts());

        $iabbe = send_event(new ImageAdminBlockBuildingEvent($image, $user, "view"));
        $this->theme->display_admin_block($page, $iabbe->get_parts());
    }

    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event): void
    {
        global $config;
        $image_info = $config->get_string(ImageConfig::INFO);
        if ($image_info) {
            $event->add_part(SHM_POST_INFO("Info", $event->image->get_info()), 85);
        }
    }
}
