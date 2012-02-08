<?php
/**
 * Name: Update
 * Author: DakuTree <dakutree@codeanimu.net>
 * Link: http://www.codeanimu.net
 * License: GPLv2
 * Description: Shimmie updater!
 */
class Update extends Extension {
	public function onInitExt(InitExtEvent $event) {
		global $config;
		$config->set_default_string("update_url", "http://nodeload.github.com/shish/shimmie2/zipball/master"); //best to avoid using https
		$config->set_default_string("commit_hash", "");
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		global $config;
		//Would prefer to use the admin panel for this.
		//But since the admin panel is optional...kind of stuck to using this.
		$sb = new SetupBlock("Update");
		$sb->position = 75;
		$sb->add_label("Current Commit: ".$config->get_string('commit_hash'));
		$sb->add_text_option("update_url", "<br>Update URL: ");
		$sb->add_label("<br><a href='".make_link('update')."'>Update</a>");
		$event->panel->add_block($sb);
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $config, $user;
		if($event->page_matches("update") && $user->is_admin()) {
			$ok = $this->update_shimmie();
		}
	}

	private function update_shimmie() {
		global $config, $page;
		//This is a REALLY ugly function. (Damn my limited PHP knowledge >_<)
		$html = "";
		$url = $config->get_string("update_url");
		$mfile = "master.zip";
		if(glob("*-shimmie2*")){ //#3
			$dir = glob("*-shimmie2*");
			preg_match('@^([a-zA-Z0-9]+\-[0-9a-z]+\-)([^/]+)@i', $dir[0], $matches);
			if(!empty($matches[2])){
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
				//FIXME: Somehow get rid of this massive rename list.
				rename ("./core", "./backup/core");
				rename ("./".$matches[0]."/core", "./core");
				rename ("./lib", "./backup/lib");
				rename ("./".$matches[0]."/lib", "./lib");
				rename ("./themes", "./backup/themes");
				rename ("./".$matches[0]."/themes", "./themes");
				rename ("./.htaccess", "./backup/.htaccess");
				rename ("./".$matches[0]."/.htaccess", "./.htaccess");
				rename ("./doxygen.conf", "./backup/doxygen.conf");
				rename ("./".$matches[0]."/doxygen.conf", "./doxygen.conf");
				rename ("./index.php", "./backup/index.php");
				rename ("./".$matches[0]."/index.php", "./index.php");
				rename ("./install.php", "./backup/install.php");
				rename ("./".$matches[0]."/install.php", "./install.php");
				rename ("./ext", "./backup/ext");
				rename ("./".$matches[0]."/ext", "./ext");
				rename ("./contrib", "./backup/contrib");
				rename ("./".$matches[0]."/contrib", "./contrib");
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
				$html .= "<br>new commit_hash has been set!";
			}else{
				$html .= "Error! Folder does not exist!?"; //Although this should be impossible, shall have it anyway.
			}
		}elseif (file_exists($mfile)){ //#2
			$zip = new ZipArchive;
			if ($zip->open($mfile) === TRUE) {
				$zip->extractTo('./');
				$zip->close();
				$html .= "extracted!";
				$html .= "<br><a href='javascript:history.go(0)'>refresh</a> the page to continue!";
				unlink($mfile); //Deletes master.zip
			} else {
				$html .= "failed!";
			}
		}else{ //#1
			//Taken from the upload ext.
			if($config->get_string("transload_engine") == "curl" && function_exists("curl_init")) {
				$ch = curl_init($url);
				$fp = fopen($mfile, "w");

				curl_setopt($ch, CURLOPT_FILE, $fp);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_REFERER, $url);
				curl_setopt($ch, CURLOPT_USERAGENT, "Shimmie-".VERSION);

				curl_exec($ch);
				curl_close($ch);
				fclose($fp);
				if(file_exists($mfile)){
					$html .= "downloaded!";
					$html .= "<br><a href='javascript:history.go(0)'>refresh</a> the page to continue!";
				}else{
					$html .= "download failed!";
					$html .= "<br><a href='javascript:history.go(0)'>refresh</a> to try again!";
					$html .= "<br>if you keep having this problem, you may have a problem with your transload engine!";
				}
			}elseif($config->get_string("transload_engine") == "wget") {
				//this doesn't work?
				$s_url = escapeshellarg($url);
				system("wget $s_url --output-document=$mfile");
				if(file_exists($mfile)){
					$html .= "downloaded!";
					$html .= "<br><a href='javascript:history.go(0)'>refresh</a> the page to continue!";
				}else{
					$html .= "download failed!";
					$html .= "<br><a href='javascript:history.go(0)'>refresh</a> to try again!";
					$html .= "<br>if you keep having this problem, you may have a problem with your transload engine!";
				}
			}elseif($config->get_string("transload_engine") == "fopen") {
				$fp = @fopen($url, "r");
				if(!$fp) {
					return false;
				}
				$data = "";
				$length = 0;
				while(!feof($fp) && $length <= $config->get_int('upload_size')) {
					$data .= fread($fp, 8192);
					$length = strlen($data);
				}
				fclose($fp);

				$fp = fopen($mfile, "w");
				fwrite($fp, $data);
				fclose($fp);
				if(file_exists($mfile)){
					$html .= "downloaded!";
					$html .= "<br><a href='javascript:history.go(0)'>refresh</a> the page to continue!";
				}else{
					$html .= "download failed!";
					$html .= "<br><a href='javascript:history.go(0)'>refresh</a> to try again!";
					$html .= "<br>if you keep having this problem, you may have a problem with your transload engine!";
				}
			}elseif($config->get_string("transload_engine") == "none"){
				$html .= "no transload engine set!";
			}
		}

		$page->add_block(new Block("Update", $html));
	}
}

?>
