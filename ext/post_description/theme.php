<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{TEXTAREA, rawHTML};

class PostDescriptionTheme extends Themelet
{
    public function get_description_editor_html(string $raw_description): HTMLElement
    {
        $tfe = send_event(new TextFormattingEvent($raw_description));

        return SHM_POST_INFO(
            "Description",
            rawHTML($tfe->formatted),
            Ctx::$user->can(PostDescriptionPermission::EDIT_IMAGE_DESCRIPTIONS)
            ? TEXTAREA([
                "type" => "text",
                "name" => "description",
                "id" => "description_editor",
                ], $raw_description)
            : null
        );
    }
}
