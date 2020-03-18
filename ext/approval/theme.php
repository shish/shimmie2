<?php declare(strict_types=1);
use function MicroHTML\BR;
use function MicroHTML\BUTTON;
use function MicroHTML\INPUT;

class ApprovalTheme extends Themelet
{
    public function get_image_admin_html(Image $image)
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


    public function get_help_html()
    {
        return '<p>Search for images that are approved/not approved.</p>
        <div class="command_example">
        <pre>approved:yes</pre>
        <p>Returns images that have been approved.</p>
        </div>
        <div class="command_example">
        <pre>approved:no</pre>
        <p>Returns images that have not been approved.</p>
        </div>
        ';
    }

    public function display_admin_block(SetupBuildingEvent $event)
    {
        $sb = new SetupBlock("Approval");
        $sb->add_bool_option(ApprovalConfig::IMAGES, "Images: ");
        $event->panel->add_block($sb);
    }

    public function display_admin_form()
    {
        global $page;

        $html = (string)SHM_SIMPLE_FORM(
            "admin/approval",
            BUTTON(["name"=>'approval_action', "value"=>'approve_all'], "Approve All Images"),
            BR(),
            BUTTON(["name"=>'approval_action', "value"=>'disapprove_all'], "Disapprove All Images"),
        );
        $page->add_block(new Block("Approval", $html));
    }
}
