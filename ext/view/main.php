<?php declare(strict_types=1);

require_once "events/displaying_image_event.php";
require_once "events/image_info_box_building_event.php";
require_once "events/image_info_set_event.php";
require_once "events/image_admin_block_building_event.php";

use function MicroHTML\TR;
use function MicroHTML\TH;
use function MicroHTML\TD;

class ViewImage extends Extension
{
    /** @var ViewImageTheme */
    protected ?Themelet $theme;

    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $user;

        if ($event->page_matches("post/prev") ||	$event->page_matches("post/next")) {
            $image_id = int_escape($event->get_arg(0));

            if (isset($_GET['search'])) {
                $search_terms = Tag::explode(Tag::decaret($_GET['search']));
                $query = "#search=".url_escape($_GET['search']);
            } else {
                $search_terms = [];
                $query = null;
            }

            $image = Image::by_id($image_id);
            if (is_null($image)) {
                $this->theme->display_error(404, "Post not found", "Post $image_id could not be found");
                return;
            }

            if ($event->page_matches("post/next")) {
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
        } elseif ($event->page_matches("post/view")) {
            if (!is_numeric($event->get_arg(0))) {
                // For some reason there exists some very broken mobile client
                // who follows up every request to '/post/view/123' with
                // '/post/view/12300000000000Image 123: tags' which spams the
                // database log with 'integer out of range'
                $this->theme->display_error(404, "Post not found", "Invalid post ID");
                return;
            }

            $image_id = int_escape($event->get_arg(0));

            $image = Image::by_id($image_id);

            if (!is_null($image)) {
                send_event(new DisplayingImageEvent($image));
            } else {
                $this->theme->display_error(404, "Post not found", "No post in the database has the ID #$image_id");
            }
        } elseif ($event->page_matches("post/set")) {
            if (!isset($_POST['image_id'])) {
                return;
            }

            $image_id = int_escape($_POST['image_id']);
            $image = Image::by_id($image_id);
            if (!$image->is_locked() || $user->can(Permissions::EDIT_IMAGE_LOCK)) {
                send_event(new ImageInfoSetEvent($image));
                $page->set_mode(PageMode::REDIRECT);

                if (isset($_GET['search'])) {
                    $query = "search=" . url_escape($_GET['search']);
                } else {
                    $query = null;
                }
                $page->set_redirect(make_link("post/view/$image_id", null, $query));
            } else {
                $this->theme->display_error(403, "Post Locked", "An admin has locked this post");
            }
        }
    }

    public function onDisplayingImage(DisplayingImageEvent $event)
    {
        global $page, $user;
        $image = $event->get_image();

        $this->theme->display_meta_headers($image);

        $iibbe = new ImageInfoBoxBuildingEvent($image, $user);
        send_event($iibbe);
        ksort($iibbe->parts);
        $this->theme->display_page($image, $iibbe->parts);

        $iabbe = new ImageAdminBlockBuildingEvent($image, $user);
        send_event($iabbe);
        ksort($iabbe->parts);
        $this->theme->display_admin_block($page, $iabbe->parts);
    }

    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event)
    {
        global $config;
        $image_info = $config->get_string(ImageConfig::INFO);
        if ($image_info) {
            $html = (string)TR(
                TH("Info"),
                TD($event->image->get_info())
            );
            $event->add_part($html, 85);
        }
    }
}
