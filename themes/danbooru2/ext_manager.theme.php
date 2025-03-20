<?php

declare(strict_types=1);

namespace Shimmie2;

class Danbooru2ExtManagerTheme extends ExtManagerTheme
{
    public function display_table(array $extensions, bool $editable): void
    {
        Ctx::$page->set_layout("no-left");
        parent::display_table($extensions, $editable);
    }

    public function display_doc(ExtensionInfo $info): void
    {
        Ctx::$page->set_layout("no-left");
        parent::display_doc($info);
    }
}
