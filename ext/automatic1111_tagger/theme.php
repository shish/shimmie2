<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{DIV, INPUT, BUTTON};

class Automatic1111TaggerTheme extends Themelet
{
    public function get_interrogate_button(int $post_id): string
    {
        return BUTTON([
            "type" => "button",
            "onclick" => "window.location='" . make_link("automatic1111_tagger/interrogate/" . $post_id) . "'"
        ], "Interrogate");
    }
}
