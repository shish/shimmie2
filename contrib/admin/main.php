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

class AdminActionEvent extends Event {
	var $action;
	var $redirect = true;
	public function __construct(/*string*/ $action) {
		$this->action = $action;
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
				if($event->count_args() == 0) {
					send_event(new AdminBuildingEvent($page));
				}
				else {
					$action = $event->get_arg(0);
					$aae = new AdminActionEvent($action);

					if($user->check_auth_token()) {
						log_info("admin", "Util: $action");
						set_time_limit(0);
						send_event($aae);
					}

					if($aae->redirect) {
						$page->set_mode("redirect");
						$page->set_redirect(make_link("admin"));
					}
				}
			}
		}
	}

	public function onAdminBuilding(AdminBuildingEvent $event) {
		$this->theme->display_page();
		$this->theme->display_form();
	}

	public function onUserBlockBuilding(UserBlockBuildingEvent $event) {
		global $user;
		if($user->is_admin()) {
			$event->add_link("Board Admin", make_link("admin"));
		}
	}

	public function onAdminAction(AdminActionEvent $event) {
		$action = $event->action;
		if(method_exists($this, $action)) {
			$event->redirect = $this->$action();
		}
	}

	public function onPostListBuilding(PostListBuildingEvent $event) {
		global $user;
		if($user->is_admin() && !empty($event->search_terms)) {
			$this->theme->display_dbq(implode(" ", $event->search_terms));
		}
	}

	private function delete_by_query() {
		global $page, $user;
		$query = $_POST['query'];
		assert(strlen($query) > 1);

		log_warning("admin", "Mass deleting: $query");
		foreach(Image::find_images(0, 1000000, Tag::explode($query)) as $image) {
			send_event(new ImageDeletionEvent($image));
		}

		$page->set_mode("redirect");
		$page->set_redirect(make_link("post/list"));
		return false;
	}

	private function lowercase_all_tags() {
		global $database;
		$database->execute("UPDATE tags SET tag=lower(tag)");
		return true;
	}

	private function recount_tag_use() {
		global $database;
		$database->Execute("
			UPDATE tags
			SET count = COALESCE(
				(SELECT COUNT(image_id) FROM image_tags WHERE tag_id=tags.id GROUP BY tag_id),
				0
			)
		");
		return true;
	}

	private function purge_unused_tags() {
		global $database;
		$this->recount_tag_use();
		$database->Execute("DELETE FROM tags WHERE count=0");
		return true;
	}

	private function database_dump() {
		global $page;

		$matches = array();
		preg_match("#^(?P<proto>\w+)\:(?:user=(?P<user>\w+)(?:;|$)|password=(?P<password>\w+)(?:;|$)|host=(?P<host>[\w\.\-]+)(?:;|$)|dbname=(?P<dbname>[\w_]+)(?:;|$))+#", DATABASE_DSN, $matches);
		$software = $matches['proto'];
		$username = $matches['user'];
		$password = $matches['password'];
		$hostname = $matches['host'];
		$database = $matches['dbname'];

		switch($software) {
			case 'mysql':
				$cmd = "mysqldump -h$hostname -u$username -p$password $database";
				break;
			case 'pgsql':
				putenv("PGPASSWORD=$password");
				$cmd = "pg_dump -h $hostname -U $username $database";
				break;
			case 'sqlite':
				$cmd = "sqlite3 $database .dump";
				break;
		}

		$page->set_mode("data");
		$page->set_type("application/x-unknown");
		$page->set_filename('shimmie-'.date('Ymd').'.sql');
		$page->set_data(shell_exec($cmd));

		return false;
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
		return true;
	}

	/*
	private function convert_to_innodb() {
		global $database;

		if($database->engine->name != "mysql") return;

		$tables = $database->db->MetaTables();
		foreach($tables as $table) {
			log_info("upgrade", "converting $table to innodb");
			$database->execute("ALTER TABLE $table TYPE=INNODB");
		}
		return true;
	}
	*/

	private function reset_image_ids() {
		global $database;

		if($database->engine->name != "mysql") return;

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
		return true;
	}

	private function download_all_images() {
		global $database, $page;

		$zip = new ZipArchive;
		$images = $database->get_all("SELECT * FROM images");
		$filename = 'imgdump-'.date('Ymd').'.zip';

		if($zip->open($filename, 1 ? ZIPARCHIVE::OVERWRITE:ZIPARCHIVE::CREATE) === TRUE){
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
		return false;  // we do want a redirect, but a manual one
	}
}
?>
