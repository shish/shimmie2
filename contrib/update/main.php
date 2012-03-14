<?php
/**
 * Name: Update
 * Author: DakuTree <dakutree@codeanimu.net>
 * Link: http://www.codeanimu.net
 * License: GPLv2
 * Description: Shimmie updater! (Requires php-curl library and admin panel extension)
 */
class Update extends Extension {
	public function onInitExt(InitExtEvent $event) {
		global $config;
		$config->set_default_string("update_guser", "shish");
		$config->set_default_string("update_grepo", "shimmie2");
		$config->set_default_string("commit_hash", "unknown");
		$config->set_default_string("commit_time", "unknown");
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Update");
		$sb->add_text_option("update_guser", "User: ");
		$sb->add_text_option("update_grepo", "<br>Repo: ");
		$event->panel->add_block($sb);
	}

	public function onAdminBuilding(AdminBuildingEvent $event) {
		global $config;

		$latestCommit = $this->get_latest_commit();
		if(is_null($latestCommit)) return;

		$commitMessage = $latestCommit["commit"]["message"];
		$commitDT = explode("T", $latestCommit["commit"]["committer"]["date"]);
		$commitTD = explode("-", $commitDT[1]);
		$commitDateTime = $commitDT[0]." (".$commitTD[0].")";
		$commitSHA = substr($latestCommit["sha"],0,7);

		$html .= "".
			"Current Commit: ".$config->get_string('commit_hash')." | ".$config->get_string('commit_time').
			"<br>Latest Commit: ".$commitSHA." | ".$commitDateTime." | ".$commitMessage.
			"<br><a href='" . make_link('update') . "'>Update</a>".
		"";
		$page->add_block(new Block("Software Update", $html, "main"));
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $config, $user;
		if($event->page_matches("update") && $user->is_admin()) {
			$ok = $this->update_shimmie();
		}
	}

	private function get_latest_commit() {
		global $config;

		if(!function_exists("curl_init")) return null;

		//Grab latest info via JSON.
		$g_user = $config->get_string("update_guser");
		$g_repo = $config->get_string("update_grepo");
		$base = "https://api.github.com/repos/".$g_user."/".$g_repo."/commits";
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $base);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$content = curl_exec($curl);
		curl_close($curl);
		$commits = json_decode($content, true);
		return $commits[0];
	}

	private function update_shimmie() {
		//This is a REALLY ugly function. (Damn my limited PHP knowledge >_<)
		global $config, $page;

		$latestCommit = $this->get_latest_commit();
		if(is_null($latestCommit)) return;

		$commitDT = explode("T", $latestCommit["commit"]["committer"]["date"]);
		$commitTD = explode("-", $commitDT[1]);
		$commitDateTime = $commitDT[0]." (".$commitTD[0].")";
		$commitSHA = substr($latestCommit["sha"],0,7);

		$html = "";
		$url = "http://nodeload.github.com/".$g_user."/".$g_repo."/zipball/".$commitSHA;
		$mfile = "master.zip";
		if(glob("*-shimmie2-".$commitSHA)) { //#3
			$dir = glob("*-shimmie2-".$commitSHA);
			preg_match('@^([a-zA-Z0-9]+\-[0-9a-z]+\-)([^/]+)@i', $dir[0], $matches);
			if(!empty($matches[2])) {
				$html .= "commit: ".$matches[2];
				$commit = $matches[2];
				mkdir("./backup");
				$html .= "<br>backup folder created!";
				$d_dir = "data/cache";
				//This should empty the /data/cache/ folder.
				if (is_dir($d_dir)) {
					$objects = scandir($d_dir);
					foreach ($objects as $object) {
						if ($object != "." && $object != "..") {
							if (filetype($d_dir."/".$object) == "dir") rmdir($d_dir."/".$object); else unlink($d_dir."/".$object);
						}
					}
					reset($objects);
					$html .= "<br>data folder emptied!";
				}
				copy ("./config.php", "./backup/config.php");//Although this stays the same, will keep backup just incase.
				$folders = array("./core", "./lib", "./themes", "./.htaccess", "./doxygen.conf", "./index.php", "./install.php", "./ext", "./contrib");
				foreach($folders as $folder){
					//TODO: Check MD5 of each file, don't rename if same.
					rename ($folder, "./backup".substr($folder, 1)); //Move old files to backup
					rename ("./".$matches[0].substr($folder, 1), $folder); //Move new files to main
				}
				$html .= "<br>old shimmie setup has been moved to /backup/ (excluding images/thumbs)!";
				if (is_dir($matches[0])) {
					$objects = scandir($matches[0]);
					foreach ($objects as $object) {
						if ($object != "." && $object != "..") {
							if (filetype($matches[0]."/".$object) == "dir") rmdir($matches[0]."/".$object); else unlink($matches[0]."/".$object);
						}
					}
					reset($objects);
					rmdir($matches[0]);
					$html .= "<br>".$matches[0]." deleted!";
				}
				$html .= "<br>shimmie updated (although you may have gotten errors, it should have worked!";
				$html .= "<br>due to the way shimmie loads extensions, all optional extensions have been disabled";
				$config->set_string("commit_hash", $commit);
				$config->set_string("commit_time", $commitDateTime);
				$html .= "<br>new commit_hash has been set!";
			}
			else {
				$html .= "Error! Folder does not exist!?"; //Although this should be impossible, shall have it anyway.
			}
		}
		elseif (file_exists($mfile)) { //#2
			$zip = new ZipArchive;
			if ($zip->open($mfile) === TRUE) {
				$zip->extractTo('./');
				$zip->close();
				$html .= "extracted!";
				$html .= "<br><a href='javascript:history.go(0)'>refresh</a> the page to continue!";
				unlink($mfile); //Deletes master.zip
			}
			else {
				$html .= "failed!";
			}
		}
		else { //#1
			//Taken from the upload ext.
			transload($url, $mfile);

			if(file_exists($mfile)) {
				$html .= "downloaded!";
				$html .= "<br><a href='javascript:history.go(0)'>refresh</a> the page to continue!";
			}
			else {
				$html .= "download failed!";
				$html .= "<br><a href='javascript:history.go(0)'>refresh</a> to try again!";
				$html .= "<br>if you keep having this problem, you may have a problem with your transload engine!";
			}
		}

		$page->add_block(new Block("Update", $html));
	}
}

?>
