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
			if(isset($_GET['search'])) {
				// implode(explode()) to resolve aliases and sanitise
				$search = url_escape(Tag::implode(Tag::explode($_GET['search'], false)));
				if(empty($search)) {
					$page->set_mode("redirect");
					$page->set_redirect(make_link("random"));
				}
				else {
					$page->set_mode("redirect");
					$page->set_redirect(make_link('random/'.$search));
				}
				return;
			}

			if($event->count_args() == 0) {
				$search_terms = array();
			}
			else if($event->count_args() == 1) {
				$search_terms = explode(' ', $event->get_arg(0));
			}
			else {
				throw new SCoreException("Error: too many arguments.");
			}

			// set vars
			$images_per_page = $config->get_int("random_images_list_count", 12);
			$random_images = array();

			// generate random images
			for ($i = 0; $i < $images_per_page; $i++)
				array_push($random_images, Image::by_random($search_terms));

			$this->theme->set_page($search_terms);
			$this->theme->display_page($page, $random_images);
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

