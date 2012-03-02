<?php
/**
 * Name: Admin Controls
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Various things to make admins' lives easier
 * Documentation:
 *  <p>Lowercase all tags:
 *  <br>Set all tags to lowercase for consistency
 *  <p>Recount tag use:
 *  <br>If the counts of images per tag get messed up somehow, this will reset them
 *  <p>Purge unused tags:
 *  <br>Get rid of all the tags that don't have any images associated with
 *  them (normally they were created as typos or spam); this is mostly for
 *  neatness, the performance gain is tiny...
 *  <p>Convert to InnoDB:
 *  <br>Convert your database tables to InnoDB, thus allowing shimmie to
 *  take advantage of useful InnoDB-only features (this should be done
 *  automatically, this button only exists as a backup). This only applies
 *  to MySQL -- all other databases come with useful features enabled
 *  as standard.
 *  <p>Database dump:
 *  <br>Download the contents of the database in plain text format, useful
 *  for backups.
 */

/**
 * Sent when the admin page is ready to be added to
 */
class AdminBuildingEvent extends Event {
	var $page;
	public function AdminBuildingEvent(Page $page) {
		$this->page = $page;
	}
}

class AdminPage extends Extension {
	public function onPageRequest(PageRequestEvent $event) {
		global $page, $user;

		if($event->page_matches("admin")) {
			if(!$user->is_admin()) {
				$this->theme->display_permission_denied();
			}
			else {
				send_event(new AdminBuildingEvent($page));
			}
		}

		if($event->page_matches("admin_utils")) {
			if($user->is_admin() && $user->check_auth_token()) {
				log_info("admin", "Util: {$_POST['action']}");
				set_time_limit(0);
				$redirect = false;

				switch($_POST['action']) {
					case 'delete by query':
						$this->delete_by_query($_POST['query']);
						$redirect = true;
						break;
					case 'lowercase all tags':
						$this->lowercase_all_tags();
						$redirect = true;
						break;
					case 'recount tag use':
						$this->recount_tag_use();
						$redirect = true;
						break;
					case 'purge unused tags':
						$this->purge_unused_tags();
						$redirect = true;
						break;
					case 'convert to innodb':
						$this->convert_to_innodb();
						$redirect = true;
						break;
					case 'database dump':
						$this->dbdump($page);
						break;
					case 'reset image ids':
						$this->reset_imageids();
						$redirect = true;
						break;
					case 'image dump':
						$this->imgdump($page);
						break;
				}

				if($redirect) {
					$page->set_mode("redirect");
					$page->set_redirect(make_link("admin"));
				}
			}
		}
	}

	public function onAdminBuilding(AdminBuildingEvent $event) {
		global $page;
		$this->theme->display_page($page);
		$this->theme->display_form($page);
	}

	public function onUserBlockBuilding(UserBlockBuildingEvent $event) {
		global $user;
		if($user->is_admin()) {
			$event->add_link("Board Admin", make_link("admin"));
		}
	}

	private function delete_by_query(/*array(string)*/ $query) {
		global $page, $user;
		assert(strlen($query) > 1);
		foreach(Image::find_images(0, 1000000, Tag::explode($query)) as $image) {
			send_event(new ImageDeletionEvent($image));
		}
	}

	private function lowercase_all_tags() {
		global $database;
		$database->execute("UPDATE tags SET tag=lower(tag)");
	}

