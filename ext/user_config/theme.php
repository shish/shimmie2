<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\DIV;
use function MicroHTML\H3;
use function MicroHTML\INPUT;
use function MicroHTML\SECTION;
use function MicroHTML\rawHTML;

class UserConfigTheme extends Themelet
{
    public function get_user_operations(string $key): HTMLElement
    {
        $html = "
                <p>".make_form(make_link("user_admin/reset_api_key"))."
                    <table style='width: 300px;'>
                        <tbody>
                        <tr><th colspan='2'>API Key</th></tr>
                        <tr>
                            <td>
                                $key
                            </td>
                        </tbody>
                        <tfoot>
                            <tr><td><input type='submit' value='Reset Key'></td></tr>
                        </tfoot>
                    </table>
                </form>
            ";
        return rawHTML($html);
    }


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
    public function display_user_config_page(Page $page, array $config_blocks, User $user): void
    {
        usort($config_blocks, "Shimmie2\blockcmp");

        $blocks = DIV(["class" => "setupblocks"]);
        foreach ($config_blocks as $block) {
            $blocks->appendChild($this->sb_to_html($block));
        }

        $table = SHM_SIMPLE_FORM(
            "user_config/save",
            INPUT(['type' => 'hidden', 'name' => 'id', 'value' => $user->id]),
            $blocks,
            INPUT(['class' => 'setupsubmit', 'type' => 'submit', 'value' => 'Save Settings'])
        );

        $page->set_mode(PageMode::PAGE);
        $page->set_title("User Options");
        $page->add_block(new NavBlock());
        $page->add_block(new Block(null, $table, id: "Setupmain"));
    }

    protected function sb_to_html(Block $block): HTMLElement
    {
        return SECTION(
            ["class" => "setupblock"],
            H3($block->header),
            DIV(["class" => "blockbody"], $block->body)
        );
    }
}
