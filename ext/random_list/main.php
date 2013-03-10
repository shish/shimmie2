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
                        $random_html .= $this->build_random_html($image);
                    
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
        
        private function build_random_html(Image $image, $query=null) {
		$i_id = int_escape($image->id);
		$h_view_link = make_link("post/view/$i_id", $query);
		$h_thumb_link = $image->get_thumb_link();
		$h_tip = html_escape($image->get_tooltip());
		$tsize = get_thumbnail_size($image->width, $image->height);

		return "
                    <a href='$h_view_link' class='thumb shm-thumb' data-post-id='$i_id'>
                    <img id='thumb_$i_id' height='{$tsize[1]}' width='{$tsize[0]}' class='lazy' data-original='$h_thumb_link' src='/lib/static/grey.gif'><noscript>
                    <img id='thumb_$i_id' height='{$tsize[1]} width='{$tsize[0]} src='$h_thumb_link'></noscript></a>
		";
	}
}
?>
