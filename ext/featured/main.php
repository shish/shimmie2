<?php

declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<FeaturedTheme> */
final class Featured extends Extension
{
    public const KEY = "featured";

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("featured_image/set/{image_id}", method: "POST", permission: FeaturedPermission::EDIT_FEATURE)) {
            $id = $event->get_iarg('image_id');
            Ctx::$config->set(FeaturedConfig::ID, $id);
            Log::info("featured", "Featured post set to >>$id", "Featured post set");
            Ctx::$page->set_redirect(make_link("post/view/$id"));
        }
        if ($event->page_matches("featured_image/download")) {
            $fid = Ctx::$config->get(FeaturedConfig::ID);
            if (!is_null($fid)) {
                $image = Image::by_id($fid);
                if (!is_null($image)) {
                    Ctx::$page->set_data($image->get_mime(), $image->get_image_filename()->get_contents());
                }
            }
        }
        if ($event->page_matches("featured_image/view")) {
            $fid = Ctx::$config->get(FeaturedConfig::ID);
            if (!is_null($fid)) {
                $image = Image::by_id($fid);
                if (!is_null($image)) {
                    send_event(new DisplayingImageEvent($image));
                }
            }
        }
    }

    #[EventListener]
    public function onPostListBuilding(PostListBuildingEvent $event): void
    {
        $fid = Ctx::$config->get(FeaturedConfig::ID);
        if (!is_null($fid)) {
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
                if (RatingsInfo::is_enabled()) {
                    if (!in_array($image['rating'], Ratings::get_user_class_privs(Ctx::$user))) {
                        return;
                    }
                }
                $this->theme->display_featured($image);
            }
        }
    }

    #[EventListener]
    public function onImageDeletion(ImageDeletionEvent $event): void
    {
        if ($event->image->id === Ctx::$config->get(FeaturedConfig::ID)) {
            Ctx::$config->delete(FeaturedConfig::ID);
        }
    }

    #[EventListener]
    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        if (Ctx::$user->can(FeaturedPermission::EDIT_FEATURE) && $event->context === "view") {
            $event->add_button("Feature This", "featured_image/set/{$event->image->id}");
        }
    }
}
