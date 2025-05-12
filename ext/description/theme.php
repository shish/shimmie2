<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

class ImageDescriptionTheme extends Themelet
{
    public function get_description_editor_html(Image $image): HTMLElement
    {
        return SHM_POST_INFO(
            "Description",
            "Put description here",
            null
        );
    }
}