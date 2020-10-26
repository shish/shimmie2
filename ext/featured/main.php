<?php declare(strict_types=1);

class Featured extends Extension
{
    /** @var FeaturedTheme */
    protected $theme;

    public function onInitExt(InitExtEvent $event)
    {
        global $config;
        $config->set_default_int('featured_id', 0);
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $config, $page, $user;
        if ($event->page_matches("featured_image")) {
            if ($event->get_arg(0) == "set" && $user->check_auth_token()) {
                if ($user->can(Permissions::EDIT_FEATURE) && isset($_POST['image_id'])) {
                    $id = int_escape($_POST['image_id']);
                    if ($id > 0) {
                        $config->set_int("featured_id", $id);
                        log_info("featured", "Featured post set to >>$id", "Featured post set");
                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(make_link("post/view/$id"));
                    }
                }
            }
            if ($event->get_arg(0) == "download") {
                $image = Image::by_id($config->get_int("featured_id"));
                if (!is_null($image)) {
                    $page->set_mode(PageMode::DATA);
                    $page->set_mime($image->get_mime());
                    $page->set_data(file_get_contents($image->get_image_filename()));
                }
            }
            if ($event->get_arg(0) == "view") {
                $image = Image::by_id($config->get_int("featured_id"));
                if (!is_null($image)) {
                    send_event(new DisplayingImageEvent($image));
                }
            }
        }
    }

    public function onPostListBuilding(PostListBuildingEvent $event)
    {
        global $cache, $config, $page, $user;
        $fid = $config->get_int("featured_id");
        if ($fid > 0) {
            $image = $cache->get("featured_image_object:$fid");
            if ($image === false) {
                $image = Image::by_id($fid);
                if ($image) { // make sure the object is fully populated before saving
                    $image->get_tag_array();
                }
                $cache->set("featured_image_object:$fid", $image, 600);
            }
            if (!is_null($image)) {
                if (Extension::is_enabled(RatingsInfo::KEY)) {
                    if (!in_array($image->rating, Ratings::get_user_class_privs($user))) {
                        return;
                    }
                }
                $this->theme->display_featured($page, $image);
            }
        }
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event)
    {
        global $user;
        if ($user->can(Permissions::EDIT_FEATURE)) {
            $event->add_part($this->theme->get_buttons_html($event->image->id));
        }
    }
}
