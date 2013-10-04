<?php
/**
 * Name: Admin Controls
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Various things to make admins' lives easier
 * Documentation:
 *  Various moderate-level tools for admins; for advanced, obscure, and
 *  possibly dangerous tools see the shimmie2-utils script set
 *  <p>Lowercase all tags:
 *  <br>Set all tags to lowercase for consistency
 *  <p>Recount tag use:
 *  <br>If the counts of images per tag get messed up somehow, this will
 *  reset them, and remove any unused tags
 *  <p>Database dump:
 *  <br>Download the contents of the database in plain text format, useful
 *  for backups.
 *  <p>Image dump:
 *  <br>Download all the images as a .zip file
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
			if(!$user->can("manage_admintools")) {
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

	public function onCommand(CommandEvent $event) {
		if($event->cmd == "help") {
			print "  get-page [query string]\n";
			print "    eg 'get-page post/list'\n\n";
		}
		if($event->cmd == "get-page") {
			global $page;
			send_event(new PageRequestEvent($event->args[0]));
			$page->display();
		}
	}

	public function onAdminBuilding(AdminBuildingEvent $event) {
		$this->theme->display_page();
		$this->theme->display_form();
	}

	public function onUserBlockBuilding(UserBlockBuildingEvent $event) {
		global $user;
		if($user->can("manage_admintools")) {
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
		if($user->can("manage_admintools") && !empty($event->search_terms)) {
			$event->add_control($this->theme->dbq_html(implode(" ", $event->search_terms)));
		}
	}

	private function delete_by_query() {
		global $page, $user;
		$query = $_POST['query'];
		$reason = @$_POST['reason'];
		assert(strlen($query) > 1);

		log_warning("admin", "Mass deleting: $query");
		$count = 0;
		foreach(Image::find_images(0, 1000000, Tag::explode($query)) as $image) {
			if($reason && class_exists("ImageBan")) {
				send_event(new AddImageHashBanEvent($image->hash, $reason));
			}
			send_event(new ImageDeletionEvent($image));
			$count++;
		}
		log_debug("admin", "Deleted $count images", true);

		$page->set_mode("redirect");
		$page->set_redirect(make_link("post/list"));
		return false;
	}

	private function set_tag_case() {
		global $database;
		$database->execute($database->scoreql_to_sql(
			"UPDATE tags SET tag=:tag1 WHERE SCORE_STRNORM(tag) = SCORE_STRNORM(:tag2)"
		), array("tag1" => $_POST['tag'], "tag2" => $_POST['tag']));
		log_info("admin", "Fixed the case of ".html_escape($_POST['tag']), true);
		return true;
	}

	private function lowercase_all_tags() {
		global $database;
		$database->execute("UPDATE tags SET tag=lower(tag)");
		log_warning("admin", "Set all tags to lowercase", true);
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
		$database->Execute("DELETE FROM tags WHERE count=0");
		log_warning("admin", "Re-counted tags", true);
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

	private function download_all_images() {
		global $database, $page;

		$zip = new ZipArchive;
		$images = $database->get_all("SELECT * FROM images");
		$filename = data_path('imgdump-'.date('Ymd').'.zip');

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

    private function reset_image_ids() {
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
        return true;
    }
}
?>
