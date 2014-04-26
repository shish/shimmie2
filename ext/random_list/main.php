<?php
/*
 * Name: Random List
 * Author: Drudex Software <support@drudexsoftware.com>
 * Link: http://www.drudexsoftware.com
 * License: GPLv2
 * Description: Allows displaying a page with random images
 * Documentation: 
 * Random image list can be accessed through www.yoursite.com/random
 * It is recommended that you create a link to this page so users know it exists.
 */

class RandomList extends Extension {
	public function onPageRequest(PageRequestEvent $event) {
		global $config, $page;

		if($event->page_matches("random")) {
			// set vars
			$page->title = "Random Images";
			$images_per_page = $config->get_int("random_images_list_count", 12);
			$random_images = array();
			$random_html = "<b>Refresh the page to view more images</b>
			<div class='shm-image-list'>";

			// generate random images
			for ($i = 0; $i < $images_per_page; $i++)
				array_push($random_images, Image::by_random());

			// create html to display images
			foreach ($random_images as $image)
				$random_html .= $this->theme->build_thumb_html($image);

			// display it
			$random_html .= "</div>";
			$page->add_block(new Block("Random Images", $random_html));
		}
	}

	public function onInitExt(InitExtEvent $event) {
		global $config;
		$config->set_default_int("random_images_list_count", 12);
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Random Images List");

		// custom headers
		$sb->add_int_option("random_images_list_count", 
		"Amount of Random images to display ");

		$event->panel->add_block($sb);
	}
}

