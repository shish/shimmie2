<?php
/**
 * Name: Arrow Key Navigation
 * Author: Drudex Software <support@drudexsoftware.com>
 * Link: http://www.drudexsoftware.com/
 * License: GPLv2
 * Description: Allows viewers no navigate between images using the left & right arrow keys.
 * Documentation:
 *  Simply enable this extention in the extention manager to enable arrow key navigation.
 */
class ArrowKeyNavigation extends Extension {
	// TODO: Only open new page if a textbox or something isn't selected
	// TODO: Fix image next/prev (not using actual image ID's)
	
    # Adds functionality for post/list
    public function onPageRequest(PageRequestEvent $event) {
    	
        if ($event->page_matches("post/view")) {
            $pageinfo = $this->get_view_pageinfo($event);
            $prev_url = make_http(make_link("post/prev/".$pageinfo));
            $next_url = make_http(make_link("post/next/".$pageinfo));
            $this->add_arrowkeys_code($prev_url, $next_url);
        }
        
        else if ($event->page_matches("post/list") ||
			$event->page_matches("")) {
            $pageinfo = $this->get_list_pageinfo($event);
            $this->add_arrowkeys_code($pageinfo["prev"], $pageinfo["next"]);
        }
        
        // for random_list extension
        else if ($event->page_matches("random")) {
            $randomurl = make_http(make_link("random"));
            $this->add_arrowkeys_code($randomurl, $randomurl);
        }
    }
    
    # adds the javascript to the page with the given urls
    private function add_arrowkeys_code($prev_url, $next_url) {
        global $page;

        $page->add_html_header("<script type=\"text/javascript\">
            document.onkeyup=checkKeycode;
            function checkKeycode(e)
            {
                var keycode;
                if(window.event) keycode=window.event.keyCode;
                else if(e) keycode=e.which;

                if (e.srcElement.tagName != \"INPUT\")
                {
                    if(keycode==\"37\") window.location.href='$prev_url' + window.location.hash;
                    else if(keycode==\"39\") window.location.href='$next_url' + window.location.hash;
                }
            }
            </script>");
    }
    
    # returns info about the current page number
    private function get_list_pageinfo($event) {
        global $config, $database;

        // get the amount of images per page
        $images_per_page = $config->get_int('index_images');
        $prefix = "post/list/";
        
        // this occurs when viewing post/list without page number
        if ($event->args[0] == "") {// no page listed
            $page_number = 1;
            $total_pages = floor($database->get_one(
                "SELECT COUNT(*) FROM images") / $images_per_page);
        }
        
        // if there are no tags, use default
        else if ($event->args[2] == "" || (int)$event->args[2] != 0) {
            if ($event->args[2] == "") $page_number = 1;
            else $page_number = (int)$event->args[2];
            
            $total_pages = ceil($database->get_one(
                "SELECT COUNT(*) FROM images") / $images_per_page);
        }
        
        else { // if there are tags, use pages with tags
            $prefix .= $event->args[2]."/";

            if ($event->args[3] == "") $page_number = 1;
            else $page_number = (int)$event->args[3];
            
            $total_pages = ceil($database->get_one(
                "SELECT count FROM tags WHERE tag=:tag", 
                    array("tag"=>$event->args[2])) / $images_per_page);
        }
        
        // creates previous & next values     
        // When previous first page, go to last page
        if ($page_number <= 1) $prev = $total_pages;
        else $prev = $page_number-1;
        if ($page_number >= $total_pages) $next = 1;
        else $next = $page_number+1;
        
        // Create return array
        $pageinfo = array(
            "prev" => make_http(make_link($prefix.$prev)),
            "next" => make_http(make_link($prefix.$next)),
        );
        
        return $pageinfo;
    }
    
    # returns url ext with any tags
    private function get_view_pageinfo($event) {
        // if there are no tags, use default
        if ($event->args(1) == ""){
            $prefix = ""; 
            $image_id = (int)$event->get_arg(0);        
        }
        
        else { // if there are tags, use pages with tags
            $prefix = $event->get_arg(0)."/";
            $image_id = (int)$event->get_arg(1);
        }
        
        // returns result
        return $prefix.$image_id;
    }
    
}
?>
