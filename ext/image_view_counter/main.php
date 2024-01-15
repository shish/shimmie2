<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{TD,TH,TR};

class ImageViewCounter extends Extension
{
    /** @var ImageViewCounterTheme */
    protected Themelet $theme;
    private int $view_interval = 3600; # allows views to be added each hour

    # Add Setup Block with options for view counter
    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Post View Counter");
        $sb->add_bool_option("image_viewcounter_adminonly", "Display view counter only to admin");
    }

    # Adds view to database if needed
    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        global $database, $user;

        $imgid = $event->image->id;

        // counts views from current IP in the last hour
        $recent_from_ip = (int)$database->get_one(
            "
				SELECT COUNT(*)
				FROM image_views
				WHERE ipaddress=:ipaddress AND timestamp >:lasthour AND image_id =:image_id
			",
            [
                "ipaddress" => get_real_ip(),
                "lasthour" => time() - $this->view_interval,
                "image_id" => $imgid
            ]
        );

        // don't add view if person already viewed recently
        if ($recent_from_ip > 0) {
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
                "ipaddress" => get_real_ip(),
            ]
        );
    }

    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event): void
    {
        global $user, $database;

        if ($user->can(Permissions::SEE_IMAGE_VIEW_COUNTS)) {
            $view_count = (string)$database->get_one(
                "SELECT COUNT(*) FROM image_views WHERE image_id =:image_id",
                ["image_id" => $event->image->id]
            );

            $event->add_part(SHM_POST_INFO("Views", $view_count), 38);
        }
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database, $config;

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

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $database;

        if ($event->page_matches("popular_images")) {
            $popular_ids = $database->get_col("
                SELECT image_id, count(*) AS total_views
                FROM image_views, images
                WHERE image_views.image_id = image_views.image_id
                AND image_views.image_id = images.id
                GROUP BY image_views.image_id
                ORDER BY total_views DESC
                LIMIT 100
            ");
            $images = Search::get_images($popular_ids);
            $this->theme->view_popular($images);
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent == "posts") {
            $event->add_nav_link("sort_by_visits", new Link('popular_images'), "Popular Posts");
        }
    }
}
