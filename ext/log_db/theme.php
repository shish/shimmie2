<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\emptyHTML;

class LogDatabaseTheme extends Themelet
{
    public function display_events(HTMLElement $table, HTMLElement $paginator): void
    {
        global $page;
        $page->set_title("Event Log");
        $page->set_heading("Event Log");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Events", emptyHTML($table, $paginator)));
    }
}
