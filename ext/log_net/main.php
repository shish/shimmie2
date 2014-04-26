<?php
/*
 * Name: Logging (Network)
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * Description: Send log events to a network port.
 * Visibility: admin
 */

class LogNet extends Extension {
	var $count = 0;

	public function onLog(LogEvent $event) {
		global $user;

		if($event->priority > 10) {
			$this->count++;
			if($this->count < 10) {
				// TODO: colour based on event->priority
				$username = ($user && $user->name) ? $user->name : "Anonymous";
				$str = sprintf("%-15s %-10s: %s", $_SERVER['REMOTE_ADDR'], $username, $event->message);
				$this->msg($str);
			}
			else if($this->count == 10) {
				$this->msg('suppressing flood, check the web log');
			}
		}
	}

	private function msg($data) {
		global $config;
		$host = $config->get_string("log_net_host", "127.0.0.1:35353");

		if(!$host) { return; }

		try {
			$parts = explode(":", $host);
			$host = $parts[0];
			$port = $parts[1];
			$fp = fsockopen("udp://$host", $port, $errno, $errstr);
			if (! $fp) { return; }
			fwrite($fp, "$data\n");
			fclose($fp);
		} catch (Exception $e) {
			/* logging errors shouldn't break everything */
		}
	}
}

