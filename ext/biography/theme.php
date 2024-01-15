<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\TEXTAREA;

class BiographyTheme extends Themelet
{
    public function display_biography(Page $page, string $bio): void
    {
        $page->add_block(new Block("About Me", format_text($bio), "main", 30, "about-me"));
    }

    public function display_composer(Page $page, string $bio): void
    {
        $html = SHM_SIMPLE_FORM(
            make_link("biography"),
            TEXTAREA(["style" => "width: 100%", "rows" => "6", "name" => "biography"], $bio),
            SHM_SUBMIT("Save")
        );

        $page->add_block(new Block("About Me", $html, "main", 30));
    }
}
