<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, B, BR, P, TABLE, TBODY, TD, THEAD, TR, emptyHTML, joinHTML};
use function MicroHTML\{INPUT};

/**
 * @phpstan-type Report array{id: int, image: Image, reason: string, reporter_name: string}
 */
class ReportImageTheme extends Themelet
{
    /**
     * @param array<Report> $reports
     */
    public function display_reported_images(array $reports): void
    {
        $tbody = TBODY();
        foreach ($reports as $report) {
            $iabbe = send_event(new ImageAdminBlockBuildingEvent($report['image'], Ctx::$user, "report"));

            $tbody->appendChild(TR(
                TD($this->build_thumb($report['image'])),
                TD(
                    ["class" => "reason"],
                    "Report by ",
                    A(["href" => make_link("user/".$report['reporter_name'])], $report['reporter_name']),
                    ": ",
                    format_text($report['reason'])
                ),
                TD(
                    ["class" => "formstretch post_controls"],
                    SHM_SIMPLE_FORM(
                        make_link("image_report/remove"),
                        INPUT(["type" => "hidden", "name" => "id", "value" => $report['id']]),
                        SHM_SUBMIT("Remove Report")
                    ),
                    joinHTML("", $iabbe->get_parts())
                )
            ));
        }

        $html = TABLE(
            ["id" => "reportedImage", "class" => "zebra"],
            THEAD(
                TR(
                    TD(["width" => Ctx::$config->get(ThumbnailConfig::WIDTH)], "Post"),
                    TD("Reason"),
                    TD(["width" => "128"], "Action")
                )
            ),
            $tbody,
        );

        Ctx::$page->set_title("Reported Posts");
        $this->display_navigation();
        Ctx::$page->add_block(new Block("Reported Posts", $html));
    }

    /**
     * @param ImageReport[] $reports
     */
    public function display_image_banner(Image $image, array $reports): void
    {
        $html = emptyHTML();
        $public = Ctx::$config->get(ReportImageConfig::SHOW_INFO);
        if ($public !== "none" && count($reports) > 0) {
            $html->appendChild(P(B("Current reports:")));
            foreach ($reports as $report) {
                $html->appendChild(BR());
                if ($public === "both") {
                    $html->appendChild(User::by_id($report->user_id)->name);
                    $html->appendChild(" - ");
                    $html->appendChild(format_text($report->reason));
                } elseif ($public === "user") {
                    $html->appendChild(User::by_id($report->user_id)->name);
                } elseif ($public === "reason") {
                    $html->appendChild(format_text($report->reason));
                }
            }
        }
        $html->appendChild(SHM_SIMPLE_FORM(
            make_link("image_report/add"),
            INPUT(["type" => 'hidden', "name" => 'image_id', "value" => $image->id]),
            INPUT(["type" => 'text', "name" => 'reason', "placeholder" => 'Please enter a reason', "required" => ""]),
            SHM_SUBMIT('Report')
        ));
        Ctx::$page->add_block(new Block("Report Post", $html, "left"));
    }

    public function get_nuller(User $duser): void
    {
        $html = SHM_SIMPLE_FORM(
            make_link("image_report/remove_reports_by"),
            INPUT(["type" => 'hidden', "name" => 'user_id', "value" => $duser->id]),
            SHM_SUBMIT('Delete all reports by this user')
        );
        Ctx::$page->add_block(new Block("Reports", $html, "main", 80));
    }
}
