<?php

declare(strict_types=1);

namespace Shimmie2;

final class RemoveReportedImageEvent extends Event
{
    public function __construct(
        public int $id
    ) {
        parent::__construct();
    }
}

final class AddReportedImageEvent extends Event
{
    public function __construct(
        public ImageReport $report
    ) {
        parent::__construct();
    }
}

final class ImageReport
{
    public function __construct(
        public int $image_id,
        public int $user_id,
        public string $reason
    ) {
    }
}

/**
 * @phpstan-type Report array{id: int, image: Image, reason: string, reporter_name: string}
 * @extends Extension<ReportImageTheme>
 */
final class ReportImage extends Extension
{
    public const KEY = "report_image";

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("image_report/add")) {
            $reason = $event->POST->req('reason');
            if (trim($reason) === "") {
                throw new ReportPostingException("Reports need text...");
            } elseif (strlen($reason) > 300) {
                throw new ReportPostingException("Report too long~");
            }
            $image_id = int_escape($event->POST->req('image_id'));
            send_event(new AddReportedImageEvent(new ImageReport($image_id, Ctx::$user->id, $reason)));
            Ctx::$page->set_redirect(make_link("post/view/$image_id"));
        }
        if ($event->page_matches("image_report/remove", method: "POST", permission: ReportImagePermission::VIEW_IMAGE_REPORT)) {
            send_event(new RemoveReportedImageEvent(int_escape($event->POST->req('id'))));
            Ctx::$page->set_redirect(make_link("image_report/list"));
        }
        if ($event->page_matches("image_report/remove_reports_by", method: "POST", permission: ReportImagePermission::VIEW_IMAGE_REPORT)) {
            $this->delete_reports_by(int_escape($event->POST->req('user_id')));
            Ctx::$page->set_redirect(make_link());
        }
        if ($event->page_matches("image_report/list", permission: ReportImagePermission::VIEW_IMAGE_REPORT)) {
            $this->theme->display_reported_images($this->get_reported_images());
        }
    }

    #[EventListener]
    public function onAddReportedImage(AddReportedImageEvent $event): void
    {
        Log::info("report_image", "Adding report of >>{$event->report->image_id} with reason '{$event->report->reason}'");
        Ctx::$database->execute(
            "INSERT INTO image_reports(image_id, reporter_id, reason)
				VALUES (:image_id, :reporter_id, :reason)",
            ['image_id' => $event->report->image_id, 'reporter_id' => $event->report->user_id, 'reason' => $event->report->reason]
        );
        Ctx::$cache->delete("image-report-count");
    }

    #[EventListener]
    public function onRemoveReportedImage(RemoveReportedImageEvent $event): void
    {
        Ctx::$database->execute("DELETE FROM image_reports WHERE id = :id", ["id" => $event->id]);
        Ctx::$cache->delete("image-report-count");
    }

    #[EventListener]
    public function onUserPageBuilding(UserPageBuildingEvent $event): void
    {
        if (Ctx::$user->can(ReportImagePermission::VIEW_IMAGE_REPORT)) {
            $this->theme->get_nuller($event->display_user);
        }
    }

    #[EventListener]
    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        if (Ctx::$user->can(ReportImagePermission::CREATE_IMAGE_REPORT)) {
            $reps = $this->get_reports($event->image);
            $this->theme->display_image_banner($event->image, $reps);
        }
    }


    #[EventListener]
    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "system") {
            if (Ctx::$user->can(ReportImagePermission::VIEW_IMAGE_REPORT)) {
                $count = $this->count_reported_images();
                $h_count = $count > 0 ? " ($count)" : "";

                $event->add_nav_link(make_link('image_report/list'), "Reported Posts$h_count");
            }
        }
    }

    #[EventListener]
    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        if (Ctx::$user->can(ReportImagePermission::VIEW_IMAGE_REPORT)) {
            $count = $this->count_reported_images();
            $h_count = $count > 0 ? " ($count)" : "";
            $event->add_link("Reported Posts$h_count", make_link("image_report/list"));
        }
    }

    #[EventListener]
    public function onImageDeletion(ImageDeletionEvent $event): void
    {
        Ctx::$database->execute("DELETE FROM image_reports WHERE image_id = :image_id", ["image_id" => $event->image->id]);
        Ctx::$cache->delete("image-report-count");
    }

    #[EventListener]
    public function onUserDeletion(UserDeletionEvent $event): void
    {
        $this->delete_reports_by($event->id);
    }

    public function delete_reports_by(int $user_id): void
    {
        Ctx::$database->execute("DELETE FROM image_reports WHERE reporter_id=:reporter_id", ['reporter_id' => $user_id]);
        Ctx::$cache->delete("image-report-count");
    }

    #[EventListener]
    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        $database = Ctx::$database;

        if ($this->get_version() < 1) {
            $database->create_table("image_reports", "
				id SCORE_AIPK,
				image_id INTEGER NOT NULL,
				reporter_id INTEGER NOT NULL,
				reason TEXT NOT NULL,
				FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
				FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE
			");
            $this->set_version(1);
        }
    }

    /**
     * @return ImageReport[]
     */
    public function get_reports(Image $image): array
    {
        $rows = Ctx::$database->get_all("
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
        $all_reports = Ctx::$database->get_all("
			SELECT image_reports.*, users.name AS reporter_name
			FROM image_reports
			JOIN users ON reporter_id = users.id
			ORDER BY image_reports.id DESC");

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

        /** @var array<Report> $reports */
        return $reports;
    }

    public function count_reported_images(): int
    {
        return (int)cache_get_or_set(
            "image-report-count",
            fn () => Ctx::$database->get_one("SELECT count(*) FROM image_reports"),
            600
        );
    }
}
