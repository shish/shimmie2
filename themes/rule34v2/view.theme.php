<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{TR, TH, TD, emptyHTML, rawHTML, joinHTML, DIV, TABLE, INPUT, A};

class CustomViewPostTheme extends ViewPostTheme
{
    // override to make info box always in edit mode
    protected function build_info(Image $image, $editor_parts): HTMLElement
    {
        global $user;

        if (count($editor_parts) == 0) {
            return emptyHTML($image->is_locked() ? "[Post Locked]" : "");
        }

        if(
            (!$image->is_locked() || $user->can(Permissions::EDIT_IMAGE_LOCK)) &&
            $user->can(Permissions::EDIT_IMAGE_TAG)
        ) {
            $editor_parts[] = TR(TD(["colspan" => 4], INPUT(["type" => "submit", "value" => "Set"])));
        }

        return SHM_SIMPLE_FORM(
            "post/set",
            INPUT(["type" => "hidden", "name" => "image_id", "value" => $image->id]),
            TABLE(
                [
                    "class" => "image_info form",
                    "style" => "width: 500px; max-width: 100%;"
                ],
                ...$editor_parts,
            ),
        );
    }
}
