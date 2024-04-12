<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{FORM, INPUT, TABLE, TR, TD, emptyHTML, rawHTML, BR, TEXTAREA, DIV, HR, P, A};

class WikiTheme extends Themelet
{
    /**
     * Show a page.
     *
     * $wiki_page The wiki page, has ->title and ->body
     * $nav_page A wiki page object with navigation, has ->body
     */
    public function display_page(Page $page, WikiPage $wiki_page, ?WikiPage $nav_page = null): void
    {
        global $user;

        if (is_null($nav_page)) {
            $nav_page = new WikiPage();
            $nav_page->body = "";
        }

        $tfe = send_event(new TextFormattingEvent($nav_page->body));

        // only the admin can edit the sidebar
        if ($user->can(Permissions::WIKI_ADMIN)) {
            $tfe->formatted .= "<p>(<a href='".make_link("wiki/wiki:sidebar/edit")."'>Edit</a>)";
        }

        // see if title is a category'd tag
        $title_html = html_escape($wiki_page->title);
        if (Extension::is_enabled(TagCategoriesInfo::KEY)) {
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

    /**
     * @param array<array{revision: string, date: string}> $history
     */

    public function display_page_history(Page $page, string $title, array $history): void
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

    public function display_page_editor(Page $page, WikiPage $wiki_page): void
    {
        $page->set_title(html_escape($wiki_page->title));
        $page->set_heading(html_escape($wiki_page->title));
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Editor", $this->create_edit_html($wiki_page)));
    }

    protected function create_edit_html(WikiPage $page): string
    {
        global $user;

        $lock = $user->can(Permissions::WIKI_ADMIN) ?
            emptyHTML(
                BR(),
                "Lock page: ",
                INPUT(["type" => "checkbox", "name" => "lock", "checked" => $page->is_locked()])
            ) :
            emptyHTML();

        $u_title = url_escape($page->title);
        return (string)SHM_SIMPLE_FORM(
            "wiki/$u_title/save",
            INPUT(["type" => "hidden", "name" => "revision", "value" => $page->revision + 1]),
            TEXTAREA(["name" => "body", "style" => "width: 100%", "rows" => 20], $page->body),
            $lock,
            BR(),
            SHM_SUBMIT("Save")
        );
    }

    protected function create_display_html(WikiPage $page): string
    {
        global $user;

        $u_title = url_escape($page->title);
        $owner = $page->get_owner();

        $formatted_body = rawHTML(Wiki::format_tag_wiki_page($page));

        $edit = TR();
        if(Wiki::can_edit($user, $page)) {
            $edit->appendChild(TD(FORM(
                ["action" => make_link("wiki/$u_title/edit", "revision={$page->revision}")],
                INPUT(["type" => "submit", "value" => "Edit"])
            )));
        }
        if ($user->can(Permissions::WIKI_ADMIN)) {
            $edit->appendChild(
                TD(SHM_SIMPLE_FORM(
                    "wiki/$u_title/delete_revision",
                    INPUT(["type" => "hidden", "name" => "revision", "value" => $page->revision]),
                    SHM_SUBMIT("Delete")
                ))
            );
            $edit->appendChild(TD(SHM_SIMPLE_FORM(
                "wiki/$u_title/delete_all",
                SHM_SUBMIT("Delete All")
            )));
        }

        return (string)DIV(
            ["class" => "wiki-page"],
            $formatted_body,
            HR(),
            P(
                ["class" => "wiki-footer"],
                A(["href" => make_link("wiki/$u_title/history")], "Revision {$page->revision}"),
                " by ",
                A(["href" => make_link("user/{$owner->name}")], $owner->name),
                " at {$page->date}",
                TABLE($edit),
            )
        );
    }
}
