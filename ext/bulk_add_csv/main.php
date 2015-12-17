<?php
/*
 * Name: Bulk Add CSV
 * Author: velocity37 <velocity37@gmail.com>
 * License: GPLv2
 * Description: Bulk add server-side images with metadata from CSV file
 * Documentation:
 *  Modification of "Bulk Add" by Shish.<br><br>
 *  Adds images from a CSV with the five following values: <br>
 *  "/path/to/image.jpg","spaced tags","source","rating s/q/e","/path/thumbnail.jpg" <br>
 *  <b>e.g.</b> "/tmp/cat.png","shish oekaki","shimmie.shishnet.org","s","tmp/custom.jpg" <br><br>
 *  Any value but the first may be omitted, but there must be five values per line.<br>
 *  <b>e.g.</b> "/why/not/try/bulk_add.jpg","","","",""<br><br>
 *  Image thumbnails will be displayed at the AR of the full image. Thumbnails that are
 *  normally static (e.g. SWF) will be displayed at the board's max thumbnail size<br><br>
 *  Useful for importing tagged images without having to do database manipulation.<br>
 *  <p><b>Note:</b> requires "Admin Controls" and optionally "Image Ratings" to be enabled<br><br>
 *  
 */

class BulkAddCSV extends Extension {
	public function onPageRequest(PageRequestEvent $event) {
		global $page, $user;
		if($event->page_matches("bulk_add_csv")) {
			if($user->is_admin() && $user->check_auth_token() && isset($_POST['csv'])) {
				set_time_limit(0);
				$this->add_csv($_POST['csv']);
				$this->theme->display_upload_results($page);
			}
		}
	}

	public function onCommand(CommandEvent $event) {
		if($event->cmd == "help") {
			print "  bulk-add-csv [/path/to.csv]\n";
			print "	Import this .csv file (refer to documentation)\n\n";
		}
		if($event->cmd == "bulk-add-csv") {
			global $user;
			
			//Nag until CLI is admin by default
			if (!$user->is_admin()) {
				print "Not running as an admin, which can cause problems.\n";
				print "Please add the parameter: -u admin_username";
			} elseif(count($event->args) == 1) {
				$this->add_csv($event->args[0]);
			}
		}
	}

	public function onAdminBuilding(AdminBuildingEvent $event) {
		$this->theme->display_admin_block();
	}

	/**
	 * Generate the necessary DataUploadEvent for a given image and tags.
	 *
	 * @param string $tmpname
	 * @param string $filename
	 * @param string $tags
	 * @param string $source
	 * @param string $rating
	 * @param string $thumbfile
	 * @throws UploadException
	 */
	private function add_image($tmpname, $filename, $tags, $source, $rating, $thumbfile) {
		assert(file_exists($tmpname));

		$pathinfo = pathinfo($filename);
		if(!array_key_exists('extension', $pathinfo)) {
			throw new UploadException("File has no extension");
		}
		$metadata = array();
		$metadata['filename'] = $pathinfo['basename'];
		$metadata['extension'] = $pathinfo['extension'];
		$metadata['tags'] = $tags;
		$metadata['source'] = $source;
		$event = new DataUploadEvent($tmpname, $metadata);
		send_event($event);
		if($event->image_id == -1) {
			throw new UploadException("File type not recognised");
		} else {
			if(class_exists("RatingSetEvent") && in_array($rating, array("s", "q", "e"))) {
				$ratingevent = new RatingSetEvent(Image::by_id($event->image_id), $rating);
				send_event($ratingevent);
			}
			if (file_exists($thumbfile)) {
				copy($thumbfile, warehouse_path("thumbs", $event->hash));
			}
		}
	}

	private function add_csv(/*string*/ $csvfile) {
		if(!file_exists($csvfile)) {
			$this->theme->add_status("Error", "$csvfile not found");
			return;
		}
		if (!is_file($csvfile) || strtolower(substr($csvfile, -4)) != ".csv") {
			$this->theme->add_status("Error", "$csvfile doesn't appear to be a csv file");
			return;
		}
	
		$linenum = 1;
		$list = "";
		$csvhandle = fopen($csvfile, "r");
		
		while (($csvdata = fgetcsv($csvhandle, 0, ",")) !== FALSE) {
			if(count($csvdata) != 5) {
				if(strlen($list) > 0) {
					$this->theme->add_status("Error", "<b>Encountered malformed data. Line $linenum $csvfile</b><br>".$list);
					fclose($csvhandle);
					return;
				} else {
					$this->theme->add_status("Error", "<b>Encountered malformed data. Line $linenum $csvfile</b><br>Check <a href=\"" . make_link("ext_doc/bulk_add_csv") . "\">here</a> for the expected format");
					fclose($csvhandle);
					return;
				}
			}
			$fullpath = $csvdata[0];
			$tags = trim($csvdata[1]);
			$source = $csvdata[2];
			$rating = $csvdata[3];
			$thumbfile = $csvdata[4];
			$pathinfo = pathinfo($fullpath);
			$shortpath = $pathinfo["basename"];
			$list .= "<br>".html_escape("$shortpath (".str_replace(" ", ", ", $tags).")... ");
			if (file_exists($csvdata[0]) && is_file($csvdata[0])) {
				try{
					$this->add_image($fullpath, $pathinfo["basename"], $tags, $source, $rating, $thumbfile);
					$list .= "ok\n";
				}
				catch(Exception $ex) {
					$list .= "failed:<br>". $ex->getMessage();
				}
			} else {
				$list .= "failed:<br> File doesn't exist ".html_escape($csvdata[0]);
			}
			$linenum += 1;
		}
		
		if(strlen($list) > 0) {
			$this->theme->add_status("Adding $csvfile", $list);
		}
		fclose($csvhandle);
	}
}

