<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{P, emptyHTML};

class TrashTheme extends Themelet
{
    public function get_help_html(): HTMLElement
    {
        return emptyHTML(
            P("Search for posts in the trash."),
            SHM_COMMAND_EXAMPLE("in:trash", "Returns posts that are in the trash.")
        );
    }
}
