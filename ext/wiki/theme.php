<?php

class WikiTheme extends Themelet {
	/**
	 * Show a page.
	 *
	 * @param Page $page The shimmie page object
	 * @param WikiPage $wiki_page The wiki page, has ->title and ->body
	 * @param WikiPage|null $nav_page A wiki page object with navigation, has ->body
	 */
	public function display_page(Page $page, WikiPage $wiki_page, $nav_page) {
		global $user;

		if(is_null($nav_page)) {
			$nav_page = new WikiPage();
			$nav_page->body = "";
		}

		$tfe = new TextFormattingEvent($nav_page->body);
		send_event($tfe);

		// only the admin can edit the sidebar
		if($user->is_admin()) {
			$tfe->formatted .= "<p>(<a href='".make_link("wiki/wiki:sidebar", "edit=on")."'>Edit</a>)";
		}

		$page->set_title(html_escape($wiki_page->title));
		$page->set_heading(html_escape($wiki_page->title));
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Wiki Index", $tfe->formatted, "left", 20));
		$page->add_block(new Block(html_escape($wiki_page->title), $this->create_display_html($wiki_page)));
	}

	public function display_page_editor(Page $page, WikiPage $wiki_page) {
		$page->set_title(html_escape($wiki_page->title));
		$page->set_heading(html_escape($wiki_page->title));
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Editor", $this->create_edit_html($wiki_page)));
	}

	protected function create_edit_html(WikiPage $page) {
		$h_title = html_escape($page->title);
		$i_revision = int_escape($page->revision) + 1;

		global $user;
		if($user->is_admin()) {
			$val = $page->is_locked() ? " checked" : "";
			$lock = "<br>Lock page: <input type='checkbox' name='lock'$val>";
		}
		else {
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

	protected function create_display_html(WikiPage $page) {
		global $user;

		$owner = $page->get_owner();

		$tfe = new TextFormattingEvent($page->body);
		send_event($tfe);

		$edit = "<table><tr>";
		$edit .= Wiki::can_edit($user, $page) ?
			"
				<td>".make_form(make_link("wiki_admin/edit"))."
					<input type='hidden' name='title' value='".html_escape($page->title)."'>
					<input type='hidden' name='revision' value='".int_escape($page->revision)."'>
					<input type='submit' value='Edit'>
				</form></td>
			" :
			"";
		if($user->is_admin()) {
			$edit .= "
				<td>".make_form(make_link("wiki_admin/delete_revision"))."
					<input type='hidden' name='title' value='".html_escape($page->title)."'>
					<input type='hidden' name='revision' value='".int_escape($page->revision)."'>
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
			$tfe->formatted
			<hr>
			<p class='wiki-footer'>
				Revision {$page->revision}
				by <a href='".make_link("user/{$owner->name}")."'>{$owner->name}</a>
				at {$page->date}
				$edit
			</p>
			</div>
		";
	}
}

