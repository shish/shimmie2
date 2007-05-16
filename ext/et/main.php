<?php

class ET extends Extension {
// event handler {{{
	public function receive_event($event) {
		if(is_a($event, 'PageRequestEvent') && ($event->page == "phone_home")) {
			global $user;
			if($user->is_admin()) {
				$this->phone_home();
			}
		}

		if(is_a($event, 'AdminBuildingEvent')) {
			global $page;
			$page->add_main_block(new Block("Gather System Info", $this->build_phone_home()), 99);
		}
	}
// }}}
// do it {{{
	private function phone_home() {
		global $page;
		$page->set_title("System Info");
		$page->set_heading("System Info");
		$page->add_side_block(new NavBlock());
		$page->add_main_block(new Block("Data which is to be sent:", $this->build_data_form()));
	}
	private function build_data_form() {
		global $database;
		global $config;
		global $_event_listeners; // yay for using secret globals \o/

		$data = "";

		$data .= "Optional:\n";
		$data .= "Add this site to the public shimmie users list: No\n";
		$data .= "Site title: ".($config->get_string("title"))."\n";
		$data .= "Theme: ".($config->get_string("theme"))."\n";
		$data .= "Genre: [please write something here]\n";

		$data .= "\nSystem stats:\n";
		$data .= "PHP: ".phpversion()."\n";
		$data .= "OS: ".php_uname()."\n";
		$data .= "Server: ".($_SERVER["SERVER_SOFTWARE"])."\n";

		include "config.php";
		$proto = preg_replace("#(.*)://.*#", "$1", $database_dsn);
		$db = $database->db->ServerInfo();
		$data .= "Database: $proto / {$db['version']}\n";

		$data .= "\nShimmie stats:\n";
		$uri = isset($_SERVER['SCRIPT_URI']) ? dirname($_SERVER['SCRIPT_URI']) : "???";
		$data .= "URL: ".($uri)."\n";
		$data .= "Version: ".($config->get_string("version"))."\n";
		$data .= "Images: ".($database->db->GetOne("SELECT COUNT(*) FROM images"))."\n";
		$data .= "Comments: ".($database->db->GetOne("SELECT COUNT(*) FROM comments"))."\n";
		$data .= "Users: ".($database->db->GetOne("SELECT COUNT(*) FROM users"))."\n";
		$data .= "Tags: ".($database->db->GetOne("SELECT COUNT(*) FROM tags"))."\n";

		$els = array();
		foreach($_event_listeners as $el) {
			$els[] = get_class($el);
		}
		$data .= "Extensions: ".join(", ", $els)."\n";
		
		//$cfs = array();
		//foreach($database->db->GetAll("SELECT name, value FROM config") as $pair) {
		//	$cfs[] = $pair['name']."=".$pair['value'];
		//}
		//$data .= "Config: ".join(", ", $cfs);

		$html = "
			<form action='http://shimmie.shishnet.org/register.php' method='POST'>
				<input type='hidden' name='registration_api' value='1'>
				<textarea name='data' rows='20' cols='80'>$data</textarea>
				<br><input type='submit' value='Click to send to Shish'>
				<br>Your stats are useful so that I know which combinations
				of web servers / databases / etc I need to support,
				<br>and so
				that I can get some idea of how people use shimmie generally
			</form>
		";
		return $html;
	}
// }}}
// admin page HTML {{{
	private function build_phone_home() {
		global $database;
		$h_bans = "";
		$html = "
			This button will gather various bits of information about
			your system (PHP version, database, etc) which will be
			useful in debugging~

			<p><form action='".make_link("phone_home")."' method='POST'>
				<input type='submit' value='Gather Info'>
			</form>
		";
		return $html;
	}
// }}}
}
add_event_listener(new ET());
?>
