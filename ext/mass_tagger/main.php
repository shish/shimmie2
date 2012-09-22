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
		
		if( !$user->is_admin() ) return;
		
		$this->theme->display_mass_tagger( $page, $event, $config );
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $config, $page, $user;
		if( !$event->page_matches("mass_tagger") ) return;
		if( !$user->is_admin() ) return;
		
		if($event->get_arg(0) == "tag") $this->_apply_mass_tags( $config, $page, $user, $event );
	}
	
	private function _apply_mass_tags( $config, Page $page, $user, $event ) {
		if( !isset($_POST['ids']) or !isset($_POST['tag']) ) return;
		
		$tag = $_POST['tag'];
		$ids = explode( ':', $_POST['ids'] );
		$ids = array_filter ( $ids , 'is_numeric' );
		
		$images = array_map( "Image::by_id", $ids );
		
		if(isset($_POST['setadd']) &&
		$_POST['setadd'] == 'set')
		{
			foreach($images as $image) {
				$image->set_tags($tag);
			}
		}
		else
		{
			foreach($images as $image) {
				$image->set_tags($tag . " " . $image->get_tag_list());
			}
		}
		
		$page->set_mode("redirect");
		$page->set_redirect(make_link("post/list"));
	}
}
?>
