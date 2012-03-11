<?php
/*
 * Name: Not A Tag
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Redirect users to the rules if they use bad tags
 */
class NotATag extends Extension {
	public function get_priority() {return 30;} // before ImageUploadEvent and tag_history

	public function onImageAddition(ImageAdditionEvent $event) {
		$this->scan($event->image->get_tag_array());
	}

	public function onTagSet(TagSetEvent $event) {
		$this->scan($event->tags);
	}

	private function scan(/*array*/ $tags_mixed) {
		global $config;

		$text = $config->get_string("not_a_tag_untags");
		if(empty($text)) return;

		$tags = array();
		foreach($tags_mixed as $tag) $tags[] = strtolower($tag);

		$pairs = explode("\n", $text);
		foreach($pairs as $pair) {
			$tag_url = explode(",", $pair);
			if(count($tag_url) != 2) continue;
			$tag = strtolower($tag_url[0]);
			$url = $tag_url[1];
			if(in_array($tag, $tags)) {
				header("Location: $url");
				exit; # FIXME: need a better way of aborting the tag-set or upload
			}
		}
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Un-Tags");

		$sb->add_label("List tag,url pairs");
		$sb->add_longtext_option("not_a_tag_untags");
		$sb->add_label("<br>(eg. 'deleteme,/wiki/reporting-images')");

		$event->panel->add_block($sb);
	}
}
?>
