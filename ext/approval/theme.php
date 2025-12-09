<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{BUTTON,P};
use function MicroHTML\emptyHTML;

use MicroHTML\HTMLElement;

class ApprovalTheme extends Themelet
{
    public function get_help_html(): HTMLElement
    {
        $help_text = emptyHTML(
            P("Search for posts that are approved/not approved."),
            SHM_COMMAND_EXAMPLE("approved=yes", "Returns posts that have been approved."),
        );

        if (Ctx::$user->can(ApprovalPermission::APPROVE_IMAGE)) {
            $help_text = emptyHTML(
                $help_text,
                SHM_COMMAND_EXAMPLE("approved=no", "Returns posts that have not been approved.")
            );
        } else {
            $help_text = emptyHTML(
                $help_text,
                SHM_COMMAND_EXAMPLE("approved=no", "Returns your own posts that have not been approved."),
                SHM_COMMAND_EXAMPLE("approved=no user=username", "Returns your own unapproved posts (only works with your own username).")
            );
        }

        return $help_text;
    }

    public function display_admin_form(): void
    {
        $form = SHM_SIMPLE_FORM(
            make_link("admin/approval"),
            BUTTON(["name" => 'approval_action', "value" => 'approve_all'], "Approve All Posts"),
            " ",
            BUTTON(["name" => 'approval_action', "value" => 'disapprove_all'], "Disapprove All Posts"),
        );

        Ctx::$page->add_block(new Block("Approval", $form));
    }
}
