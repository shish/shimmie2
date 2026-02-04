<?php

declare(strict_types=1);

namespace Shimmie2;

class HelpPagesTheme extends Themelet
{
    public function display_help_page(string $title): void
    {
        Ctx::$page->set_title("Help - $title");
    }
}
