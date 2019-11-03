<?php

if ( // kill these glitched requests immediately
    strpos(@$_SERVER["REQUEST_URI"], "/http") !== false
    && strpos(@$_SERVER["REQUEST_URI"], "paheal.net") !== false
) {
    die("No");
}

class Rule34 extends Extension
{
    public function onImageDeletion(ImageDeletionEvent $event)
    {
        global $database;
        $database->execute("NOTIFY shm_image_bans, '{$event->image->hash}';");
    }

    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event)
    {
        global $config;
        $image_link = $config->get_string(ImageConfig::ILINK);
        $url0 = $event->image->parse_link_template($image_link, "url_escape", 0);
        $url1 = $event->image->parse_link_template($image_link, "url_escape", 1);
        $html = "<tr><th>Links</th><td><a href='$url0'>Image Only</a> (<a href='$url1'>Backup Server</a>)</td></tr>";
        $event->add_part($html, 90);
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
            $current_state = bool_escape($database->get_one("SELECT comic_admin FROM users WHERE id=?", [$event->display_user->id]));
            $this->theme->show_comic_changer($event->display_user, $current_state);
        }
    }

    public function onThumbnailGeneration(ThumbnailGenerationEvent $event)
    {
        global $database, $user;
        if ($user->can(Permissions::MANAGE_ADMINTOOLS)) {
            $database->execute("NOTIFY shm_image_bans, '{$event->hash}';");
        }
    }

    public function onCommand(CommandEvent $event)
    {
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

    public function onPageRequest(PageRequestEvent $event)
    {
        global $database, $page, $user;

        $database->set_timeout(DATABASE_TIMEOUT+15000); // deleting users can take a while

        if (function_exists("sd_notify_watchdog")) {
            sd_notify_watchdog();
        }

        if ($event->page_matches("rule34/comic_admin")) {
            if ($user->can(Permissions::CHANGE_SETTING) && $user->check_auth_token()) {
                $input = validate_input([
                    'user_id' => 'user_id,exists',
                    'is_admin' => 'bool',
                ]);
                $database->execute(
                    'UPDATE users SET comic_admin=? WHERE id=?',
                    [$input['is_admin'] ? 't' : 'f', $input['user_id']]
                );
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(@$_SERVER['HTTP_REFERER']);
            }
        }

        if ($event->page_matches("tnc_agreed")) {
            setcookie("ui-tnc-agreed", "true", 0, "/");
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(@$_SERVER['HTTP_REFERER'] ?? "/");
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
                            flash_message("Cleaning {$hash}");
                            if (strlen($hash) != 32) {
                                continue;
                            }
                            log_info("admin", "Cleaning {$hash}");
                            @unlink(warehouse_path(Image::IMAGE_DIR, $hash));
                            @unlink(warehouse_path(Image::THUMBNAIL_DIR, $hash));
                            $database->execute("NOTIFY shm_image_bans, '{$hash}';");
                        }
                    }
                }

                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link("admin"));
            }
        }

        if ($event->page_matches("sys_ip_ban")) {
            global $page, $user;
            if ($user->can(Permissions::BAN_IP)) {
                if ($event->get_arg(0) == "list") {
                    $bans = (isset($_GET["all"])) ? $this->get_bans() : $this->get_active_bans();
                    $this->theme->display_bans($page, $bans);
                }
            } else {
                $this->theme->display_permission_denied();
            }
        }
    }

    private function get_bans()
    {
        global $database;
        $bans = $database->get_all("
			SELECT sys_ip_bans.*, users.name as banner_name
			FROM sys_ip_bans
			JOIN users ON banner_id = users.id
			ORDER BY time_start, time_end, sys_ip_bans.id
		");
        if ($bans) {
            return $bans;
        } else {
            return [];
        }
    }

    private function get_active_bans()
    {
        global $database;

        $bans = $database->get_all("
			SELECT sys_ip_bans.*, users.name as banner_name
			FROM sys_ip_bans
			JOIN users ON banner_id = users.id
			WHERE (time_end > now()) OR (time_end IS NULL)
			ORDER BY time_end, sys_ip_bans.id
		");

        if ($bans) {
            return $bans;
        } else {
            return [];
        }
    }
}
