<?php

declare(strict_types=1);

namespace Shimmie2;

require_once "events/displaying_image_event.php";
require_once "events/image_info_box_building_event.php";
require_once "events/image_info_set_event.php";
require_once "events/image_admin_block_building_event.php";

/** @extends Extension<ViewPostTheme> */
final class ViewPost extends Extension
{
    public const KEY = "view";

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("post/prev/{image_id}") || $event->page_matches("post/next/{image_id}")) {
            $image_id = $event->get_iarg('image_id');

            $search = $event->GET->get('search');
            if ($search) {
                $search_terms = SearchTerm::explode($search);
                $fragment = "search=".url_escape($search);
            } else {
                $search_terms = [];
                $fragment = null;
            }

            $image = Image::by_id_ex($image_id);

            if ($event->page_matches("post/next/{image_id}")) {
                $image = $image->get_next($search_terms);
            } else {
                $image = $image->get_prev($search_terms);
            }

            if (is_null($image)) {
                throw new PostNotFound("No more posts");
            }

            Ctx::$page->set_redirect(make_link("post/view/{$image->id}", fragment: $fragment));
        } elseif ($event->page_matches("post/view/{image_id}")) {
            if (!is_numeric($event->get_arg('image_id'))) {
                // For some reason there exists some very broken mobile client
                // who follows up every request to '/post/view/123' with
                // '/post/view/12300000000000Image 123: tags' which spams the
                // database log with 'integer out of range'
                throw new PostNotFound("Invalid post ID");
            }

            $image_id = $event->get_iarg('image_id');
            $image = Image::by_id_ex($image_id);
            send_event(new DisplayingImageEvent($image));
        } elseif ($event->page_matches("post/set", method: "POST")) {
            $image_id = int_escape($event->POST->req('image_id'));
            $image = Image::by_id_ex($image_id);
            if (!$image->is_locked() || Ctx::$user->can(PostLockPermission::EDIT_IMAGE_LOCK)) {
                send_event(new ImageInfoSetEvent($image, 0, $event->POST));

                if ($event->GET->get('search')) {
                    $fragment = "search=" . url_escape($event->GET->get('search'));
                } else {
                    $fragment = null;
                }
                Ctx::$page->set_redirect(make_link("post/view/$image_id", fragment: $fragment));
            } else {
                throw new PermissionDenied("An admin has locked this post");
            }
        }
    }

    #[EventListener]
    public function onRobotsBuilding(RobotsBuildingEvent $event): void
    {
        // next and prev are just CPU-heavier ways of getting
        // to the same images that the index shows
        $event->add_disallow("post/next");
        $event->add_disallow("post/prev");
    }

    #[EventListener]
    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        $this->theme->display_meta_headers($event->image);

        $iibbe = send_event(new ImageInfoBoxBuildingEvent($event->image, Ctx::$user));
        $this->theme->display_page($event->image, $iibbe->get_parts(), $iibbe->get_sidebar_parts());

        $iabbe = send_event(new ImageAdminBlockBuildingEvent($event->image, Ctx::$user, "view"));
        $this->theme->display_admin_block($iabbe->get_parts());
    }

    #[EventListener]
    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event): void
    {
        $image_info = Ctx::$config->get(ImageConfig::INFO);
        if ($image_info) {
            $text = send_event(new ParseLinkTemplateEvent($image_info, $event->image))->text;
            $event->add_part(SHM_POST_INFO("Info", $text), 85);
        }
    }
}
