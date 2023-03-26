<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\TABLE;
use function MicroHTML\TR;
use function MicroHTML\TH;
use function MicroHTML\TD;
use function MicroHTML\INPUT;
use function MicroHTML\TEXTAREA;
use function MicroHTML\rawHTML;
use function MicroHTML\SELECT;
use function MicroHTML\OPTION;

class BlocksTheme extends Themelet
{
    public function display_blocks($blocks)
    {
        global $page;

        $html = TABLE(["class"=>"form", "style"=>"width: 100%;"]);
        foreach ($blocks as $block) {
            $html->appendChild(SHM_SIMPLE_FORM(
                "blocks/update",
                TR(
                    INPUT(["type"=>"hidden", "name"=>"id", "value"=>$block['id']]),
                    TH("Title"),
                    TD(INPUT(["type"=>"text", "name"=>"title", "value"=>$block['title']])),
                    TH("Area"),
                    TD(INPUT(["type"=>"text", "name"=>"area", "value"=>$block['area']])),
                    TH("Priority"),
                    TD(INPUT(["type"=>"text", "name"=>"priority", "value"=>$block['priority']])),
                    TH("Pages"),
                    TD(INPUT(["type"=>"text", "name"=>"pages", "value"=>$block['pages']])),
                    TH("Delete"),
                    TD(INPUT(["type"=>"checkbox", "name"=>"delete"])),
                    TD(INPUT(["type"=>"submit", "value"=>"Save"]))
                ),
                TR(
                    TD(["colspan"=>"11"], TEXTAREA(["rows"=>"5", "name"=>"content"], $block['content']))
                ),
                TR(
                    TD(["colspan"=>"11"], rawHTML("&nbsp;"))
                ),
            ));
        }

        $html->appendChild(SHM_SIMPLE_FORM(
            "blocks/add",
            TR(
                TH("Title"),
                TD(INPUT(["type"=>"text", "name"=>"title", "value"=>""])),
                TH("Area"),
                TD(SELECT(["name"=>"area"], OPTION("left"), OPTION("main"))),
                TH("Priority"),
                TD(INPUT(["type"=>"text", "name"=>"priority", "value"=>'50'])),
                TH("Pages"),
                TD(INPUT(["type"=>"text", "name"=>"pages", "value"=>'post/list*'])),
                TD(["colspan"=>'3'], INPUT(["type"=>"submit", "value"=>"Add"]))
            ),
            TR(
                TD(["colspan"=>"11"], TEXTAREA(["rows"=>"5", "name"=>"content"]))
            ),
        ));

        $page->set_title("Blocks");
        $page->set_heading("Blocks");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Block Editor", (string)$html));
    }
}
