<?php
/**
 * Name: Image View Counter
 * Author: Drudex Software <support@drudexsoftware.com>
 * Link: http://drudexsoftware.com
 * License: GPLv2
 * Description: Tracks & displays how many times an image is viewed
 * Documentation:
 *  Whenever anyone views an image, a view will be added to that image.
 *  This extension will also track any username & the IP adress.
 *  This is done to prevent duplicate views. 
 *  A person can only count as a view again 1 hour after viewing the image initially.
 */
class image_view_counter extends Extension {    
        private $view_interval = 3600; # allows views to be added each hour
    
        # Add Setup Block with options for view counter
        public function onSetupBuilding(SetupBuildingEvent $event) {
            $sb = new SetupBlock("Image View Counter");
            $sb->add_bool_option("image_viewcounter_adminonly", "Display view counter only to admin");

            $event->panel->add_block($sb);
	}
        
        # Load Analytics tracking code on page request
        public function onDisplayingImage(DisplayingImageEvent $event) {
            $imgid = $event->image->id; // determines image id
            $this->addview($imgid); // adds a view
        }
        
        # Installs DB table
        public function onInitExt(InitExtEvent $event) {
            global $database, $config;
            
            // if the sql table doesn't exist yet, create it
            if($config->get_bool("image_viewcounter_installed") == false) {
                $database->execute("CREATE TABLE image_views (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                image_id int(11) NOT NULL,
                timestamp int(11) NOT NULL,
                ipaddress varchar(255) NOT NULL, 
                PRIMARY KEY (id))"); 
                $config->set_bool("image_viewcounter_installed", true);
            }
        }
        
        # Adds a view to the item if needed
        private function addview($imgid)
        {
            global $database;
            // don't add view if person already viewed recently
            if ($this->can_add_view($imgid) == false) return;
            
            // Add view for current IP
            $database->execute("INSERT INTO image_views (image_id, timestamp, ipaddress)
                VALUES (:image_id, :timestamp, :ipaddress)", array(
                    "image_id" => $imgid,
                    "timestamp" => time(),
                    "ipaddress" => $_SERVER['REMOTE_ADDR'],
                ));
        }
        
        # Returns true if this IP hasn't recently viewed this image
        private function can_add_view($imgid)
        {
            global $database;
            
            // counts views from current IP in the last hour
            $recent_from_ip = $database->get_row("SELECT COUNT(*) FROM image_views WHERE
                ipaddress=:ipaddress AND timestamp >:lasthour AND image_id =:image_id", 
                    array(  "ipaddress" => $_SERVER['REMOTE_ADDR'],
                            "lasthour" => time() - $this->view_interval,
                            "image_id" => $imgid));
            
            // if no views were found with the set criteria, return true
            if($recent_from_ip["COUNT(*)"] == "0") return true;
            else return false;
        }
        
        # Returns the int of the view count from the given image id
        // $imgid - if not set or 0, return views of all images
        private function get_view_count($imgid = 0)
        {
            global $database;
            
            if ($imgid == 0) // return view count of all images
                $view_count = $database->get_row("SELECT COUNT(*) FROM image_views");
            else // return view count of specified image
                $view_count = $database->get_row("SELECT COUNT(*) FROM image_views WHERE ".
                    "image_id =:image_id", array("image_id" => $imgid));
            
            // returns the count as int
            return intval($view_count["COUNT(*)"]);
        }
}
?>
