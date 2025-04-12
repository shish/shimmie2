<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;

final class RegenThumb extends Extension
{
    public const KEY = "regen_thumb";
    /** @var RegenThumbTheme */
    protected Themelet $theme;

    public function regenerate_thumbnail(Image $image, bool $force = true): bool
    {
        $event = send_event(new ThumbnailGenerationEvent($image, $force));
        Ctx::$cache->delete("thumb-block:{$image->id}");
        return $event->generated;
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("regen_thumb/one/{image_id}", method: "POST", permission: ImagePermission::DELETE_IMAGE)) {
            $image = Image::by_id_ex($event->get_iarg('image_id'));

            $this->regenerate_thumbnail($image);

            $this->theme->display_results($image);
        }
        if ($event->page_matches("regen_thumb/mass", method: "POST", permission: ImagePermission::DELETE_IMAGE)) {
            $tags = Tag::explode(strtolower($event->POST->req('tags')), false);
            $images = Search::find_images(limit: 10000, tags: $tags);

            foreach ($images as $image) {
                $this->regenerate_thumbnail($image);
            }

            Ctx::$page->set_redirect(make_link());
        }
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        if (Ctx::$user->can(ImagePermission::DELETE_IMAGE)) {
            $event->add_button("Regenerate Thumbnail", "regen_thumb/one/{$event->image->id}");
        }
    }

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event): void
    {
        $event->add_action("regen-thumb", "Regen Thumbnails", block: $this->theme->bulk_html(), permission: ImagePermission::DELETE_IMAGE);
    }

    public function onBulkAction(BulkActionEvent $event): void
    {
        switch ($event->action) {
            case "regen-thumb":
                if (Ctx::$user->can(ImagePermission::DELETE_IMAGE)) {
                    $force = true;
                    if (isset($event->params["bulk_regen_thumb_missing_only"])
                        && $event->params["bulk_regen_thumb_missing_only"] === "true") {
                        $force = false;
                    }

                    $total = 0;
                    foreach ($event->items as $image) {
                        if ($this->regenerate_thumbnail($image, $force)) {
                            $total++;
                        }
                    }
                    $event->log_action("Regenerated thumbnails for $total items");
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
        switch ($event->action) {
            case "regen_thumbs":
                $event->redirect = true;
                $force = false;
                if (isset($event->params["regen_thumb_force"]) && $event->params["regen_thumb_force"] === "true") {
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
                        $path = Filesystem::warehouse_path(Image::THUMBNAIL_DIR, $image->hash, false);
                        if ($path->exists()) {
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
                Ctx::$page->flash("Re-generated $i thumbnails");
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
