<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\emptyHTML;

use function MicroHTML\{BUTTON,INPUT,P};

class ApprovalTheme extends Themelet
{
    public function get_image_admin_html(Image $image): HTMLElement
    {
        if ($image->approved === true) {
            $form = SHM_SIMPLE_FORM(
                'disapprove_image/'.$image->id,
                INPUT(["type" => 'hidden', "name" => 'image_id', "value" => $image->id]),
                SHM_SUBMIT("Disapprove")
            );
        } else {
            $form = SHM_SIMPLE_FORM(
                'approve_image/'.$image->id,
                INPUT(["type" => 'hidden', "name" => 'image_id', "value" => $image->id]),
                SHM_SUBMIT("Approve")
            );
        }

        return $form;
    }

    public function get_help_html(): HTMLElement
    {
        return emptyHTML(
            P("Search for posts that are approved/not approved."),
            SHM_COMMAND_EXAMPLE("approved:yes", "Returns posts that have been approved."),
            SHM_COMMAND_EXAMPLE("approved:no", "Returns posts that have not been approved.")
        );
    }

    public function display_admin_block(SetupBuildingEvent $event)
    {
        $sb = $event->panel->create_new_block("Approval");
        $sb->add_bool_option(ApprovalConfig::IMAGES, "Posts: ");
    }

    public function display_admin_form()
    {
        global $page;

        $form = SHM_SIMPLE_FORM(
            "admin/approval",
            BUTTON(["name" => 'approval_action', "value" => 'approve_all'], "Approve All Posts"),
            " ",
            BUTTON(["name" => 'approval_action', "value" => 'disapprove_all'], "Disapprove All Posts"),
        );

        $page->add_block(new Block("Approval", $form));
    }
}
