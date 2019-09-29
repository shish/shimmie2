<?php


class RegenThumb extends Extension
{
    public function regenerate_thumbnail($image, $force = true): string
    {
        global $database;
        $event = new ThumbnailGenerationEvent($image->hash, $image->ext, $force);
        send_event($event);
        $database->cache->delete("thumb-block:{$image->id}");
        return $event->generated;
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $database, $page, $user;

        if ($event->page_matches("regen_thumb/one") && $user->can(Permissions::DELETE_IMAGE) && isset($_POST['image_id'])) {
            $image = Image::by_id(int_escape($_POST['image_id']));

            $this->regenerate_thumbnail($image);

            $this->theme->display_results($page, $image);
        }
        if ($event->page_matches("regen_thumb/mass") && $user->can(Permissions::DELETE_IMAGE) && isset($_POST['tags'])) {
            $tags = Tag::explode(strtolower($_POST['tags']), false);
            $images = Image::find_images(0, 10000, $tags);

            foreach ($images as $image) {
                $this->regenerate_thumbnail($image);
            }

            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/list"));
        }
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event)
    {
        global $user;
        if ($user->can(Permissions::DELETE_IMAGE)) {
            $event->add_part($this->theme->get_buttons_html($event->image->id));
        }
    }

    // public function onPostListBuilding(PostListBuildingEvent $event)
    // {
    //     global $user;
    //     if ($user->can(UserAbilities::DELETE_IMAGE) && !empty($event->search_terms)) {
    //         $event->add_control($this->theme->mtr_html(Tag::implode($event->search_terms)));
    //     }
    // }

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event)
    {
        global $user;

        if ($user->can(Permissions::DELETE_IMAGE)) {
            $event->add_action("bulk_regen", "Regen Thumbnails", "", "", $this->theme->bulk_html());
        }
    }

    public function onBulkAction(BulkActionEvent $event)
    {
        global $user;

        switch ($event->action) {
            case "bulk_regen":
                if ($user->can(Permissions::DELETE_IMAGE)) {
                    $force = true;
                    if (isset($_POST["bulk_regen_thumb_missing_only"])
                        &&$_POST["bulk_regen_thumb_missing_only"]=="true") {
                        $force=false;
                    }

                    $total = 0;
                    foreach ($event->items as $image) {
                        if ($this->regenerate_thumbnail($image, $force)) {
                            $total++;
                        }
                    }
                    flash_message("Regenerated thumbnails for $total items");
                }
                break;
        }
    }

    public function onAdminBuilding(AdminBuildingEvent $event)
    {
        $this->theme->display_admin_block();
    }

    public function onAdminAction(AdminActionEvent $event)
    {
        global $database;

        switch ($event->action) {
            case "regen_thumbs":
            $event->redirect = true;
                $force = false;
                if (isset($_POST["regen_thumb_force"])&&$_POST["regen_thumb_force"]=="true") {
                    $force=true;
                }
                $limit = 1000;
                if (isset($_POST["regen_thumb_limit"])&&is_numeric($_POST["regen_thumb_limit"])) {
                    $limit=intval($_POST["regen_thumb_limit"]);
                }

                $type = "";
                if (isset($_POST["regen_thumb_limit"])) {
                    $type = $_POST["regen_thumb_type"];
                }
                $images = $this->get_images($type);

                $i = 0;
                foreach ($images as $image) {
                    if (!$force) {
                        $path = warehouse_path(Image::THUMBNAIL_DIR, $image["hash"], false);
                        if (file_exists($path)) {
                            continue;
                        }
                    }
                    $event = new ThumbnailGenerationEvent($image["hash"], $image["ext"], $force);
                    send_event($event);
                    if ($event->generated) {
                        $i++;
                    }
                    if ($i>=$limit) {
                        break;
                    }
                }
                flash_message("Re-generated $i thumbnails");
                break;
            case "delete_thumbs":
                $event->redirect = true;

                if (isset($_POST["delete_thumb_type"])&&$_POST["delete_thumb_type"]!="") {
                    $images = $this->get_images($_POST["delete_thumb_type"]);

                    $i = 0;
                    foreach ($images as $image) {
                        $outname = warehouse_path(Image::THUMBNAIL_DIR, $image["hash"]);
                        if (file_exists($outname)) {
                            unlink($outname);
                            $i++;
                        }
                    }
                    flash_message("Deleted $i thumbnails for ".$_POST["delete_thumb_type"]." images");
                } else {
                    $dir = "data/thumbs/";
                    $this->remove_dir_recursively($dir);
                    flash_message("Deleted all thumbnails");
                }


                break;
        }
    }

    public function get_images(String $ext = null)
    {
        global $database;

        $query = "SELECT hash, ext FROM images";
        $args = [];
        if ($ext!=null&&$ext!="") {
            $query .= " WHERE ext = :ext";
            $args["ext"] = $ext;
        }

        return $database->get_all($query, $args);
    }

    public function remove_dir_recursively($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir."/".$object) == "dir") {
                        $this->remove_dir_recursively($dir."/".$object);
                    } else {
                        unlink($dir."/".$object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }
}
