<?php

declare(strict_types=1);

namespace Shimmie2;

class WikiTheme extends Themelet
{
    /**
     * Show a page.
     *
     * $wiki_page The wiki page, has ->title and ->body
     * $nav_page A wiki page object with navigation, has ->body
     */
    public function display_page(Page $page, WikiPage $wiki_page, ?WikiPage $nav_page = null)
    {
        global $user;

        if (is_null($nav_page)) {
            $nav_page = new WikiPage();
            $nav_page->body = "";
        }

        $tfe = send_event(new TextFormattingEvent($nav_page->body));

        // only the admin can edit the sidebar
        if ($user->can(Permissions::WIKI_ADMIN)) {
            $tfe->formatted .= "<p>(<a href='".make_link("wiki/wiki:sidebar", "edit=on")."'>Edit</a>)";
        }

        // see if title is a category'd tag
        $title_html = html_escape($wiki_page->title);
        if (class_exists('Shimmie2\TagCategories')) {
            $tagcategories = new TagCategories();
            $tag_category_dict = $tagcategories->getKeyedDict();
            $title_html = $tagcategories->getTagHtml($title_html, $tag_category_dict);
        }

        if (!$wiki_page->exists) {
            $page->set_code(404);
        }

        $page->set_title(html_escape($wiki_page->title));
        $page->set_heading(html_escape($wiki_page->title));
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Wiki Index", $tfe->formatted, "left", 20));
        $page->add_block(new Block($title_html, $this->create_display_html($wiki_page)));
    }

    public function display_page_history(Page $page, string $title, array $history)
    {
        $html = "<table class='zebra'>";
        foreach ($history as $row) {
            $rev = $row['revision'];
            $html .= "<tr><td><a href='".make_link("wiki/$title", "revision=$rev")."'>{$rev}</a></td><td>{$row['date']}</td></tr>";
        }
        $html .= "</table>";
        $page->set_title(html_escape($title));
        $page->set_heading(html_escape($title));
        $page->add_block(new NavBlock());
        $page->add_block(new Block(html_escape($title), $html));
    }

    public function display_page_editor(Page $page, WikiPage $wiki_page)
    {
        $page->set_title(html_escape($wiki_page->title));
        $page->set_heading(html_escape($wiki_page->title));
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Editor", $this->create_edit_html($wiki_page)));
    }

    protected function create_edit_html(WikiPage $page): string
    {
        $h_title = html_escape($page->title);
        $i_revision = $page->revision + 1;

        global $user;
        if ($user->can(Permissions::WIKI_ADMIN)) {
            $val = $page->is_locked() ? " checked" : "";
            $lock = "<br>Lock page: <input type='checkbox' name='lock'$val>";
        } else {
            $lock = "";
        }
        return "
			".make_form(make_link("wiki_admin/save"))."
				<input type='hidden' name='title' value='$h_title'>
				<input type='hidden' name='revision' value='$i_revision'>
				<textarea name='body' style='width: 100%' rows='20'>".html_escape($page->body)."</textarea>
				$lock
				<br><input type='submit' value='Save'>
			</form>
		";
    }

    protected function create_display_html(WikiPage $page): string
    {
        global $user;

        $owner = $page->get_owner();

        $formatted_body = Wiki::format_tag_wiki_page($page);

        $edit = "<table><tr>";
        $edit .= Wiki::can_edit($user, $page) ?
            "
				<td>".make_form(make_link("wiki_admin/edit"))."
					<input type='hidden' name='title' value='".html_escape($page->title)."'>
					<input type='hidden' name='revision' value='".$page->revision."'>
					<input type='submit' value='Edit'>
				</form></td>
			" :
            "";
        if ($user->can(Permissions::WIKI_ADMIN)) {
            $edit .= "
				<td>".make_form(make_link("wiki_admin/delete_revision"))."
					<input type='hidden' name='title' value='".html_escape($page->title)."'>
					<input type='hidden' name='revision' value='".$page->revision."'>
					<input type='submit' value='Delete This Version'>
				</form></td>
				<td>".make_form(make_link("wiki_admin/delete_all"))."
					<input type='hidden' name='title' value='".html_escape($page->title)."'>
					<input type='submit' value='Delete All'>
				</form></td>
			";
        }
        $edit .= "</tr></table>";

        return "
			<div class='wiki-page'>
			$formatted_body
			<hr>
			<p class='wiki-footer'>
				<a href='".make_link("wiki_admin/history", "title={$page->title}")."'>Revision {$page->revision}</a>
				by <a href='".make_link("user/{$owner->name}")."'>{$owner->name}</a>
				at {$page->date}
				$edit
			</p>
			</div>
		";
    }
}
