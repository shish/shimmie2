<?php
/**
 * Name: Random Tip
 * Author: Sein Kraft <mail@seinkraft.info>
 * License: GPLv2
 * Description: Show a random line of text in the subheader space
 * Documentation:
 *  Formatting is done with HTML
 */

class Tips extends Extension {
	protected $db_support = ['mysql', 'sqlite'];  // rand() ?

	public function onInitExt(InitExtEvent $event) {
		global $config, $database;

		if ($config->get_int("ext_tips_version") < 1){
			$database->create_table("tips", "
					id SCORE_AIPK,
					enable SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N,
					image TEXT NOT NULL,
					text TEXT NOT NULL,
					");

			$database->execute("
					INSERT INTO tips (enable, image, text)
					VALUES (?, ?, ?)",
					array("Y", "coins.png", "Do you like this extension? Please support us for developing new ones. <a href=\"https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8235933\" target=\"_blank\">Donate through paypal</a>."));

			$config->set_int("ext_tips_version", 1);
			log_info("tips", "extension installed");
		}
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $page, $user;

		$this->getTip();

		if($event->page_matches("tips") && $user->is_admin()) {
			switch($event->get_arg(0)) {
				case "list":
					$this->manageTips();
					$this->getAll();
					break;
				case "save":
					if($user->check_auth_token()) {
						$this->saveTip();
						$page->set_mode("redirect");
						$page->set_redirect(make_link("tips/list"));
					}
					break;
				case "status":
					// FIXME: HTTP GET CSRF
					$tipID = int_escape($event->get_arg(1));
					$this->setStatus($tipID);
					$page->set_mode("redirect");
					$page->set_redirect(make_link("tips/list"));
					break;
				case "delete":
					// FIXME: HTTP GET CSRF
					$tipID = int_escape($event->get_arg(1));
					$this->deleteTip($tipID);
					$page->set_mode("redirect");
					$page->set_redirect(make_link("tips/list"));
					break;
			}
		}
	}

	public function onUserBlockBuilding(UserBlockBuildingEvent $event) {
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

	/**
	 * @param int $tipID
	 */
	private function setStatus($tipID) {
		global $database;

		$tip = $database->get_row("SELECT * FROM tips WHERE id = ? ", array(int_escape($tipID)));

		if (bool_escape($tip['enable'])) {
			$enable = "N";
		} else {
			$enable = "Y";
		}

		$database->execute("UPDATE tips SET enable = ? WHERE id = ?", array ($enable, int_escape($tipID)));
	}

	/**
	 * @param int $tipID
	 */
	private function deleteTip($tipID) {
		global $database;
		$database->execute("DELETE FROM tips WHERE id = ?", array(int_escape($tipID)));
	}
}

