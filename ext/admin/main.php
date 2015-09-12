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
 *  <br>Download all the images as a .zip file (Requires ZipArchive)
 */

/**
 * Sent when the admin page is ready to be added to
 */
class AdminBuildingEvent extends Event {
	/** @var \Page */
	public $page;

	/**
	 * @param Page $page
	 */
	public function __construct(Page $page) {
		$this->page = $page;
	}
}

class AdminActionEvent extends Event {
	/** @var string */
	public $action;
	/** @var bool */
	public $redirect = true;

	/**
	 * @param string $action
	 */
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
		global $page;
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
		preg_match("#^(?P<proto>\w+)\:(?:user=(?P<user>\w+)(?:;|$)|password=(?P<password>\w*)(?:;|$)|host=(?P<host>[\w\.\-]+)(?:;|$)|dbname=(?P<dbname>[\w_]+)(?:;|$))+#", DATABASE_DSN, $matches);
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
			default:
				$cmd = false;
		}

		//FIXME: .SQL dump is empty if cmd doesn't exist

		if($cmd) {
			$page->set_mode("data");
			$page->set_type("application/x-unknown");
			$page->set_filename('shimmie-'.date('Ymd').'.sql');
			$page->set_data(shell_exec($cmd));
		}

		return false;
	}

	private function download_all_images() {
		global $database, $page;

		$images = $database->get_all("SELECT hash, ext FROM images");
		$filename = data_path('imgdump-'.date('Ymd').'.zip');

		$zip = new ZipArchive;
		if($zip->open($filename, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) === TRUE){
			foreach($images as $img){
				$img_loc = warehouse_path("images", $img["hash"], FALSE);
				$zip->addFile($img_loc, $img["hash"].".".$img["ext"]);
			}
			$zip->close();
		}

		$page->set_mode("redirect");
		$page->set_redirect(make_link($filename)); //TODO: Delete file after downloaded?

		return false;  // we do want a redirect, but a manual one
	}

    private function reset_image_ids() {
        global $database;

		//TODO: Make work with PostgreSQL + SQLite
		//TODO: Update score_log (Having an optional ID column for score_log would be nice..)
		preg_match("#^(?P<proto>\w+)\:(?:user=(?P<user>\w+)(?:;|$)|password=(?P<password>\w*)(?:;|$)|host=(?P<host>[\w\.\-]+)(?:;|$)|dbname=(?P<dbname>[\w_]+)(?:;|$))+#", DATABASE_DSN, $matches);

		if($matches['proto'] == "mysql"){
			$tables = $database->get_col("SELECT TABLE_NAME
			                              FROM information_schema.KEY_COLUMN_USAGE
			                              WHERE TABLE_SCHEMA = :db
			                              AND REFERENCED_COLUMN_NAME = 'id'
			                              AND REFERENCED_TABLE_NAME = 'images'", array("db" => $matches['dbname']));

			$i = 1;
			$ids = $database->get_col("SELECT id FROM images ORDER BY images.id ASC");
			foreach($ids as $id){
				$sql = "SET FOREIGN_KEY_CHECKS=0;
				        UPDATE images SET id={$i} WHERE image_id={$id};";

				foreach($tables as $table){
					$sql .= "UPDATE {$table} SET image_id={$i} WHERE image_id={$id};";
				}

				$sql .= " SET FOREIGN_KEY_CHECKS=1;";
				$database->execute($sql);

				$i++;
			}
			$database->execute("ALTER TABLE images AUTO_INCREMENT=".(count($ids) + 1));
		}elseif($matches['proto'] == "pgsql"){
			//TODO: Make this work with PostgreSQL
		}elseif($matches['proto'] == "sqlite"){
			//TODO: Make this work with SQLite
		}
        return true;
    }
}

