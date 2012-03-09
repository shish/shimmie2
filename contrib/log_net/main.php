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
			// TODO: colour based on event->priority
			$username = ($user && $user->name) ? $user->name : "Anonymous";
			$str = sprintf("%-15s %-10s: %s", $_SERVER['REMOTE_ADDR'], $username, $event->message);
			system("echo ".escapeshellarg($str)." | nc -q 0 localhost 5000");
		}
	}
}
?>
