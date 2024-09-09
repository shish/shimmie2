<?php

declare(strict_types=1);

namespace Shimmie2;

class Danbooru2ExtManagerTheme extends ExtManagerTheme
{
    public function display_table(Page $page, array $extensions, bool $editable): void
    {
        $page->set_layout("no-left");
        parent::display_table($page, $extensions, $editable);
    }

    public function display_doc(Page $page, ExtensionInfo $info): void
    {
        $page->set_layout("no-left");
        parent::display_doc($page, $info);
    }
}
