<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\FORM;
use function MicroHTML\INPUT;
use function MicroHTML\P;
use function MicroHTML\TEXTAREA;

class ETTheme extends Themelet
{
    /*
     * Create a page showing info
     */
    public function display_info_page(string $yaml, string $extra): void
    {
        global $page;

        $page->set_title("System Info");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Site Information", $this->build_data_form($yaml)));
        $page->add_block(new Block("System Information", TEXTAREA(
            ["name" => 'data', "style" => "width: 100%; height: 20em;"],
            $extra
        )));
    }

    protected function build_data_form(string $yaml): \MicroHTML\HTMLElement
    {
        return FORM(
            ["action" => "https://shimmie.shishnet.org/register.php", "method" => "POST"],
            INPUT(["type" => "hidden", "name" => "registration_api", "value" => "2"]),
            "Your stats are useful so that I know which combinations of ".
                "web servers / databases / etc I need to support :)",
            TEXTAREA(
                ["name" => 'data', "style" => "width: 100%; height: 20em;"],
                $yaml
            ),
            INPUT(
                ["type" => 'submit', "value" => 'Click to send to Shish', "style" => "width: 100%; padding: 1em;"]
            ),
        );
    }
}
