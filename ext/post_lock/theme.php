<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{INPUT};

class PostLockTheme extends Themelet
{
    public function get_lock_editor_html(Image $image): HTMLElement
    {
        global $user;
        return SHM_POST_INFO(
            "Locked",
            $image->is_locked() ? "Yes (Only admins may edit these details)" : "No",
            $user->can(Permissions::EDIT_IMAGE_LOCK) ? INPUT(["type" => "checkbox", "name" => "locked", "checked" => $image->is_locked()]) : null
        );
    }
}
