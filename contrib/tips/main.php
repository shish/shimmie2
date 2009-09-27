<?php
/**
 * Name: Random Tip
 * Author: Sein Kraft <mail@seinkraft.info>
 * License: GPLv2
 * Description: Show a random line of text in the subheader space
 * Documentation:
 *  Formatting is done with HTML
 */

class Tips extends SimpleExtension {
	public function onInitExt($event) {
		global $config, $database;

		if ($config->get_int("ext_tips_version") < 1){
			$database->create_table("tips", "
					id SCORE_AIPK,
					enable SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N,
					image TEXT NOT NULL,
					text TEXT NOT NULL,
					INDEX (id)
					");

			$database->execute("
					INSERT INTO tips (enable, image, text)
					VALUES (?, ?, ?)",
					array("Y", "coins.png", "Do you like this extension? Please support us for developing new ones. <a href=\"https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8235933\" target=\"_blank\">Donate through paypal</a>."));

			$config->set_int("ext_tips_version", 1);
			log_info("tips", "extension installed");
		}
	}

	public function onPageRequest($event) {
		global $page, $user;

		$this->getTip();

		if($event->page_matches("tips")) {
			switch($event->get_arg(0)) {
				case "list":
				{
					if($user->is_admin()) {
						$this->manageTips();
						$this->getAll();
					}
					break;
				}
				case "new":
				{
					break;
				}
				case "save":
				{
					if($user->is_admin()) {
						$this->saveTip();

						$page->set_mode("redirect");
						$page->set_redirect(make_link("tips/list"));
					}
					break;
				}
				case "status":
				{
					if($user->is_admin()) {
						$tipID = int_escape($event->get_arg(1));
						$this->setStatus($tipID);

						$page->set_mode("redirect");
						$page->set_redirect(make_link("tips/list"));		
					}
					break;
				}
				case "delete":
				{
					if($user->is_admin()) {
						$tipID = int_escape($event->get_arg(1));
						$this->deleteTip($tipID);

						$page->set_mode("redirect");
						$page->set_redirect(make_link("tips/list"));		
					}
					break;
				}
			}
		}
	}

	public function onUserBlockBuilding($event) {
		global $user;
		if($user->is_admin()) {
			$event->add_link("Tips Editor", make_link("tips/list"));
		}
	}

	private function manageTips() {
		$data_href = get_base_href();
		$url = $data_href."/ext/tips/images/";

		$dirPath = dir('./ext/tips/images');
		$images = array();
		while(($file = $dirPath->read()) !== false) {
			if($file[0] != ".") {
				$images[] = trim($file);
			}
		}
		$dirPath->close();
		sort($images);

		$this->theme->manageTips($url, $images);
	}

	private function saveTip() {
		global $database;

		$enable = isset($_POST["enable"]) ? "Y" : "N";
		$image = html_escape($_POST["image"]);
		$text = $_POST["text"];

		$database->execute("
				INSERT INTO tips (enable, image, text)
				VALUES (?, ?, ?)",
				array($enable, $image, $text));

	}

	private function getTip() {
		global $database;

		$data_href = get_base_href();
		$url = $data_href."/ext/tips/images/";

		$tip = $database->get_row("SELECT * ".
				"FROM tips ".
				"WHERE enable = 'Y' ".
				"ORDER BY RAND() ".
				"LIMIT 1");

		if($tip) {
			$this->theme->showTip($url, $tip);
		}
	}

	private function getAll() {
		global $database;

		$data_href = get_base_href();
		$url = $data_href."/ext/tips/images/";

		$tips = $database->get_all("SELECT * FROM tips ORDER BY id ASC");

		$this->theme->showAll($url, $tips);
	}

	private function setStatus($tipID) {
		global $database;

		$tip = $database->get_row("SELECT * FROM tips WHERE id = ? ", array($tipID));

		if($tip['enable'] == "Y") {
			$enable = "N";
		} elseif($tip['enable'] == "N") {
			$enable = "Y";
		}

		$database->execute("UPDATE tips SET enable = ? WHERE id = ?", array ($enable, $tipID));
	}

	private function deleteTip($tipID) {
		global $database;
		$database->execute("DELETE FROM tips WHERE id = ?", array($tipID));
	}
}
?>

