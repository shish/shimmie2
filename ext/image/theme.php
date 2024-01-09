<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\INPUT;

class ImageIOTheme extends Themelet
{
    /**
     * Display a link to delete an image
     * (Added inline Javascript to confirm the deletion)
     */
    public function get_deleter_html(int $image_id): string
    {
        return (string)"<span id='image_delete_form'>".SHM_SIMPLE_FORM(
            "image/delete",
            INPUT(["type" => 'hidden', "name" => 'image_id', "value" => $image_id]),
            INPUT(["type" => 'submit', "value" => 'Delete', "onclick" => 'return confirm("Delete the image?");', "id" => "image_delete_button"]),
        )."</span>";
    }
}
