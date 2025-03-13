<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{TEXTAREA,rawHTML};
use function MicroHTML\TABLE;
use function MicroHTML\TD;
use function MicroHTML\TR;

class BiographyTheme extends Themelet
{
    public function display_biography(Page $page, string $bio): void
    {
        $page->add_block(new Block("About Me", rawHTML(format_text($bio)), "main", 30, "about-me"));
    }

    public function display_composer(Page $page, User $duser, string $bio): void
    {
        $html = SHM_SIMPLE_FORM(
            make_link("user/{$duser->name}/biography"),
            TABLE(
                ["class" => "form", "style" => "width: 100%"],
                TR(TD(TEXTAREA(["rows" => "6", "name" => "biography"], $bio))),
                TR(TD(SHM_SUBMIT("Save")))
            ),
        );

        $page->add_block(new Block("About Me", $html, "main", 30));
    }
}
