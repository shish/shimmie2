<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

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
    public function display_user_config_page(Page $page, User $user, SetupPanel $panel): void
    {
        usort($panel->blocks, "Shimmie2\blockcmp");

        /*
         * Try and keep the two columns even; count the line breaks in
         * each an calculate where a block would work best
         */
        $setupblock_html = "";
        foreach ($panel->blocks as $block) {
            $setupblock_html .= $this->sb_to_html($block);
        }

        $table = "
			".make_form(make_link("user_config/save"))."
			    <input type='hidden' name='id' value='".$user->id."'>
				<div class='setupblocks'>$setupblock_html</div>
				<input type='submit' value='Save Settings'>
			</form>
			";

        $page->set_title("User Options");
        $page->set_heading("User Options");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("User Options", $table));
        $page->set_mode(PageMode::PAGE);
    }

    protected function sb_to_html(SetupBlock $block): string
    {
        $h = $block->header;
        $b = $block->body;
        $i = preg_replace('/[^a-zA-Z0-9]/', '_', $h) . "-setup";
        $html = "
			<section class='setupblock'>
				<b class='shm-toggler' data-toggle-sel='#$i'>$h</b>
				<br><div id='$i'>$b</div>
			</section>
		";
        return $html;
    }
}
