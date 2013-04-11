<?php
/*
 * Name: Bulk Remove
 * Author: Drudex Software <support@drudexsoftware.com>
 * Link: http://www.drudexsoftware.com/
 * License: GPLv2
 * Description: Allows admin to delete many images at once through Board Admin.
 * Documentation:
 *  
 */
//todo: removal by tag returns 1 less image in test for some reason, actually a combined search doesn't seem to work for shit either

class bulk_remove extends Extension {
	public function onPageRequest(PageRequestEvent $event) {
		global $page, $user;
		if($event->page_matches("bulk_remove") && $user->is_admin() && $user->check_auth_token()) {
                    if ($event->get_arg(0) == "confirm") $this->do_bulk_remove(); 
                    else $this->show_confirm();
		}
	}
 
	public function onAdminBuilding(AdminBuildingEvent $event) {
		global $page, $user;
		$html = "<b>Be extremely careful when using this!</b><br>
                    Once an image is removed there is no way to recover it so it is recommended that
                    you first take when removing a large amount of images.<br>
                    <b>Note:</b> Entering both an ID range and tags will only remove images between the given ID's that have the given tags.

			<p>".make_form(make_link("bulk_remove"))."
                            <table class='form'>
                                <tr><td colspan='2'><b>Remove images by ID</b></td></tr>
                                <tr><th>From</th><td> <input type='text' name='remove_id_min' size='2'></td></tr>
                                <tr><th>Until</th><td> <input type='text' name='remove_id_max' size='2'></td></tr>

                                <tr><td colspan='2'><b>Where tags are</b></td></tr>
                                <tr><td colspan='2'>
                                <input type='text' name='remove_tags' size='10'>
                               </td> </tr>
                                <tr><td colspan='2'><input type='submit' value='Remove'></td></tr>
                            </table>
			</form>
		";
		$page->add_block(new Block("Bulk Remove", $html));
	}

        // returns a list of images to be removed
	private function determine_images()
        {
            // set vars
            $images_for_removal = array();
            $error = "";
            
            $min_id = $_POST['remove_id_min'];
            $max_id = $_POST['remove_id_max'];
            $tags = $_POST['remove_tags'];
            
            
            // if using id range to remove (comined removal with tags)
            if ($min_id != "" && $max_id != "") 
            { 
                // error if values are not correctly entered
                if (!is_numeric($min_id) || !is_numeric($max_id) || 
                intval($max_id) < intval($min_id))
                    $error = "Values not correctly entered for removal between id.";
                
                else { // if min & max id are valid
                    
                    // Grab the list of images & place it in the removing array
                    foreach (Image::find_images(intval($min_id), intval($max_id)) as $image)
                    array_push($images_for_removal, $image);
                }      
            }
          
            // refine previous results or create results from tags
            if ($tags != "")
            {
                $tags_arr = explode(" ", $_POST['remove_tags']);
                
                // Search all images with the specified tags & add to list
                foreach (Image::find_images(1, 2147483647, $tags_arr) as $image)
                    array_push($images_for_removal, $image);
            }
            
            
            // if no images were found with the given info
            if (count($images_for_removal) == 0 && $html == "")
                $error = "No images selected for removal";
            
            var_dump($tags_arr); 
            return array(
                "error" => $error, 
                "images_for_removal" => $images_for_removal);
        }
        
        // displays confirmation to admin before removal
        private function show_confirm()
        {
            global $page;
            
            // set vars
            $determined_imgs = $this->determine_images();
            $error = $determined_imgs["error"];
            $images_for_removal = $determined_imgs["images_for_removal"];
            
            // if there was an error in determine_images()
            if ($error != "") {
                $page->add_block(new Block("Cannot remove images", $error));
                return;
            }
            // generates the image array & places it in $_POST["bulk_remove_images"]
            $_POST["bulk_remove_images"] = $images_for_removal;
            
     var_dump($images_for_removal);
            
            // Display confirmation message 
            $html = make_form(make_link("bulk_remove")).
                    "Are you sure you want to PERMANENTLY remove ". 
                    count($images_for_removal) ." images?<br></form>";
            $page->add_block(new Block("Confirm Removal", $html));
        }
        
        private function do_bulk_remove()
        {
            // display error if user didn't go through admin board
            if (!isset($_POST["bulk_remove_images"])) {
                $page->add_block(new Block("Bulk Remove Error", 
                    "Please use Board Admin to use bulk remove."));
            }
            
            //
            $image_arr = $_POST["bulk_remove_images"];
        }
}
?>
