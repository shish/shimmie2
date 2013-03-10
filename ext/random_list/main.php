<?php
/*
 * Name: Random List
 * Author: Drudex Software <support@drudexsoftware.com>
 * Link: http://www.drudexsoftware.com
 * License: GPLv2
 * Description: Allows displaying a page with random images
 * Documentation:
 */

class RandomList extends Extension {
	public function onPageRequest(PageRequestEvent $event) {
            global $page;

            if($event->page_matches("random")) {
                $html = "<b>Refresh the page to view more images</b>
                    <div class='shm-image-list'>";

                // create html to display images
                $html .= $this->build_random_html();

                // display it
                $html .= "</div>";
                $page->add_block(new Block("Random Images", $html));
            }
	}
        
        private function build_random_html() {
            global $config;
            
            $random_html = "";
            $images_per_page = $config->get_int("index_images");
            $random_images = $this->by_random(array(), $images_per_page);
            
            var_dump($images_per_page);
            var_dump($random_images);
            
            foreach ($random_images as $image) {
		$i_id = int_escape($image->id);
		$h_view_link = make_link("post/view/$i_id");
		$h_thumb_link = $image->get_thumb_link();
		$h_tip = html_escape($image->get_tooltip());
		$tsize = get_thumbnail_size($image->width, $image->height);
                
                $random_html .= "
                    <a href='$h_view_link' class='thumb shm-thumb' data-post-id='$i_id'>
                    <img id='thumb_$i_id' height='{$tsize[1]}' width='{$tsize[0]}' class='lazy' data-original='$h_thumb_link' src='/lib/static/grey.gif'><noscript>
                    <img id='thumb_$i_id' height='{$tsize[1]} width='{$tsize[0]} src='$h_thumb_link'></noscript></a>
		";
            }
            
            return $random_html;
	}
        
        /**
	 * Pick certain amount of random images random image out of a set
	 *
	 * @retval Image
	 */
	private function by_random($tags=array(), $amount=1) {
		assert(is_array($tags));
		$max = Image::count_images($tags);
		if ($max < 1) return null;		// From Issue #22 - opened by HungryFeline on May 30, 2011.
		$rand = mt_rand(0, $max-1);
		$set = Image::find_images($rand, $amount, $tags);
		if(count($set) > 0)
                {
                    if ($amount == 1) return $set[0]; // return as single image
                    else if ($amount > 1) return $set; // return as array
                    else return null;
                }
		else return null;
	}
}
?>
