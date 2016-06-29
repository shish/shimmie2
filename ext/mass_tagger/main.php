<?php
/*
 * Name: Mass Tagger
 * Author: Christian Walde <walde.christian@googlemail.com>, contributions by Shish and Agasa
 * License: WTFPL
 * Description: Tag a bunch of images at once
 * Documentation:
 *  Once enabled, a new "Mass Tagger" box will appear on the left hand side of
 *  post listings, with a button to enable the mass tagger. Once clicked JS will
 *  add buttons to each image to mark them for tagging, and a field for
 *  inputting tags will appear. Once the "Tag" button is clicked, the tags in
 *  the text field will be added to marked images.
 */

class MassTagger extends Extension {
	public function onPostListBuilding(PostListBuildingEvent $event) {
		global $config, $page, $user;
		
		if($user->is_admin()) {
			$this->theme->display_mass_tagger( $page, $event, $config );
		}
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $page, $user;
		if($event->page_matches("mass_tagger/tag") && $user->is_admin()) {
			if( !isset($_POST['ids']) or !isset($_POST['tag']) ) return;

			$tag = $_POST['tag'];

			$tag_array = explode(" ",$tag);
			$pos_tag_array = array();
			$neg_tag_array = array();
			foreach($tag_array as $new_tag) {
				if (strpos($new_tag, '-') === 0)
					$neg_tag_array[] = substr($new_tag,1);
				else
					$pos_tag_array[] = $new_tag;
			}

			$ids = explode( ':', $_POST['ids'] );
			$ids = array_filter ( $ids , 'is_numeric' );

			$images = array_map( "Image::by_id", $ids );

			if(isset($_POST['setadd']) && $_POST['setadd'] == 'set') {
				foreach($images as $image) {
					$event = new TagSetEvent ($image, $tag);
					self::onTagSet($event);
				}
			}
			else {
				foreach($images as $image) {
					if (!empty($neg_tag_array)) {
						$neg_event = new TagSetEvent($image, implode(' ',$neg_tag_array));
						$pos_event = new TagSetEvent($image, implode(' ',$pos_tag_array));
						$img_tags = array_merge($pos_event->tags, explode(" ",$image->get_tag_list()));
						$img_tags = array_diff($img_tags, $neg_event->tags);
						$event = new TagSetEvent($image, $img_tags);
						self::onTagSet($event);
					}
					else {
						$event = new TagSetEvent($image, $tag . " " . $image->get_tag_list());
						self::onTagSet($event);
					}
				}
			}

			$page->set_mode("redirect");
			if(!isset($_SERVER['HTTP_REFERER'])) $_SERVER['HTTP_REFERER'] = make_link();
			$page->set_redirect($_SERVER['HTTP_REFERER']);
		}
	}
	private function onTagSet(TagSetEvent $event) {
		$event->image->set_tags($event->tags);
	}
}

