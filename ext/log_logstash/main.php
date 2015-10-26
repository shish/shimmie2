<?php
/*
 * Name: Logging (Logstash)
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * Description: Send log events to a network port.
 * Visibility: admin
 */

class LogLogstash extends Extension {
	
	public function onLog(LogEvent $event) {
		global $user;

		try {
			$data = array(
				"@type" => "shimmie",
				"@message" => $event->message,
				"@fields" => array(
					"username" => ($user && $user->name) ? $user->name : "Anonymous",
					"section" => $event->section,
					"priority" => $event->priority,
					"time" => $event->time,
					"args" => $event->args,
				),
				#"@request" => $_SERVER,
				"@request" => array(
					"UID" => get_request_id(),
					"REMOTE_ADDR" => $_SERVER['REMOTE_ADDR'],
				),
			);

			$this->send_data($data);
		} catch (Exception $e) {
		}
	}

	private function send_data($data) {
		global $config;

		$host = $config->get_string("log_logstash_host");
		if(!$host) { return; }

		try {
			$parts = explode(":", $host);
			$host = $parts[0];
			$port = $parts[1];
			$fp = fsockopen("udp://$host", $port, $errno, $errstr);
			if (! $fp) { return; }
			fwrite($fp, json_encode($data));
			fclose($fp);
		} catch (Exception $e) {
		}
	}
}