	private function recount_tag_use() {
		global $database;
		$database->Execute("
			UPDATE tags
			SET count = COALESCE(
				(SELECT COUNT(image_id) FROM image_tags WHERE tag_id=tags.id GROUP BY tag_id),
				0
			)");
	}

	private function purge_unused_tags() {
		global $database;
		$this->recount_tag_use();
		$database->Execute("DELETE FROM tags WHERE count=0");
	}

	private function dbdump(Page $page) {
		$matches = array();
		preg_match("#^(?P<proto>\w+)\:(?:user=(?P<user>\w+)(?:;|$)|password=(?P<password>\w+)(?:;|$)|host=(?P<host>[\w\.\-]+)(?:;|$)|dbname=(?P<dbname>[\w_]+)(?:;|$))+#", DATABASE_DSN, $matches);
		$software = $matches['proto'];
		$username = $matches['user'];
		$password = $matches['password'];
		$hostname = $matches['host'];
		$database = $matches['dbname'];

		// TODO: Support more than just MySQL..
		switch($software) {
			case 'mysql':
				$cmd = "mysqldump -h$hostname -u$username -p$password $database";
				break;
		}

		$page->set_mode("data");
		$page->set_type("application/x-unknown");
		$page->set_filename('shimmie-'.date('Ymd').'.sql');
		$page->set_data(shell_exec($cmd));
	}

	private function check_for_orphanned_images() {
		$orphans = array();
		foreach(glob("images/*") as $dir) {
			foreach(glob("$dir/*") as $file) {
				$hash = str_replace("$dir/", "", $file);
				if(!$this->db_has_hash($hash)) {
					$orphans[] = $hash;
				}
			}
		}
	}

	/*
	private function convert_to_innodb() {
		global $database;
		if($database->engine->name == "mysql") {
			$tables = $database->db->MetaTables();
			foreach($tables as $table) {
				log_info("upgrade", "converting $table to innodb");
				$database->execute("ALTER TABLE $table TYPE=INNODB");
			}
		}
	}
	*/

	private function reset_imageids() {
		global $database;
		//This might be a bit laggy on boards with lots of images (?)
		//Seems to work fine with 1.2k~ images though.
		$i = 0;
		$image = $database->get_all("SELECT * FROM images ORDER BY images.id ASC");
		/*$score_log = $database->get_all("SELECT message FROM score_log");*/
		foreach($image as $img){
			$xid = $img[0];
			$i = $i + 1;
			$table = array( //Might be missing some tables?
				"image_tags", "tag_histories", "image_reports", "comments", "user_favorites", "tag_histories",
				"numeric_score_votes", "pool_images", "slext_progress_cache", "notes");

			$sql =
				"SET FOREIGN_KEY_CHECKS=0;
				UPDATE images
				SET id=".$i.
				" WHERE id=".$xid.";"; //id for images

			foreach($table as $tbl){
				$sql .= "
					UPDATE ".$tbl."
					SET image_id=".$i."
					WHERE image_id=".$xid.";";
			}

			/*foreach($score_log as $sl){
				//This seems like a bad idea.
				//TODO: Might be better for log_info to have an $id option (which would then affix the id to the table?)
				preg_replace(".Image \\#[0-9]+.", "Image #".$i, $sl);
			}*/
			$sql .= " SET FOREIGN_KEY_CHECKS=1;";
			$database->execute($sql);
		}
		$count = (count($image)) + 1;
		$database->execute("ALTER TABLE images AUTO_INCREMENT=".$count);
	}

	private function imgdump(Page $page) {
		global $database;
		$zip = new ZipArchive;
		$images = $database->get_all("SELECT * FROM images");
		$filename = 'imgdump-'.date('Ymd').'.zip';

		if($zip->open($filename, 1 ? ZIPARCHIVE::OVERWRITE:ZIPARCHIVE::CREATE)===TRUE){
			foreach($images as $img){
				$hash = $img["hash"];
				preg_match("^[A-Za-z0-9]{2}^", $hash, $matches);
				$img_loc = "images/".$matches[0]."/".$hash;
				if(file_exists($img_loc)){
					$zip->addFile($img_loc, $hash.".".$img["ext"]);
				}

			}
			$zip->close();
		}
		$page->set_mode("redirect");
		$page->set_redirect(make_link($filename)); //Fairly sure there is better way to do this..
		//TODO: Delete file after downloaded?
	}
}
?>
