<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{BUTTON, DIALOG, DIV, H1, SPAN};

use MicroHTML\HTMLElement;

class TermsTheme extends Themelet
{
    public function display_page(string $sitename, string $path, HTMLElement $body): void
    {
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
        Ctx::$page->add_block(new Block(null, $html, "main", 1));
    }
}
