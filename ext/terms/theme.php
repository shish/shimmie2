<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\BUTTON;
use function MicroHTML\DIALOG;
use function MicroHTML\DIV;
use function MicroHTML\H1;
use function MicroHTML\SPAN;

class TermsTheme extends Themelet
{
    public function display_page(string $sitename, string $path, HTMLElement $body): void
    {
        global $page;
        $html = DIV(
            ["id" => "terms-modal-bg"],
            DIALOG(
                ["id" => "terms-modal", "class" => "setupblock", "open" => true],
                H1(SPAN($sitename)),
                $body,
                SHM_SIMPLE_FORM(
                    make_link("accept_terms/$path"),
                    BUTTON(["class" => "terms-modal-enter", "autofocus" => true], "Enter")
                )
            )
        );
        $page->add_block(new Block(null, $html, "main", 1));
    }
}
