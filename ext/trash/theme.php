<?php declare(strict_types=1);
use function MicroHTML\INPUT;

class TrashTheme extends Themelet
{
    public function get_image_admin_html(int $image_id): string
    {
        return (string)SHM_SIMPLE_FORM(
            'trash_restore/'.$image_id,
            INPUT(["type"=>'hidden', "name"=>'image_id', "value"=>$image_id]),
            INPUT(["type"=>'submit', "value"=>'Restore From Trash']),
        );
    }


    public function get_help_html(): string
    {
        return '<p>Search for posts in the trash.</p>
        <div class="command_example">
        <pre>in:trash</pre>
        <p>Returns posts that are in the trash.</p>
        </div>
        ';
    }
}
