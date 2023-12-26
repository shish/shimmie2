<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{emptyHTML, A};

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
    /** @var Rule34Theme */
    protected Themelet $theme;

    public function onImageDeletion(ImageDeletionEvent $event)
    {
        global $database;
        $database->notify("shm_image_bans", $event->image->hash);
    }

    public function onImageInfoSet(ImageInfoSetEvent $event)
    {
        global $cache;
        $cache->delete("thumb-block:{$event->image->id}");
    }

    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event)
    {
        global $config;
        $image_link = $config->get_string(ImageConfig::ILINK);
        $url0 = $event->image->parse_link_template($image_link, 0);
        $url1 = $event->image->parse_link_template($image_link, 1);
        $event->add_part(
            SHM_POST_INFO(
                "Links",
                emptyHTML(
                    A(["href" => $url0], "File Only"),
                    " (",
                    A(["href" => $url1], "Backup Server"),
                    ")"
                )
            ),
            90
        );
    }

    public function onAdminBuilding(AdminBuildingEvent $event)
    {
        global $page;
        $html = make_form(make_link("admin/cache_purge"), "POST");
        $html .= "<textarea type='text' name='hash' placeholder='Enter image URL or hash' cols='80' rows='5'></textarea>";
        $html .= "<br><input type='submit' value='Purge from caches'>";
        $html .= "</form>\n";
        $page->add_block(new Block("Cache Purger", $html));
    }

    public function onUserPageBuilding(UserPageBuildingEvent $event)
    {
        global $database, $user, $config;
        if ($user->can(Permissions::CHANGE_SETTING) && $config->get_bool('r34_comic_integration')) {
            $current_state = bool_escape($database->get_one("SELECT comic_admin FROM users WHERE id=:id", ['id' => $event->display_user->id]));
            $this->theme->show_comic_changer($event->display_user, $current_state);
        }
    }

    public function onThumbnailGeneration(ThumbnailGenerationEvent $event)
    {
        # global $database, $user;
        # if ($user->can(Permissions::MANAGE_ADMINTOOLS)) {
        #     $database->notify("shm_image_bans", $event->hash);
        # }
    }

    public function onCommand(CommandEvent $event)
    {
        global $cache;
        if ($event->cmd == "wipe-thumb-cache") {
            foreach (Search::find_images_iterable(0, null, Tag::explode($event->args[0])) as $image) {
                print($image->id . "\n");
                $cache->delete("thumb-block:{$image->id}");
            }
        }
    }

    public function onSourceSet(SourceSetEvent $event)
    {
        // Maybe check for 404?
        if (empty($event->source)) {
            return;
        }
        if (!preg_match("/^(https?:\/\/)?[a-zA-Z0-9\.\-]+(\/.*)?$/", $event->source)) {
            throw new SCoreException("Invalid source URL");
        }
    }

    public function onRobotsBuilding(RobotsBuildingEvent $event)
    {
        // robots should only check the canonical site, not mirrors
        if ($_SERVER['HTTP_HOST'] != "rule34.paheal.net") {
            $event->add_disallow("");
        }
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $database, $page, $user;

        # Database might not be connected at this point...
        #$database->set_timeout(null); // deleting users can take a while

        $page->add_html_header("<meta name='theme-color' content='#7EB977'>");
        $page->add_html_header("<meta name='juicyads-site-verification' content='20d309e193510e130c3f8a632f281335'>");

        if (function_exists("sd_notify_watchdog")) {
            \sd_notify_watchdog();
        }

        if ($event->page_matches("rule34/comic_admin")) {
            if ($user->can(Permissions::CHANGE_SETTING) && $user->check_auth_token()) {
                $input = validate_input([
                    'user_id' => 'user_id,exists',
                    'is_admin' => 'bool',
                ]);
                $database->execute(
                    'UPDATE users SET comic_admin=:is_admin WHERE id=:id',
                    ['is_admin' => $input['is_admin'] ? 't' : 'f', 'id' => $input['user_id']]
                );
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(referer_or(make_link()));
            }
        }

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
