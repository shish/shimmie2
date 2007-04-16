<?php

class Downtime extends Extension {
// event handler {{{
	public function receive_event($event) {
		$this->check_downtime($event);

		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("Downtime");
			$sb->add_label("Disable non-admin access: ");
			$sb->add_bool_option("downtime");
			$sb->add_label("<br>");
			$sb->add_longtext_option("downtime_message");
			$event->panel->add_main_block($sb);
		}
		if(is_a($event, 'ConfigSaveEvent')) {
			$event->config->set_bool("downtime", $_POST['downtime']);
			$event->config->set_string("downtime_message", $_POST['downtime_message']);
		}
	}
// }}}
// do things {{{
	private function check_downtime($event) {
		global $user;
		global $config;

		if($config->get_bool("downtime") && !$user->is_admin() && 
				is_a($event, 'PageRequestEvent') && !$this->is_safe_page($event)) {
			$msg = $config->get_string("downtime_message");
			print <<<EOD
<html>
	<head>
		<title>Downtime</title>
	</head>
	<body>
		$msg
	</body>
</html>
EOD;
			exit;
		}
	}

	private function is_safe_page($event) {
		if($event->page == "user" && $event->get_arg(0) == "login") return true;
		else return false;
	}
// }}}
}
add_event_listener(new Downtime(), 10);
?>
