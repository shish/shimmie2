<?php

class WikiTheme extends Themelet {
	/*
	 * Show a page
	 *
	 * $page = the shimmie page object
	 * $wiki_page = the wiki page, has ->title and ->body
	 * $nav_page = a wiki page object with navigation, has ->body
	 */
	public function display_page(Page $page, WikiPage $wiki_page, $nav_page) { // $nav_page = WikiPage or null
		if(is_null($nav_page)) {
			$nav_page = new WikiPage();
			$nav_page->body = "";
		}

		$tfe = new TextFormattingEvent($nav_page->body);
		send_event($tfe);

		global $user;
		if($user->is_admin()) {
			$tfe->formatted .= "<p>(<a href='".make_link("wiki/wiki:sidebar", "edit=on")."'>Edit</a>)";
		}

		$page->set_title(html_escape($wiki_page->title));
		$page->set_heading(html_escape($wiki_page->title));
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Wiki Index", $tfe->formatted, "left", 20));
		$page->add_block(new Block("Content", $this->create_display_html($wiki_page)));
	}

	public function display_page_editor(Page $page, WikiPage $wiki_page) {
		$page->set_title(html_escape($wiki_page->title));
		$page->set_heading(html_escape($wiki_page->title));
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Editor", $this->create_edit_html($wiki_page)));
	}

	protected function can_edit(User $user, WikiPage $page) {
		global $config;

		if(!is_null($page) && $page->is_locked() && !$user->is_admin()) return false;
		if($config->get_bool("wiki_edit_anon", false) && $user->is_anonymous()) return true;
		if($config->get_bool("wiki_edit_user", false) && !$user->is_anonymous()) return true;
		if($user->is_admin()) return true;
		return false;
	}

	protected function create_edit_html(WikiPage $page) {
		$h_title = html_escape($page->title);
		$u_title = url_escape($page->title);
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
			<form action='".make_link("wiki/$u_title", "save=on")."' method='POST'>
				<input type='hidden' name='title' value='$h_title'>
				<input type='hidden' name='revision' value='$i_revision'>
				<textarea name='body' style='width: 100%' rows='20'>".html_escape($page->body)."</textarea>
				$lock
				<br><input type='submit' value='Save'>
			</form>
		";
	}

	protected function create_display_html(WikiPage $page) {
		$owner = $page->get_owner();

		$tfe = new TextFormattingEvent($page->body);
		send_event($tfe);

		$html = "<div class='wiki-page'>";
		$html .= $tfe->formatted;
		$html .= "<hr>";
		$html .= "<p class='wiki-footer'>Revision {$page->revision} by ".
		         "<a href='".make_link("user/{$owner->name}")."'>{$owner->name}</a> at {$page->date} ";

		global $user;
		if($this->can_edit($user, $page)) {
			$html .= "[<a href='".make_link("wiki/{$page->title}", "edit=on")."'>edit</a>] ";
		}

		$html .= "</p></div>";

		return $html;
	}
}
?>
