<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{P, emptyHTML};

class PrivateImageTheme extends Themelet
{
    public function get_help_html(): HTMLElement
    {
        return emptyHTML(
            P("Search for posts that are private/public."),
            SHM_COMMAND_EXAMPLE("private:yes", "Returns posts that are private, restricted to yourself if you are not an admin."),
            SHM_COMMAND_EXAMPLE("private:no", "Returns posts that are public.")
        );
    }
}
