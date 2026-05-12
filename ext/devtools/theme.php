<?php

declare(strict_types=1);

namespace Shimmie2;

class DevToolsTheme extends Themelet
{
    public function display_page(): void
    {
        Ctx::$page->set_title("DevTools");
        $this->display_navigation();
    }
}
