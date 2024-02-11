<?php

declare(strict_types=1);

namespace Shimmie2;

class RemoveReportedImageEvent extends Event
{
    public int $id;

    public function __construct(int $id)
    {
        parent::__construct();
        $this->id = $id;
    }
}

class AddReportedImageEvent extends Event
{
    public ImageReport $report;

    public function __construct(ImageReport $report)
    {
        parent::__construct();
        $this->report = $report;
    }
}

class ImageReport
{
    public int $user_id;
    public int $image_id;
    public string $reason;

    public function __construct(int $image_id, int $user_id, string $reason)
    {
        $this->image_id = $image_id;
        $this->user_id = $user_id;
        $this->reason = $reason;
    }
}

/**
 * @phpstan-type Report array{id: int, image: Image, reason: string, reporter_name: string}
 */
class ReportImage extends Extension
{
    /** @var ReportImageTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user;
        if ($event->page_matches("image_report/add")) {
            $image_id = int_escape($event->req_POST('image_id'));
            send_event(new AddReportedImageEvent(new ImageReport($image_id, $user->id, $event->req_POST('reason'))));
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/$image_id"));
        }
        if ($event->page_matches("image_report/remove", method: "POST", permission: Permissions::VIEW_IMAGE_REPORT)) {
            send_event(new RemoveReportedImageEvent(int_escape($event->req_POST('id'))));
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("image_report/list"));
        }
        if ($event->page_matches("image_report/remove_reports_by", method: "POST", permission: Permissions::VIEW_IMAGE_REPORT)) {
            $this->delete_reports_by(int_escape($event->req_POST('user_id')));
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link());
        }
        if ($event->page_matches("image_report/list", permission: Permissions::VIEW_IMAGE_REPORT)) {
            $this->theme->display_reported_images($page, $this->get_reported_images());
        }
    }

    public function onAddReportedImage(AddReportedImageEvent $event): void
    {
        global $cache, $database;
        log_info("report_image", "Adding report of >>{$event->report->image_id} with reason '{$event->report->reason}'");
        $database->execute(
            "INSERT INTO image_reports(image_id, reporter_id, reason)
				VALUES (:image_id, :reporter_id, :reason)",
            ['image_id' => $event->report->image_id, 'reporter_id' => $event->report->user_id, 'reason' => $event->report->reason]
        );
        $cache->delete("image-report-count");
    }

    public function onRemoveReportedImage(RemoveReportedImageEvent $event): void
    {
        global $cache, $database;
        $database->execute("DELETE FROM image_reports WHERE id = :id", ["id" => $event->id]);
        $cache->delete("image-report-count");
    }

    public function onUserPageBuilding(UserPageBuildingEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::VIEW_IMAGE_REPORT)) {
            $this->theme->get_nuller($event->display_user);
        }
    }

    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::CREATE_IMAGE_REPORT)) {
            $reps = $this->get_reports($event->image);
            $this->theme->display_image_banner($event->image, $reps);
        }
    }


    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        global $user;
        if ($event->parent === "system") {
            if ($user->can(Permissions::VIEW_IMAGE_REPORT)) {
                $count = $this->count_reported_images();
                $h_count = $count > 0 ? " ($count)" : "";

                $event->add_nav_link("image_report", new Link('image_report/list'), "Reported Posts$h_count");
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::VIEW_IMAGE_REPORT)) {
            $count = $this->count_reported_images();
            $h_count = $count > 0 ? " ($count)" : "";
            $event->add_link("Reported Posts$h_count", make_link("image_report/list"));
        }
    }

    public function onImageDeletion(ImageDeletionEvent $event): void
    {
        global $cache, $database;
        $database->execute("DELETE FROM image_reports WHERE image_id = :image_id", ["image_id" => $event->image->id]);
        $cache->delete("image-report-count");
    }

    public function onUserDeletion(UserDeletionEvent $event): void
    {
        $this->delete_reports_by($event->id);
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Post Reports");

        $opts = [
            "Reporter Only" => "user",
            "Reason Only" => "reason",
            "Both" => "both",
            "None" => "none",
        ];
        $sb->add_choice_option("report_image_publicity", $opts, "Show publicly: ");
    }

    public function delete_reports_by(int $user_id): void
    {
        global $cache, $database;
        $database->execute("DELETE FROM image_reports WHERE reporter_id=:reporter_id", ['reporter_id' => $user_id]);
        $cache->delete("image-report-count");
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        if ($this->get_version("ext_report_image_version") < 1) {
            $database->create_table("image_reports", "
				id SCORE_AIPK,
				image_id INTEGER NOT NULL,
				reporter_id INTEGER NOT NULL,
				reason TEXT NOT NULL,
				FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
				FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE
			");
            $this->set_version("ext_report_image_version", 1);
        }
    }

    /**
     * @return ImageReport[]
     */
    public function get_reports(Image $image): array
    {
        global $database;

        $rows = $database->get_all("
			SELECT *
			FROM image_reports
			WHERE image_reports.image_id = :image_id
		", ["image_id" => $image->id]);
        $reps = [];
        foreach ($rows as $row) {
            $reps[] = new ImageReport($row["image_id"], $row["reporter_id"], $row["reason"]);
        }
        return $reps;
    }

    /**
     * @return array<Report>
     */
    public function get_reported_images(): array
    {
        global $database;

        $all_reports = $database->get_all("
			SELECT image_reports.*, users.name AS reporter_name
			FROM image_reports
			JOIN users ON reporter_id = users.id
			ORDER BY image_reports.id DESC");
        if (is_null($all_reports)) {
            $all_reports = [];
        }

        $reports = [];
        foreach ($all_reports as $report) {
            $image_id = (int)$report['image_id'];
            $image = Image::by_id($image_id);
            if (is_null($image)) {
                send_event(new RemoveReportedImageEvent((int)$report['id']));
                continue;
            }
            $report['image'] = $image;
            $reports[] = $report;
        }

        return $reports;
    }

    public function count_reported_images(): int
    {
        global $database;

        return (int)cache_get_or_set(
            "image-report-count",
            fn () => $database->get_one("SELECT count(*) FROM image_reports"),
            600
        );
    }
}
