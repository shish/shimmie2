<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\INPUT;

class RotateImageTheme extends Themelet
{
    /**
     * Display a link to rotate an image.
     */
    public function get_rotate_html(int $image_id): string
    {
        return (string)SHM_SIMPLE_FORM(
            'rotate/'.$image_id,
            INPUT(["type"=>'hidden', "name"=>'image_id', "value"=>$image_id]),
            INPUT(["type"=>'number', "name"=>'rotate_deg', "id"=>"rotate_deg", "placeholder"=>"Rotation degrees"]),
            INPUT(["type"=>'submit', "value"=>'Rotate', "id"=>"rotatebutton"]),
        );
    }

    /**
     * Display the error.
     */
    public function display_rotate_error(Page $page, string $title, string $message)
    {
        $page->set_title("Rotate Image");
        $page->set_heading("Rotate Image");
        $page->add_block(new NavBlock());
        $page->add_block(new Block($title, $message));
    }
}
