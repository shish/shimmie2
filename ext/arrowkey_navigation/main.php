<?php
/**
 * Name: Arrow Key Navigation
 * Author: Drudex Software <support@drudexsoftware.com>
 * Link: http://drudexsoftware.com
 * License: GPLv2
 * Description: Allows viewers no navigate between images using the left & right arrow keys.
 * Documentation:
 *  Simply enable this extention in the extention manager to enable arrow key navigation.
 */
class arrowkey_navigation extends Extension {   
    public function onDisplayingImage(DisplayingImageEvent $event) {
        global $page;

        $prev_url = "http://".$_SERVER['HTTP_HOST']."/post/prev/".$event->image->id;
        $next_url = "http://".$_SERVER['HTTP_HOST']."/post/next/".$event->image->id;
        
        $page->add_html_header("<script type=\"text/javascript\">
            document.onkeyup=checkKeycode;
            function checkKeycode(e)
            {
                var keycode;
                if(window.event) keycode=window.event.keyCode;
                else if(e) keycode=e.which;

                if (e.srcElement.tagName != \"INPUT\")
                {
                    if(keycode==\"37\") window.location.href='$prev_url';
                    else if(keycode==\"39\") window.location.href='$next_url';
                }
            }
            </script>");
    }  
}
?>
