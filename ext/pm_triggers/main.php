<?php
/*
 * Name: [Beta] PM triggers
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Send PMs in response to certain events (eg image deletion)
 */

class PMTrigger extends Extension {
	public function onImageDeletion(ImageDeletionEvent $event) {
		$this->send(
			$event->image->owner_id,
			"[System] An image you uploaded has been deleted",
			"Image le gone~ (#{$event->image->id}, {$event->image->get_tag_list()})"
		);
	}

	private function send($to_id, $subject, $body) {
		global $user;
		send_event(new SendPMEvent(new PM(
			$user->id,
			$_SERVER["REMOTE_ADDR"],
			$to_id,
			$subject,
			$body
		)));
	}
}

