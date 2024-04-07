<?php

declare(strict_types=1);

namespace Shimmie2;

class Featured extends Extension
{
    /** @var FeaturedTheme */
    protected Themelet $theme;

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_int('featured_id', 0);
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page, $user;
        if ($event->page_matches("featured_image/set/{image_id}", method: "POST", permission: Permissions::EDIT_FEATURE)) {
            $id = $event->get_iarg('image_id');
            $config->set_int("featured_id", $id);
            log_info("featured", "Featured post set to >>$id", "Featured post set");
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/$id"));
        }
        if ($event->page_matches("featured_image/download")) {
            $image = Image::by_id($config->get_int("featured_id"));
            if (!is_null($image)) {
                $page->set_mode(PageMode::DATA);
                $page->set_mime($image->get_mime());
                $page->set_data(\Safe\file_get_contents($image->get_image_filename()));
            }
        }
        if ($event->page_matches("featured_image/view")) {
            $image = Image::by_id($config->get_int("featured_id"));
            if (!is_null($image)) {
                send_event(new DisplayingImageEvent($image));
            }
        }
    }

    public function onPostListBuilding(PostListBuildingEvent $event): void
    {
        global $cache, $config, $page, $user;
        $fid = $config->get_int("featured_id");
        if ($fid > 0) {
            $image = cache_get_or_set(
                "featured_image_object:$fid",
                function () use ($fid) {
                    $image = Image::by_id($fid);
                    if ($image) { // make sure the object is fully populated before saving
                        $image->get_tag_array();
                    }
                    return $image;
                },
                600
            );
            if (!is_null($image)) {
                if (Extension::is_enabled(RatingsInfo::KEY)) {
                    if (!in_array($image['rating'], Ratings::get_user_class_privs($user))) {
                        return;
                    }
                }
                $this->theme->display_featured($page, $image);
            }
        }
    }

    public function onImageDeletion(ImageDeletionEvent $event): void
    {
        global $config;
        if ($event->image->id == $config->get_int("featured_id")) {
            $config->set_int("featured_id", 0);
            $config->save();
        }
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::EDIT_FEATURE) && $event->context == "view") {
            $event->add_button("Feature This", "featured_image/set/{$event->image->id}");
        }
    }
}
