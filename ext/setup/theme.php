<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, DIV, H3, INPUT, SECTION};

use MicroHTML\HTMLElement;

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
    public function display_page(array $config_blocks): void
    {
        usort($config_blocks, Block::cmp(...));

        $blocks = DIV(["class" => "setupblocks"]);
        foreach ($config_blocks as $block) {
            $blocks->appendChild($this->sb_to_html($block));
        }

        $table = SHM_SIMPLE_FORM(
            make_link("setup/save"),
            $blocks,
            INPUT(['class' => 'setupsubmit', 'type' => 'submit', 'value' => 'Save Settings'])
        );

        $nav = @$_GET["advanced"] === "on" ?
            A(["href" => make_link("setup")], "Simple") :
            A(["href" => make_link("setup", ["advanced" => "on"])], "Advanced");

        Ctx::$page->set_title("Shimmie Setup");
        Ctx::$page->add_to_navigation($nav);
        Ctx::$page->add_block(new Block(null, $table, id: "Setupmain"));
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
