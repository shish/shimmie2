<?php
/**
 * Name: Update
 * Author: DakuTree <dakutree@codeanimu.net>
 * Link: http://www.codeanimu.net
 * License: GPLv2
 * Description: Update shimmie!
 */
class Update extends SimpleExtension {
	public function onInitExt(Event $event) {
		global $config;
		$config->set_default_string("update_url", "http://nodeload.github.com/shish/shimmie2/zipball/master");
		//TODO: Check current VERSION > use commit hash.
		$config->set_default_string("commit_hash", "");
	}

	public function onPageRequest(Event $event) {
		global $config, $page;
		if($event->page_matches("update/a")) {
			if($config->get_string("commit_hash") == ""){
				$c_commit = "Unknown";
			}else{
				$c_commit = $config->get_string("commit_hash");
			}
			$tmp_filename = tempnam("/tmp", "shimmie_transload");

			//$u_commit = ""; // Get redirected url > grab hash from new filename.

			//Would prefer to use the admin panel for this.
			//But since the admin panel is optional...kind of stuck to using this.
			$html = "".make_form(make_link("update/b"))."
					Current commit hash: $c_commit
					<br><input id='updatebutton' type='submit' value='Update'>
					</form>";

			$page->add_block(new Block("Update", $html));
		}

		if($event->page_matches("update/b")) {
			$html = "Updating?";
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
				//Must be better way to do this...
				//FIXME: Somehow get rid of this massive rename list.
				rename ("./core", "./backup/core");
				rename ("./".$matches[0]."/core", "./core");
				rmdir("data");
				mkdir("data");
				rename ("./ext", "./backup/ext");
				rename ("./".$matches[0]."/ext", "./ext");
				rename ("./lib", "./backup/lib");
				rename ("./".$matches[0]."/lib", "./lib");
				rename ("./themes", "./backup/themes");
				rename ("./".$matches[0]."/themes", "./themes");
				rename ("./.htaccess", "./backup/.htaccess");
				rename ("./".$matches[0]."/.htaccess", "./.htaccess");
				copy ("./config.php", "./backup/config.php");//Although this stays the same, will keep backup just incase.
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
				rmdir ("./".$matches[0]);
				$config->set_string("commit_hash", $commit);
			}else{
				$html .= "Error! Folder does not exist!?"; //Although this should be impossible, shall have it anyway.
			}
		}elseif (file_exists($mfile)){ //#2
			$zip = new ZipArchive;
			if ($zip->open($mfile) === TRUE) {
				$zip->extractTo('./');
				$zip->close();
				$html .= "extracted!";
				$html .= "<br>refresh the page to continue!";
				unlink($mfile); //Deletes master.zip
			} else {
				$html .= "failed!";
			}
		}else{ //#1
			//TODO: Add other transload_engines!
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
				$html .= "downloaded!";
				$html .= "<br>refresh the page to continue!";
			}
		}

		$page->add_block(new Block("Update", $html));
	}
}

?>
