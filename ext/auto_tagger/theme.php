<?php

declare(strict_types=1);

namespace Shimmie2;

class AutoTaggerTheme extends Themelet
{
    /**
     * Show a page of auto-tag definitions.
     *
     * Note: $can_manage = whether things like "add new alias" should be shown
     */
    public function display_auto_tagtable($table, $paginator): void
    {
        global $page, $user;

        $can_manage = $user->can(Permissions::MANAGE_AUTO_TAG);
        $html = "
            $table
            $paginator
			<p><a href='".make_link("auto_tag/export/auto_tag.csv")."' download='auto_tag.csv'>Download as CSV</a></p>
		";

        $bulk_html = "
			".make_form(make_link("auto_tag/import"), 'post', true)."
				<input type='file' name='auto_tag_file'>
				<input type='submit' value='Upload List'>
			</form>
		";

        $page->set_title("Auto-Tag List");
        $page->set_heading("Auto-Tag List");
        $page->add_block(new NavBlock());
		$block = new Block("Auto-Tag", $html);
        $page->add_block($block);
        if ($can_manage) {
            $page->add_block(new Block("Bulk Upload", $bulk_html, "main", 51));
        }
    }
}
