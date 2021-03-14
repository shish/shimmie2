<?php declare(strict_types=1);

class Update extends Extension
{
    /** @var UpdateTheme */
    protected ?Themelet $theme;

    public function onInitExt(InitExtEvent $event)
    {
        global $config;
        $config->set_default_string("update_guserrepo", "shish/shimmie2");
        $config->set_default_string("commit_hash", "unknown");
        $config->set_default_string("update_time", "01/01/1970");
    }

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = $event->panel->create_new_block("Update");
        $sb->add_text_option("update_guserrepo", "User/Repo: ");
    }

    public function onAdminBuilding(AdminBuildingEvent $event)
    {
        global $config;
        if ($config->get_string(UploadConfig::TRANSLOAD_ENGINE) !== "none") {
            $this->theme->display_admin_block();
        }
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $user, $page;
        if ($user->can(Permissions::EDIT_FILES) && isset($_GET['sha'])) {
            if ($event->page_matches("update/download")) {
                $ok = $this->download_shimmie();

                $page->set_mode(PageMode::REDIRECT);
                if ($ok) {
                    $page->set_redirect(make_link("update/update", "sha=".$_GET['sha']));
                } else {
                    $page->set_redirect(make_link("admin"));
                } //TODO: Show error?
            } elseif ($event->page_matches("update/update")) {
                $ok = $this->update_shimmie();

                $page->set_mode(PageMode::REDIRECT);
                if ($ok) {
                    $page->set_redirect(make_link("admin"));
                } //TODO: Show success?
                else {
                    $page->set_redirect(make_link("admin"));
                } //TODO: Show error?
            }
        }
    }

    private function download_shimmie(): bool
    {
        global $config;

        $commitSHA = $_GET['sha'];
        $g_userrepo = $config->get_string('update_guserrepo');

        $url = "https://codeload.github.com/".$g_userrepo."/zip/".$commitSHA;
        $filename = "./data/update_{$commitSHA}.zip";

        log_info("update", "Attempting to download Shimmie commit:  ".$commitSHA);
        if ($headers = fetch_url($url, $filename)) {
            if (($headers['Content-Type'] !== MimeType::ZIP) || ((int) $headers['Content-Length'] !== filesize($filename))) {
                unlink("./data/update_{$commitSHA}.zip");
                log_warning("update", "Download failed: not zip / not same size as remote file.");
                return false;
            }

            return true;
        }

        log_warning("update", "Download failed to download.");
        return false;
    }

    private function update_shimmie(): bool
    {
        global $config;

        $commitSHA = $_GET['sha'];

        log_info("update", "Download succeeded. Attempting to update Shimmie.");
        $ok = false;

        /** TODO: Backup all folders (except /data, /images, /thumbs) before attempting this?
                  Either that or point to https://github.com/shish/shimmie2/blob/master/README.txt -> Upgrade from 2.3.X **/

        $zip = new ZipArchive;
        if ($zip->open("./data/update_$commitSHA.zip") === true) {
            for ($i = 1; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);

                if (substr($filename, -1) !== "/") {
                    copy("zip://".dirname(dirname(__DIR__)).'/'."./data/update_$commitSHA.zip"."#".$filename, substr($filename, 50));
                }
            }
            $ok = true; //TODO: Do proper checking to see if everything copied properly
        } else {
            log_warning("update", "Update failed to open ZIP.");
        }

        $zip->close();
        unlink("./data/update_$commitSHA.zip");

        if ($ok) {
            $config->set_string("commit_hash", $commitSHA);
            $config->set_string("update_time", date('d-m-Y'));
            log_info("update", "Update succeeded?");
        }

        return $ok;
    }
}
