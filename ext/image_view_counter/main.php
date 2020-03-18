<?php declare(strict_types=1);

class ImageViewCounter extends Extension
{
    private $view_interval = 3600; # allows views to be added each hour

    # Add Setup Block with options for view counter
    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = new SetupBlock("Image View Counter");
        $sb->add_bool_option("image_viewcounter_adminonly", "Display view counter only to admin");

        $event->panel->add_block($sb);
    }

    # Adds view to database if needed
    public function onDisplayingImage(DisplayingImageEvent $event)
    {
        $imgid = $event->image->id; // determines image id
        $this->addview($imgid); // adds a view
    }

    # display views to user or admin below image if allowed
    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event)
    {
        global $user, $config;

        $adminonly = $config->get_bool("image_viewcounter_adminonly"); // todo
        if ($adminonly == false || ($adminonly && $user->can(Permissions::SEE_IMAGE_VIEW_COUNTS))) {
            $event->add_part(
                "<tr><th>Views:</th><td>".
                $this->get_view_count($event->image->id) .
                "</tr>",
                38
            );
        }
    }

    # Installs DB table
    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event)
    {
        global $database, $config;

        // if the sql table doesn't exist yet, create it
        if ($config->get_bool("image_viewcounter_installed") == false) { //todo
            $database->create_table("image_views", "
					id SCORE_AIPK,
					image_id INTEGER NOT NULL,
					user_id INTEGER NOT NULL,
					timestamp INTEGER NOT NULL,
					ipaddress SCORE_INET NOT NULL");
            $config->set_bool("image_viewcounter_installed", true);
        }
    }

    /**
     * Adds a view to the item if needed
     */
    private function addview(int $imgid)
    {
        global $database, $user;

        // don't add view if person already viewed recently
        if ($this->can_add_view($imgid) === false) {
            return;
        }

        // Add view for current IP
        $database->execute(
            "
				INSERT INTO image_views (image_id, user_id, timestamp, ipaddress)
				VALUES (:image_id, :user_id, :timestamp, :ipaddress)
			",
            [
                "image_id" => $imgid,
                "user_id" => $user->id,
                "timestamp" => time(),
                "ipaddress" => $_SERVER['REMOTE_ADDR'],
            ]
        );
    }

    /**
     * Returns true if this IP hasn't recently viewed this image
     */
    private function can_add_view(int $imgid)
    {
        global $database;

        // counts views from current IP in the last hour
        $recent_from_ip = (int)$database->get_one(
            "
				SELECT COUNT(*)
				FROM image_views
				WHERE ipaddress=:ipaddress AND timestamp >:lasthour AND image_id =:image_id
			",
            [
                "ipaddress" => $_SERVER['REMOTE_ADDR'],
                "lasthour" => time() - $this->view_interval,
                "image_id" => $imgid
            ]
        );

        // if no views were found with the set criteria, return true
        if ($recent_from_ip == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the int of the view count from the given image id
     */
    private function get_view_count(int $imgid = 0)
    {
        global $database;

        if ($imgid == 0) { // return view count of all images
            $view_count = (int)$database->get_one(
                "SELECT COUNT(*) FROM image_views"
            );
        } else { // return view count of specified image
            $view_count = (int)$database->get_one(
                "SELECT COUNT(*) FROM image_views WHERE image_id =:image_id",
                ["image_id" => $imgid]
            );
        }

        // returns the count as int
        return $view_count;
    }
}
