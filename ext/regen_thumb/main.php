<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface,InputArgument};
use Symfony\Component\Console\Output\OutputInterface;

class RegenThumb extends Extension
{
    /** @var RegenThumbTheme */
    protected Themelet $theme;

    public function regenerate_thumbnail(Image $image, bool $force = true): bool
    {
        global $cache;
        $event = send_event(new ThumbnailGenerationEvent($image, $force));
        $cache->delete("thumb-block:{$image->id}");
        return $event->generated;
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user;

        if ($event->page_matches("regen_thumb/one/{image_id}", method: "POST", permission: Permissions::DELETE_IMAGE)) {
            $image = Image::by_id_ex($event->get_iarg('image_id'));

            $this->regenerate_thumbnail($image);

            $this->theme->display_results($page, $image);
        }
        if ($event->page_matches("regen_thumb/mass", method: "POST", permission: Permissions::DELETE_IMAGE)) {
            $tags = Tag::explode(strtolower($event->req_POST('tags')), false);
            $images = Search::find_images(limit: 10000, tags: $tags);

            foreach ($images as $image) {
                $this->regenerate_thumbnail($image);
            }

            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link());
        }
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::DELETE_IMAGE)) {
            $event->add_button("Regenerate Thumbnail", "regen_thumb/one/{$event->image->id}");
        }
    }

    // public function onPostListBuilding(PostListBuildingEvent $event): void
    // {
    //     global $user;
    //     if ($user->can(UserAbilities::DELETE_IMAGE) && !empty($event->search_terms)) {
    //         $event->add_control($this->theme->mtr_html(Tag::implode($event->search_terms)));
    //     }
    // }

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event): void
    {
        global $user;

        if ($user->can(Permissions::DELETE_IMAGE)) {
            $event->add_action("bulk_regen", "Regen Thumbnails", "", "", $this->theme->bulk_html());
        }
    }

    public function onBulkAction(BulkActionEvent $event): void
    {
        global $page, $user;

        switch ($event->action) {
            case "bulk_regen":
                if ($user->can(Permissions::DELETE_IMAGE)) {
                    $force = true;
                    if (isset($event->params["bulk_regen_thumb_missing_only"])
                        && $event->params["bulk_regen_thumb_missing_only"] == "true") {
                        $force = false;
                    }

                    $total = 0;
                    foreach ($event->items as $image) {
                        if ($this->regenerate_thumbnail($image, $force)) {
                            $total++;
                        }
                    }
                    $page->flash("Regenerated thumbnails for $total items");
                }
                break;
        }
    }

    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $this->theme->display_admin_block();
    }

    public function onAdminAction(AdminActionEvent $event): void
    {
        global $page;
        switch ($event->action) {
            case "regen_thumbs":
                $event->redirect = true;
                $force = false;
                if (isset($event->params["regen_thumb_force"]) && $event->params["regen_thumb_force"] == "true") {
                    $force = true;
                }
                $limit = 1000;
                if (isset($event->params["regen_thumb_limit"]) && is_numeric($event->params["regen_thumb_limit"])) {
                    $limit = intval($event->params["regen_thumb_limit"]);
                }

                $mime = "";
                if (isset($event->params["regen_thumb_mime"])) {
                    $mime = $event->params["regen_thumb_mime"];
                }
                $images = Search::find_images(tags: ["mime=" . $mime]);

                $i = 0;
                foreach ($images as $image) {
                    if (!$force) {
                        $path = warehouse_path(Image::THUMBNAIL_DIR, $image->hash, false);
                        if (file_exists($path)) {
                            continue;
                        }
                    }
                    $event = send_event(new ThumbnailGenerationEvent($image, $force));
                    if ($event->generated) {
                        $i++;
                    }
                    if ($i >= $limit) {
                        break;
                    }
                }
                $page->flash("Re-generated $i thumbnails");
                break;
            case "delete_thumbs":
                $event->redirect = true;

                if (isset($event->params["delete_thumb_mime"]) && $event->params["delete_thumb_mime"] != "") {
                    $images = Search::find_images(tags: ["mime=" . $event->params["delete_thumb_mime"]]);

                    $i = 0;
                    foreach ($images as $image) {
                        $outname = $image->get_thumb_filename();
                        if (file_exists($outname)) {
                            unlink($outname);
                            $i++;
                        }
                    }
                    $page->flash("Deleted $i thumbnails for ".$event->params["delete_thumb_mime"]." images");
                } else {
                    $dir = "data/thumbs/";
                    deltree($dir);
                    $page->flash("Deleted all thumbnails");
                }


                break;
        }
    }

    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('regen-thumb')
            ->addArgument('id_or_hash', InputArgument::REQUIRED)
            ->setDescription("Regenerate a post's thumbnail")
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $uid = $input->getArgument('id_or_hash');
                $image = Image::by_id_or_hash($uid);
                if ($image) {
                    send_event(new ThumbnailGenerationEvent($image, true));
                } else {
                    $output->writeln("No post with ID '$uid'\n");
                }
                return Command::SUCCESS;
            });
    }
}
