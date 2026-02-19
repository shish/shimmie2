<?php

declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<ReplaceFileTheme> */
final class ReplaceFile extends Extension
{
    public const KEY = "replace_file";

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("replace/{image_id}", method: "GET", permission: ReplaceFilePermission::REPLACE_IMAGE)) {
            $image_id = $event->get_iarg('image_id');
            $image = Post::by_id_ex($image_id);
            $this->theme->display_replace_page($image_id);
        }

        if ($event->page_matches("replace/{image_id}", method: "POST", permission: ReplaceFilePermission::REPLACE_IMAGE)) {
            $image_id = $event->get_iarg('image_id');
            $image = Post::by_id_ex($image_id);

            if (!empty($event->POST->get("url"))) {
                $tmp_filename = shm_tempnam("transload");
                $url = $event->POST->req("url");
                assert(!empty($url));
                Network::fetch_url($url, $tmp_filename);
            } elseif (count($_FILES) > 0) {
                $tmp_filename = new Path($_FILES["data"]['tmp_name']);
            } else {
                Ctx::$page->set_redirect(make_link("replace/$image_id"));
                return;
            }
            if ($tmp_filename->filesize() > Ctx::$config->get(UploadConfig::SIZE)) {
                $size = to_shorthand_int($tmp_filename->filesize());
                $limit = to_shorthand_int(Ctx::$config->get(UploadConfig::SIZE));
                throw new UploadException("File too large ($size > $limit)");
            }
            send_event(new MediaReplaceEvent($image, $tmp_filename));
            if ($event->POST->get("source")) {
                send_event(new SourceSetEvent($image, $event->POST->req("source")));
            }
            Ctx::$cache->delete("thumb-block:{$image_id}");
            Ctx::$page->set_redirect(make_link("post/view/$image_id"));
        }
    }

    #[EventListener]
    public function onPostAdminBlockBuilding(PostAdminBlockBuildingEvent $event): void
    {
        /* In the future, could perhaps allow users to replace images that they own as well... */
        if (Ctx::$user->can(ReplaceFilePermission::REPLACE_IMAGE)) {
            $event->add_button("Replace", "replace/{$event->image->id}");
        }
    }

    #[EventListener]
    public function onMediaReplace(MediaReplaceEvent $event): void
    {
        $image = $event->image;

        $duplicate = Post::by_hash($event->new_hash);
        if (!is_null($duplicate) && $duplicate->id !== $image->id) {
            throw new MediaReplaceException("A different post >>{$duplicate->id} already has hash {$duplicate->hash}");
        }

        $image->delete_media(); // Actually delete the old image file from disk

        $target = Filesystem::warehouse_path(Post::MEDIA_DIR, $event->new_hash);
        try {
            $event->tmp_filename->copy($target);
        } catch (\Exception $e) {
            throw new MediaReplaceException("Failed to copy file from uploads ({$event->tmp_filename->str()}) to archive ({$target->str()}): {$e->getMessage()}");
        }
        $event->tmp_filename->unlink();

        // update metadata and save metadata to DB
        $event->image->hash = $event->new_hash;
        $filesize = $target->filesize();
        if ($filesize === 0) {
            throw new MediaReplaceException("Replacement file size is zero");
        }
        $event->image->filesize = $filesize;
        $event->image->set_mime(MimeType::get_for_file($target));
        send_event(new MediaCheckPropertiesEvent($image));
        $image->save_to_db();

        send_event(new ThumbnailGenerationEvent($image));

        Log::info("image", "Replaced >>{$image->id} {$event->old_hash} with {$event->new_hash}");
    }
}
