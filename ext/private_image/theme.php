<?php declare(strict_types=1);
use function MicroHTML\INPUT;

class PrivateImageTheme extends Themelet
{
    public function get_image_admin_html(Image $image): string
    {
        if ($image->private===false) {
            $html = SHM_SIMPLE_FORM(
                'privatize_image/'.$image->id,
                INPUT(["type"=>'hidden', "name"=>'image_id', "value"=>$image->id]),
                SHM_SUBMIT("Make Private")
            );
        } else {
            $html = SHM_SIMPLE_FORM(
                'publicize_image/'.$image->id,
                INPUT(["type"=>'hidden', "name"=>'image_id', "value"=>$image->id]),
                SHM_SUBMIT("Make Public")
            );
        }

        return (string)$html;
    }

    public function get_help_html(): string
    {
        return '<p>Search for posts that are private/public.</p>
        <div class="command_example">
        <pre>private:yes</pre>
        <p>Returns posts that are private, restricted to yourself if you are not an admin.</p>
        </div>
        <div class="command_example">
        <pre>private:no</pre>
        <p>Returns posts that are public.</p>
        </div>
        ';
    }
}
