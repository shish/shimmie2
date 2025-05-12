<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{TEXTAREA, rawHTML};

use MicroHTML\HTMLElement;

class ImageDescriptionTheme extends Themelet
{
    public function get_description_editor_html(Image $image): HTMLElement
        {
        $raw_description = "Here is [i]sample[/i] description with [b]formatting[/b].";
        $tfe = send_event(new TextFormattingEvent($raw_description));

        return SHM_POST_INFO(
            "Description",
            rawHTML($tfe->formatted),
            true // TODO : Add permisions check
            ? TEXTAREA([
                "type" => "text",
                "name" => "description",
                "id" => "description_editor",
                ], $raw_description)
            : null
        );
    }
}