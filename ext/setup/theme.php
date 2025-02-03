<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\DIV;
use function MicroHTML\H3;
use function MicroHTML\INPUT;
use function MicroHTML\SECTION;
use function MicroHTML\TABLE;
use function MicroHTML\TBODY;
use function MicroHTML\TD;
use function MicroHTML\TEXTAREA;
use function MicroHTML\TFOOT;
use function MicroHTML\TH;
use function MicroHTML\THEAD;
use function MicroHTML\TR;
use function MicroHTML\rawHTML;

class SetupTheme extends Themelet
{
    /*
     * Display a set of setup option blocks
     *
     * $panel = the container of the blocks
     * $panel->blocks the blocks to be displayed, unsorted
     *
     * It's recommended that the theme sort the blocks before doing anything
     * else, using:  usort($panel->blocks, "blockcmp");
     *
     * The page should wrap all the options in a form which links to setup_save
     */
    public function display_page(Page $page, SetupPanel $panel): void
    {
        usort($panel->blocks, "Shimmie2\blockcmp");

        $blocks = DIV(["class" => "setupblocks"]);
        foreach ($panel->blocks as $block) {
            $blocks->appendChild($this->sb_to_html($block));
        }

        $table = SHM_SIMPLE_FORM(
            "setup/save",
            $blocks,
            INPUT(['class' => 'setupsubmit', 'type' => 'submit', 'value' => 'Save Settings'])
        );

        $page->set_title("Shimmie Setup");
        $page->add_block(new Block("Navigation", $this->build_navigation(), "left", 0));
        $page->add_block(new Block(null, $table, id: "Setupmain"));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function display_advanced(Page $page, array $options): void
    {
        $rows = TBODY();
        ksort($options);
        foreach ($options as $name => $value) {
            $ext = ConfigGroup::get_group_for_entry_by_name($name);
            if ($ext) {
                $ext_name = \Safe\preg_replace("#Shimmie2.(.*)Config#", '$1', $ext::class);
            } else {
                $ext_name = "";
            }

            if (is_null($value)) {
                $value = '';
            }

            $valbox = TD();
            if (is_string($value) && str_contains($value, "\n")) {
                $valbox->appendChild(TEXTAREA(
                    ['name' => "_config_$name", 'cols' => 50, 'rows' => 4],
                    $value,
                ));
            } else {
                $valbox->appendChild(INPUT(
                    ['type' => 'text', 'name' => "_config_$name", 'value' => $value],
                ));
            }
            $valbox->appendChild(INPUT(
                ['type' => 'hidden', 'name' => '_type_' . $name, 'value' => 'string'],
            ));

            $rows->appendChild(TR(TD($ext_name), TD($name), $valbox));
        }

        $table = SHM_SIMPLE_FORM(
            "setup/save",
            TABLE(
                ['id' => 'settings', 'class' => 'zebra advanced_settings'],
                THEAD(TR(
                    TH(['width' => '20%'], 'Group'),
                    TH(['width' => '20%'], 'Name'),
                    TH('Value'),
                )),
                $rows,
                TFOOT(TR(
                    TD(["colspan" => 3], INPUT(['type' => 'submit', 'value' => 'Save Settings']))
                )),
            )
        );

        $page->set_title("Shimmie Setup");
        $page->add_block(new Block("Navigation", $this->build_navigation(), "left", 0));
        $page->add_block(new Block("Setup", $table));
    }

    protected function build_navigation(): HTMLElement
    {
        return rawHTML("
			<a href='".make_link()."'>Index</a>
			<br><a href='https://github.com/shish/shimmie2/wiki/Settings'>Help</a>
			<br><a href='".make_link("setup/advanced")."'>Advanced</a>
		");
    }

    protected function sb_to_html(SetupBlock $block): HTMLElement
    {
        return SECTION(
            ['class' => 'setupblock'],
            H3($block->header),
            DIV(['class' => 'blockbody'], $block->get_html()),
        );
    }
}
