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
		$sb->add_text_option("livefeed_host", "IP:port to send events to: ");
		$event->panel->add_block($sb);
	}

	public function onUserCreation(UserCreationEvent $event) {
		$this->msg("New user created: {$event->username}");
	}

	public function onImageAddition(ImageAdditionEvent $event) {
		global $user;
		$this->msg(
			make_http(make_link("post/view/".$event->image->id))." - ".
			"new post by ".$user->name
		);
	}

	public function onTagSet(TagSetEvent $event) {
		$this->msg(
			make_http(make_link("post/view/".$event->image->id))." - ".
			"tags set to: ".Tag::implode($event->tags)
		);
	}

	public function onCommentPosting(CommentPostingEvent $event) {
		global $user;
		$this->msg(
			make_http(make_link("post/view/".$event->image_id))." - ".
			$user->name . ": " . str_replace("\n", " ", $event->comment)
		);
	}

	public function onImageInfoSet(ImageInfoSetEvent $event) {
#		$this->msg("Image info set");
	}

	public function get_priority() {return 99;}

	/**
	 * @param string $data
	 */
    private function msg($data) {
		global $config;
		assert('is_string($data)');

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
            /* logging errors shouldn't break everything */
        }
    }
}
