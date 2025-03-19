<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{INPUT};
use function MicroHTML\A;
use function MicroHTML\B;
use function MicroHTML\BR;
use function MicroHTML\P;
use function MicroHTML\TABLE;
use function MicroHTML\TBODY;
use function MicroHTML\TD;
use function MicroHTML\THEAD;
use function MicroHTML\TR;
use function MicroHTML\emptyHTML;
use function MicroHTML\joinHTML;

/**
 * @phpstan-type Report array{id: int, image: Image, reason: string, reporter_name: string}
 */
class ReportImageTheme extends Themelet
{
    /**
     * @param array<Report> $reports
     */
    public function display_reported_images(Page $page, array $reports): void
    {
        global $config, $user;

        $tbody = TBODY();
        foreach ($reports as $report) {
            $iabbe = send_event(new ImageAdminBlockBuildingEvent($report['image'], $user, "report"));

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
                    TD(["width" => $config->get_int(ThumbnailConfig::WIDTH)], "Post"),
                    TD("Reason"),
                    TD(["width" => "128"], "Action")
                )
            ),
            $tbody,
        );

        $page->set_title("Reported Posts");
        $this->display_navigation();
        $page->add_block(new Block("Reported Posts", $html));
    }

    /**
     * @param ImageReport[] $reports
     */
    public function display_image_banner(Image $image, array $reports): void
    {
        global $config, $page;

        $html = emptyHTML();
        $public = $config->get_string(ReportImageConfig::SHOW_INFO);
        if ($public !== "none" && count($reports) > 0) {
            $html->appendChild(P(B("Current reports:")));
            foreach ($reports as $report) {
                $html->appendChild(BR());
                if ($public == "both") {
                    $html->appendChild(User::by_id($report->user_id)->name);
                    $html->appendChild(" - ");
                    $html->appendChild(format_text($report->reason));
                } elseif ($public == "user") {
                    $html->appendChild(User::by_id($report->user_id)->name);
                } elseif ($public == "reason") {
                    $html->appendChild(format_text($report->reason));
                }
            }
        }
        $html->appendChild(SHM_SIMPLE_FORM(
            make_link("image_report/add"),
            INPUT(["type" => 'hidden', "name" => 'image_id', "value" => $image->id]),
            INPUT(["type" => 'text', "name" => 'reason', "placeholder" => 'Please enter a reason']),
            SHM_SUBMIT('Report')
        ));
        $page->add_block(new Block("Report Post", $html, "left"));
    }

    public function get_nuller(User $duser): void
    {
        global $page;
        $html = SHM_SIMPLE_FORM(
            make_link("image_report/remove_reports_by"),
            INPUT(["type" => 'hidden', "name" => 'user_id', "value" => $duser->id]),
            SHM_SUBMIT('Delete all reports by this user')
        );
        $page->add_block(new Block("Reports", $html, "main", 80));
    }
}
