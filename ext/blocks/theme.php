<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{INPUT, OPTION, SELECT, TABLE, TD, TEXTAREA, TH, TR};

/**
 * @phpstan-type BlockArray array{id:int,title:string,area:string,priority:int,userclass:string,pages:string,content:string}
 */
class BlocksTheme extends Themelet
{
    /**
     * @param array<BlockArray> $blocks
     */
    public function display_blocks(array $blocks): void
    {
        $html = TABLE(["class" => "form", "style" => "width: 100%;"]);
        foreach ($blocks as $block) {
            $html->appendChild(SHM_SIMPLE_FORM(
                make_link("blocks/update"),
                TR(
                    INPUT(["type" => "hidden", "name" => "id", "value" => $block['id']]),
                    TH("Title"),
                    TD(INPUT(["type" => "text", "name" => "title", "value" => $block['title']])),
                    TH("Area"),
                    TD(INPUT(["type" => "text", "name" => "area", "value" => $block['area']])),
                    TH("Priority"),
                    TD(INPUT(["type" => "text", "name" => "priority", "value" => $block['priority']])),
                    TH("User Class"),
                    TD(INPUT(["type" => "text", "name" => "userclass", "value" => $block['userclass']])),
                    TH("Pages"),
                    TD(INPUT(["type" => "text", "name" => "pages", "value" => $block['pages']])),
                    TH("Delete"),
                    TD(INPUT(["type" => "checkbox", "name" => "delete"])),
                    TD(INPUT(["type" => "submit", "value" => "Save"]))
                ),
                TR(
                    TD(["colspan" => "13"], TEXTAREA(["rows" => "5", "name" => "content"], $block['content']))
                ),
                TR(
                    TD(["colspan" => "13"], " ")
                ),
            ));
        }

        $html->appendChild(SHM_SIMPLE_FORM(
            make_link("blocks/add"),
            TR(
                TH("Title"),
                TD(INPUT(["type" => "text", "name" => "title", "value" => ""])),
                TH("Area"),
                TD(SELECT(["name" => "area"], OPTION("left"), OPTION("main"))),
                TH("Priority"),
                TD(INPUT(["type" => "text", "name" => "priority", "value" => '50'])),
                TH("User Class"),
                TD(INPUT(["type" => "text", "name" => "userclass", "value" => ""])),
                TH("Pages"),
                TD(INPUT(["type" => "text", "name" => "pages", "value" => 'post/list*'])),
                TD(["colspan" => '3'], INPUT(["type" => "submit", "value" => "Add"]))
            ),
            TR(
                TD(["colspan" => "13"], TEXTAREA(["rows" => "5", "name" => "content"]))
            ),
        ));

        $page = Ctx::$page;
        $page->set_title("Blocks");
        $page->add_block(new Block(null, $html));
    }
}
