<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{TEXTAREA,BR,TABLE,TR,TD};

class LinkScanTheme extends Themelet
{
    public function display_form(): void
    {
        global $page;

        $html = SHM_SIMPLE_FORM(
            "admin/link_scan",
            TABLE(
                ["class" => "form"],
                TR(TD(TEXTAREA(["name" => 'text', "placeholder" => 'Paste text']))),
                TR(TD(SHM_SUBMIT('Find Posts'))),
            ),
        );
        $page->add_block(new Block("Find Referenced Posts", $html));
    }
}
