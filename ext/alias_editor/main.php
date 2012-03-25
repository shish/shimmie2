<?php
/**
 * Name: Alias Editor
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Edit the alias list
 * Documentation:
 *  The list is visible at <a href="$site/alias/list">/alias/list</a>; only
 *  site admins can edit it, other people can view and download it
 */

class AddAliasEvent extends Event {
	var $oldtag;
	var $newtag;

	public function AddAliasEvent($oldtag, $newtag) {
		$this->oldtag = $oldtag;
		$this->newtag = $newtag;
	}
}

class AddAliasException extends SCoreException {}

class AliasEditor extends Extension {
	public function onPageRequest(PageRequestEvent $event) {
		global $config, $database, $page, $user;

		if($event->page_matches("alias")) {
			if($event->get_arg(0) == "add") {
				if($user->can("manage_alias_list")) {
					if(isset($_POST['oldtag']) && isset($_POST['newtag'])) {
						try {
							$aae = new AddAliasEvent($_POST['oldtag'], $_POST['newtag']);
							send_event($aae);
							$page->set_mode("redirect");
							$page->set_redirect(make_link("alias/list"));
						}
						catch(AddAliasException $ex) {
							$this->theme->display_error(500, "Error adding alias", $ex->getMessage());
						}
					}
				}
			}
			else if($event->get_arg(0) == "remove") {
				if($user->can("manage_alias_list")) {
					if(isset($_POST['oldtag'])) {
						$database->execute("DELETE FROM aliases WHERE oldtag=:oldtag", array("oldtag" => $_POST['oldtag']));
						log_info("alias_editor", "Deleted alias for ".$_POST['oldtag']);

						$page->set_mode("redirect");
						$page->set_redirect(make_link("alias/list"));
					}
				}
			}
			else if($event->get_arg(0) == "list") {
				$page_number = $event->get_arg(1);
				if(is_null($page_number) || !is_numeric($page_number)) {
					$page_number = 0;
				}
				else if ($page_number <= 0) {
					$page_number = 0;
				}
				else {
					$page_number--;
				}

				$alias_per_page = $config->get_int('alias_items_per_page', 30);

				$query = "SELECT oldtag, newtag FROM aliases ORDER BY newtag ASC LIMIT :limit OFFSET :offset";
				$alias = $database->get_pairs($query,
					array("limit"=>$alias_per_page, "offset"=>$page_number * $alias_per_page)
				);

				$total_pages = ceil($database->get_one("SELECT COUNT(*) FROM aliases") / $alias_per_page);

				$this->theme->display_aliases($alias, $page_number + 1, $total_pages);
			}
			else if($event->get_arg(0) == "export") {
				$page->set_mode("data");
				$page->set_type("text/plain");
				$page->set_data($this->get_alias_csv($database));
			}
			else if($event->get_arg(0) == "import") {
				if($user->can("manage_alias_list")) {
					if(count($_FILES) > 0) {
						$tmp = $_FILES['alias_file']['tmp_name'];
						$contents = file_get_contents($tmp);
						$this->add_alias_csv($database, $contents);
						$page->set_mode("redirect");
						$page->set_redirect(make_link("alias/list"));
					}
					else {
						$this->theme->display_error(400, "No File Specified", "You have to upload a file");
					}
				}
				else {
					$this->theme->display_error(401, "Admins Only", "Only admins can edit the alias list");
				}
			}
		}
	}

	public function onAddAlias(AddAliasEvent $event) {
		global $database;
		$pair = array("oldtag" => $event->oldtag, "newtag" => $event->newtag);
		if($database->get_row("SELECT * FROM aliases WHERE oldtag=:oldtag AND lower(newtag)=lower(:newtag)", $pair)) {
			throw new AddAliasException("That alias already exists");
		}
		else {
			$database->execute("INSERT INTO aliases(oldtag, newtag) VALUES(:oldtag, :newtag)", $pair);
			log_info("alias_editor", "Added alias for {$event->oldtag} -> {$event->newtag}");
		}
	}

	public function onUserBlockBuilding(UserBlockBuildingEvent $event) {
		global $user;
		if($user->can("manage_alias_list")) {
			$event->add_link("Alias Editor", make_link("alias/list"));
		}
	}

	private function get_alias_csv(Database $database) {
		$csv = "";
		$aliases = $database->get_pairs("SELECT oldtag, newtag FROM aliases ORDER BY newtag");
		foreach($aliases as $old => $new) {
			$csv .= "$old,$new\n";
		}
		return $csv;
	}

	private function add_alias_csv(Database $database, /*string*/ $csv) {
		$csv = str_replace("\r", "\n", $csv);
		foreach(explode("\n", $csv) as $line) {
			$parts = explode(",", $line);
			if(count($parts) == 2) {
				$database->execute("INSERT INTO aliases(oldtag, newtag) VALUES(:oldtag, :newtag)", array("oldtag" => $parts[0], "newtag" => $parts[1]));
			}
		}
	}
}
?>
