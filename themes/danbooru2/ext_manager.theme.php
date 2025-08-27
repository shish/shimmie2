<?php

declare(strict_types=1);

namespace Shimmie2;

class Danbooru2ExtManagerTheme extends ExtManagerTheme
{
    public function display_table(array $extensions): void
    {
        Ctx::$page->set_layout("no-left");
        parent::display_table($extensions);
    }

    public function display_doc(ExtensionInfo $info): void
    {
        Ctx::$page->set_layout("no-left");
        parent::display_doc($info);
    }
}
