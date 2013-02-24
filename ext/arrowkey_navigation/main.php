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
class arrowkey_navigation extends Extension {  
    # Adds functionality for post/list
    public function onPageRequest(PageRequestEvent $event) {
        if ($event->page_matches("post/view")) {
            $pageinfo = $this->get_view_pageinfo($event);
            $prev_url = make_http(make_link("post/prev/".$pageinfo["current"]));
            $next_url = make_http(make_link("post/next/".$pageinfo["current"]));
            $this->add_arrowkeys_code($prev_url, $next_url);
        }
        
        else if ($event->page_matches("post/list")) {
            $pageinfo = $this->get_list_pageinfo($event);
            $prev_url = make_http(make_link("post/list/".$pageinfo["prev"]));
            $next_url = make_http(make_link("post/list/".$pageinfo["next"]));
            $this->add_arrowkeys_code($prev_url, $next_url);
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
  
        // this occurs when viewing post/list without page number
        if ($event->get_arg(0) == null) {// no page listed
            $prefix = ""; 
            $page_number = 1;
            $total_pages = ceil($database->get_one(
                "SELECT COUNT(*) FROM images") / $images_per_page);
        }
        
        // if there are no tags, use default
        else if ($event->get_arg(1) == null){
            $prefix = ""; 
            $page_number = (int)$event->get_arg(0);
            $total_pages = ceil($database->get_one(
                "SELECT COUNT(*) FROM images") / $images_per_page);
        }
        
        else { // if there are tags, use pages with tags
            $prefix = $event->get_arg(0)."/";
            $page_number = (int)$event->get_arg(1);
            $total_pages = ceil($database->get_one(
                "SELECT count FROM tags WHERE tag=:tag", 
                    array("tag"=>$event->get_arg(0))) / $images_per_page);
        }
        
        // creates previous & next values     
        // When previous first page, go to last page
        if ($page_number <= 1) $prev = $total_pages;
        else $prev = $page_number-1;
        if ($page_number >= $total_pages) $next = 1;
        else $next = $page_number+1;
        
        // Create return array
        $pageinfo = array(
            "prev" => $prefix.$prev.$after,
            "next" => $prefix.$next.$after,
        );
        
        return $pageinfo;
    }
    
    # returns url ext with any tags
    private function get_view_pageinfo($event) {
        // if there are no tags, use default
        if ($event->get_arg(1) == null){
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
