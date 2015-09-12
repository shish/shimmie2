<?php
/**
 * Name: [Beta] Update
 * Author: DakuTree <dakutree@codeanimu.net>
 * Link: http://www.codeanimu.net
 * License: GPLv2
 * Description: Shimmie updater! (Requires admin panel extension & transload engine (cURL/fopen/Wget))
 */
class Update extends Extension {
	public function onInitExt(InitExtEvent $event) {
		global $config;
		$config->set_default_string("update_guserrepo", "shish/shimmie2");
		$config->set_default_string("commit_hash", "unknown");
		$config->set_default_string("update_time", "01/01/1970");
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Update");
		$sb->add_text_option("update_guserrepo", "User/Repo: ");
		$event->panel->add_block($sb);
	}

	public function onAdminBuilding(AdminBuildingEvent $event) {
		global $config;
		if($config->get_string('transload_engine') !== "none"){
			$this->theme->display_admin_block();
		}
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $user, $page;
		if($user->is_admin() && isset($_GET['sha'])){
			if($event->page_matches("update/download")){
				$ok = $this->download_shimmie();

				$page->set_mode("redirect");
				if($ok)	$page->set_redirect(make_link("update/update", "sha=".$_GET['sha']));
				else    $page->set_redirect(make_link("admin")); //TODO: Show error?
			}elseif($event->page_matches("update/update")){
				$ok = $this->update_shimmie();

				$page->set_mode("redirect");
				if($ok)	$page->set_redirect(make_link("admin")); //TODO: Show success?
				else    $page->set_redirect(make_link("admin")); //TODO: Show error?
			}
		}
	}

	/**
	 * @return bool
	 */
	private function download_shimmie() {
		global $config;

		$commitSHA = $_GET['sha'];
		$g_userrepo = $config->get_string('update_guserrepo');

		$url = "https://codeload.github.com/".$g_userrepo."/zip/".$commitSHA;
		$filename = "./data/update_{$commitSHA}.zip";

		log_info("update", "Attempting to download Shimmie commit:  ".$commitSHA);
		if($headers = transload($url, $filename)){
			if(($headers['Content-Type'] !== "application/zip") || ((int) $headers['Content-Length'] !== filesize($filename))){
				unlink("./data/update_{$commitSHA}.zip");
				log_warning("update", "Download failed: not zip / not same size as remote file.");
				return false;
			}

			return true;
		}

		log_warning("update", "Download failed to download.");
		return false;
	}

	/**
	 * @return bool
	 */
	private function update_shimmie() {
		global $config;

		$commitSHA = $_GET['sha'];

		log_info("update", "Download succeeded. Attempting to update Shimmie.");
		$config->set_bool("in_upgrade", TRUE);
		$ok = FALSE;

		/** TODO: Backup all folders (except /data, /images, /thumbs) before attempting this?
		          Either that or point to https://github.com/shish/shimmie2/blob/master/README.txt -> Upgrade from 2.3.X **/

		$zip = new ZipArchive;
		if ($zip->open("./data/update_$commitSHA.zip") === TRUE) {
			for($i = 1; $i < $zip->numFiles; $i++) {
				$filename = $zip->getNameIndex($i);

				if(substr($filename, -1) !== "/"){
					copy("zip://".dirname(dirname(__DIR__)).'/'."./data/update_$commitSHA.zip"."#".$filename, substr($filename, 50));
				}
			}
			$ok = TRUE; //TODO: Do proper checking to see if everything copied properly
		}else{ log_warning("update", "Update failed to open ZIP."); }

		$zip->close();
		unlink("./data/update_$commitSHA.zip");
		$config->set_bool("in_upgrade", FALSE);

		if($ok){
			$config->set_string("commit_hash", $commitSHA);
			$config->set_string("update_time", date('d-m-Y'));
			log_info("update", "Update succeeded?");
		}

		return $ok;
	}
}


