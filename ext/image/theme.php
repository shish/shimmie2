<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{INPUT, emptyHTML};

class ImageIOTheme extends Themelet
{
    /**
     * Display a link to delete an image
     * (Added inline Javascript to confirm the deletion)
     */
    public function get_deleter_html(int $image_id): \MicroHTML\HTMLElement
    {
        $form = SHM_FORM("image/delete", form_id: "image_delete_form");
        $form->appendChild(emptyHTML(
            INPUT(["type" => 'hidden', "name" => 'image_id', "value" => $image_id]),
            INPUT(["type" => 'submit', "value" => 'Delete', "onclick" => 'return confirm("Delete the image?");', "id" => "image_delete_button"]),
        ));
        return $form;
    }
}
