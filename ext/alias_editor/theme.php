<?php

declare(strict_types=1);

class AliasEditorTheme extends Themelet
{
    /**
     * Show a page of aliases.
     *
     * Note: $can_manage = whether things like "add new alias" should be shown
     */
    public function display_aliases($table, $paginator): void
    {
        global $page, $user;

        $can_manage = $user->can(Permissions::MANAGE_ALIAS_LIST);
        $html = "
            $table
            $paginator
			<p><a href='".make_link("alias/export/aliases.csv")."' download='aliases.csv'>Download as CSV</a></p>
		";

        $bulk_html = "
			".make_form(make_link("alias/import"), 'post', true)."
				<input type='file' name='alias_file'>
				<input type='submit' value='Upload List'>
			</form>
		";

        $page->set_title("Alias List");
        $page->set_heading("Alias List");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Aliases", $html));
        if ($can_manage) {
            $page->add_block(new Block("Bulk Upload", $bulk_html, "main", 51));
        }
    }
}
