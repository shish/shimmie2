<?php

declare(strict_types=1);

namespace Shimmie2;

class ReplaceFile extends Extension
{
    /** @var ReplaceFileTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $cache, $page, $user;

        if ($event->page_matches("replace")) {
            if (!$user->can(Permissions::REPLACE_IMAGE)) {
                $this->theme->display_error(403, "Error", "{$user->name} doesn't have permission to replace images");
                return;
            }

            $image_id = int_escape($event->get_arg(0));
            $image = Image::by_id($image_id);
            if (is_null($image)) {
                throw new UploadException("Can not replace Post: No post with ID $image_id");
            }

            if($event->method == "GET") {
                $this->theme->display_replace_page($page, $image_id);
            } elseif($event->method == "POST") {
                if (!empty($_POST["url"])) {
                    $tmp_filename = tempnam(ini_get('upload_tmp_dir'), "shimmie_transload");
                    fetch_url($_POST["url"], $tmp_filename);
                    send_event(new ImageReplaceEvent($image, $tmp_filename));
                } elseif (count($_FILES) > 0) {
                    send_event(new ImageReplaceEvent($image, $_FILES["data"]['tmp_name']));
                }
                if(!empty($_POST["source"])) {
                    send_event(new SourceSetEvent($image, $_POST["source"]));
                }
                $cache->delete("thumb-block:{$image_id}");
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link("post/view/$image_id"));
            }
        }
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        global $user;

        /* In the future, could perhaps allow users to replace images that they own as well... */
        if ($user->can(Permissions::REPLACE_IMAGE)) {
            $event->add_part($this->theme->get_replace_html($event->image->id));
        }
    }

    public function onImageReplace(ImageReplaceEvent $event): void
    {
        $image = $event->image;

        $duplicate = Image::by_hash($event->new_hash);
        if (!is_null($duplicate) && $duplicate->id != $image->id) {
            throw new ImageReplaceException("A different post >>{$duplicate->id} already has hash {$duplicate->hash}");
        }

        $image->remove_image_only(); // Actually delete the old image file from disk

        $target = warehouse_path(Image::IMAGE_DIR, $event->new_hash);
        if (!@copy($event->tmp_filename, $target)) {
            $errors = error_get_last();
            throw new ImageReplaceException(
                "Failed to copy file from uploads ({$event->tmp_filename}) to archive ($target): ".
                "{$errors['type']} / {$errors['message']}"
            );
        }
        unlink($event->tmp_filename);

        // update metadata and save metadata to DB
        $event->image->hash = $event->new_hash;
        $event->image->filesize = filesize($target);
        $event->image->set_mime(MimeType::get_for_file($target));
        send_event(new MediaCheckPropertiesEvent($image));
        $image->save_to_db();

        send_event(new ThumbnailGenerationEvent($image));

        log_info("image", "Replaced >>{$image->id} {$event->old_hash} with {$event->new_hash}");
    }
}
