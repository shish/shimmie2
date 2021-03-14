<?php declare(strict_types=1);
use function MicroHTML\BR;
use function MicroHTML\BUTTON;
use function MicroHTML\INPUT;

class ApprovalTheme extends Themelet
{
    public function get_image_admin_html(Image $image): string
    {
        if ($image->approved===true) {
            $html = SHM_SIMPLE_FORM(
                'disapprove_image/'.$image->id,
                INPUT(["type"=>'hidden', "name"=>'image_id', "value"=>$image->id]),
                SHM_SUBMIT("Disapprove")
            );
        } else {
            $html = SHM_SIMPLE_FORM(
                'approve_image/'.$image->id,
                INPUT(["type"=>'hidden', "name"=>'image_id', "value"=>$image->id]),
                SHM_SUBMIT("Approve")
            );
        }

        return (string)$html;
    }

    public function get_help_html(): string
    {
        return '<p>Search for posts that are approved/not approved.</p>
        <div class="command_example">
        <pre>approved:yes</pre>
        <p>Returns posts that have been approved.</p>
        </div>
        <div class="command_example">
        <pre>approved:no</pre>
        <p>Returns posts that have not been approved.</p>
        </div>
        ';
    }

    public function display_admin_block(SetupBuildingEvent $event)
    {
        $sb = $event->panel->create_new_block("Approval");
        $sb->add_bool_option(ApprovalConfig::IMAGES, "Posts: ");
    }

    public function display_admin_form()
    {
        global $page;

        $html = (string)SHM_SIMPLE_FORM(
            "admin/approval",
            BUTTON(["name"=>'approval_action', "value"=>'approve_all'], "Approve All Posts"),
            BR(),
            BUTTON(["name"=>'approval_action', "value"=>'disapprove_all'], "Disapprove All Posts"),
        );
        $page->add_block(new Block("Approval", $html));
    }
}
