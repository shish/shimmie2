<?php
/*
 * Name: Logging (Network)
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * Description: Send log events to a network port.
 * Visibility: admin
 */

class LogNet extends Extension {
	public function onLog(LogEvent $event) {
		global $user;

		if($event->priority > 10) {
			$username = ($user && $user->name) ? $user->name : "Anonymous";
			$str = sprintf("%2d %15s (%s): %s - %s", $event->priority, $_SERVER['REMOTE_ADDR'], $username, $event->section, $event->message);
			system("echo ".escapeshellarg($str)." | nc -q 0 localhost 5000");
		}
	}
}
?>
