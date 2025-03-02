<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\A;
use function MicroHTML\BR;
use function MicroHTML\DIV;
use function MicroHTML\H3;
use function MicroHTML\INPUT;
use function MicroHTML\SECTION;
use function MicroHTML\emptyHTML;

class SetupTheme extends Themelet
{
    /**
     * Display a set of setup option blocks
     *
     * It's recommended that the theme sort the blocks before doing anything
     * else, using:  usort($panel->blocks, "blockcmp");
     *
     * The page should wrap all the options in a form which links to setup_save
     *
     * @param array<Block> $config_blocks
     */
    public function display_page(Page $page, array $config_blocks): void
    {
        usort($config_blocks, Block::cmp(...));

        $blocks = DIV(["class" => "setupblocks"]);
        foreach ($config_blocks as $block) {
            $blocks->appendChild($this->sb_to_html($block));
        }

        $table = SHM_SIMPLE_FORM(
            "setup/save",
            $blocks,
            INPUT(['class' => 'setupsubmit', 'type' => 'submit', 'value' => 'Save Settings'])
        );

        $nav = emptyHTML(
            A(["href" => make_link()], "Index"),
            BR(),
            @$_GET["advanced"] == "on" ?
                A(["href" => make_link("setup")], "Simple") :
                A(["href" => make_link("setup", "advanced=on")], "Advanced")
        );

        $page->set_title("Shimmie Setup");
        $page->add_block(new Block("Navigation", $nav, "left", 0));
        $page->add_block(new Block(null, $table, id: "Setupmain"));
    }

    protected function sb_to_html(Block $block): HTMLElement
    {
        return SECTION(
            ['class' => 'setupblock'],
            H3($block->header),
            DIV(['class' => 'blockbody'], $block->body),
        );
    }
}
