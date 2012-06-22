<?php
/*
* Name: Live Feed
* Author: Shish <webmaster@shishnet.org>
* License: GPLv2
* Visibility: admin
* Description: Logs user-safe (no IPs) data to a UDP socket, eg IRCCat
* Documentation:
*/

class LiveFeed extends Extension {
	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Live Feed");
		$sb->add_string_option("IP/port to send events to");
		$event->panel->add_block($sb);
	}

	public function onUserCreation($event) {
		$this->msg("New user created: {$event->user}");
	}

	public function onImageAddition($event) {
		$this->msg("Image posted: ".make_http(make_link("post/view/".$event->image->id)));
		$this->msg("- tagged ".$event->image->get_tag_list());
	}

	public function onCommentPosting($event) {
		$this->msg("Comment posted on ".make_http(make_link("post/view/".$event->image_id))." :");
		$this->msg("- {$event->user->name}: {$event->comment}");
	}

	public function onImageInfoSet($event) {
#		$this->msg("Image info set");
	}

	public function get_priority() {return 99;}

    private function msg($data) {
		global $config;
		$host = $config->get_string("livefeed_host", "127.0.0.1:25252");

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
        }
    }
}
