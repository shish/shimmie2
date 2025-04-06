<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{TABLE, TD, TR};
use function MicroHTML\{TEXTAREA};

class BiographyTheme extends Themelet
{
    public function display_biography(string $bio): void
    {
        Ctx::$page->add_block(new Block("About Me", format_text($bio), "main", 30, "about-me"));
    }

    public function display_composer(User $duser, string $bio): void
    {
        $html = SHM_SIMPLE_FORM(
            make_link("user/{$duser->name}/biography"),
            TABLE(
                ["class" => "form", "style" => "width: 100%"],
                TR(TD(TEXTAREA(["rows" => "6", "name" => "biography"], $bio))),
                TR(TD(SHM_SUBMIT("Save")))
            ),
        );

        Ctx::$page->add_block(new Block("About Me", $html, "main", 30));
    }
}
