<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;

use Symfony\Component\Console\Input\{InputInterface,InputArgument};
use Symfony\Component\Console\Output\OutputInterface;

if (
    // kill these glitched requests immediately
    !empty($_SERVER["REQUEST_URI"])
    && str_contains(@$_SERVER["REQUEST_URI"], "/http")
    && str_contains(@$_SERVER["REQUEST_URI"], "paheal.net")
) {
    die("No");
}

class Rule34 extends Extension
{
    public function onImageDeletion(ImageDeletionEvent $event): void
    {
        global $database;
        $database->notify("shm_image_bans", $event->image->hash);
    }

    public function onImageInfoSet(ImageInfoSetEvent $event): void
    {
        global $cache;
        $cache->delete("thumb-block:{$event->image->id}");
    }

    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        global $page;
        $html = make_form(make_link("admin/cache_purge"), "POST");
        $html .= "<textarea type='text' name='hash' placeholder='Enter image URL or hash' cols='80' rows='5'></textarea>";
        $html .= "<br><input type='submit' value='Purge from caches'>";
        $html .= "</form>\n";
        $page->add_block(new Block("Cache Purger", $html));
    }

    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('wipe-thumb-cache')
            ->addArgument('tags', InputArgument::REQUIRED)
            ->setDescription('Delete cached thumbnails for images matching the given tags')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                global $cache;
                $tags = Tag::explode($input->getArgument('tags'));
                foreach (Search::find_images_iterable(0, null, $tags) as $image) {
                    $output->writeln($image->id);
                    $cache->delete("thumb-block:{$image->id}");
                }
                return Command::SUCCESS;
            });
    }

    public function onSourceSet(SourceSetEvent $event): void
    {
        // Maybe check for 404?
        if (empty($event->source)) {
            return;
        }
        if (!preg_match("/^(https?:\/\/)?[a-zA-Z0-9\.\-]+(\/.*)?$/", $event->source)) {
            throw new SCoreException("Invalid source URL");
        }
    }

    public function onRobotsBuilding(RobotsBuildingEvent $event): void
    {
        // robots should only check the canonical site, not mirrors
        if ($_SERVER['HTTP_HOST'] != "rule34.paheal.net") {
            $event->add_disallow("");
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $database, $page, $user;

        # Database might not be connected at this point...
        #$database->set_timeout(null); // deleting users can take a while

        $page->add_html_header("<meta name='theme-color' content='#7EB977'>");
        $page->add_html_header("<meta name='juicyads-site-verification' content='20d309e193510e130c3f8a632f281335'>");

        if ($event->page_matches("tnc_agreed")) {
            setcookie("ui-tnc-agreed", "true", 0, "/");
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(referer_or("/"));
        }

        if ($event->page_matches("admin/cache_purge")) {
            if (!$user->can(Permissions::MANAGE_ADMINTOOLS)) {
                $this->theme->display_permission_denied();
            } else {
                if ($user->check_auth_token()) {
                    $all = $_POST["hash"];
                    $matches = [];
                    if (preg_match_all("/([a-fA-F0-9]{32})/", $all, $matches)) {
                        $matches = $matches[0];
                        foreach ($matches as $hash) {
                            $page->flash("Cleaning {$hash}");
                            if (strlen($hash) != 32) {
                                continue;
                            }
                            log_info("admin", "Cleaning {$hash}");
                            @unlink(warehouse_path(Image::IMAGE_DIR, $hash));
                            @unlink(warehouse_path(Image::THUMBNAIL_DIR, $hash));
                            $database->notify("shm_image_bans", $hash);
                        }
                    }
                }

                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link("admin"));
            }
        }
    }
}
