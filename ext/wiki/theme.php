<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

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

        $body_html = format_text($nav_page->body);

        // only the admin can edit the sidebar
        if ($user->can(Permissions::WIKI_ADMIN)) {
            $body_html .= "<p>(<a href='".make_link("wiki/wiki:sidebar/edit")."'>Edit</a>)";
        }

        if (!$wiki_page->exists) {
            $page->set_code(404);
        }

        $page->set_title($wiki_page->title);
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Wiki Index", rawHTML($body_html), "left", 20));
        $page->add_block(new Block($wiki_page->title, $this->create_display_html($wiki_page)));
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
        $page->set_title($title);
        $page->add_block(new NavBlock());
        $page->add_block(new Block($title, rawHTML($html)));
    }

    public function display_page_editor(Page $page, WikiPage $wiki_page): void
    {
        $page->set_title($wiki_page->title);
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Editor", $this->create_edit_html($wiki_page)));
    }

    protected function create_edit_html(WikiPage $page): HTMLElement
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
        return SHM_SIMPLE_FORM(
            "wiki/$u_title/save",
            INPUT(["type" => "hidden", "name" => "revision", "value" => $page->revision + 1]),
            TEXTAREA(["name" => "body", "style" => "width: 100%", "rows" => 20], $page->body),
            $lock,
            BR(),
            SHM_SUBMIT("Save")
        );
    }

    protected function format_wiki_page(WikiPage $page): HTMLElement
    {
        global $database, $config;

        $text = "{body}";

        // if this is a tag page, add tag info
        $tag = $database->get_one("SELECT tag FROM tags WHERE tag = :tag", ["tag" => $page->title]);
        if (!is_null($tag)) {
            $text = $config->get_string(WikiConfig::TAG_PAGE_TEMPLATE);

            if (Extension::is_enabled(AliasEditorInfo::KEY)) {
                $aliases = $database->get_col("
                    SELECT oldtag
                    FROM aliases
                    WHERE newtag = :title
                    ORDER BY oldtag ASC
                ", ["title" => $tag]);

                if (!empty($aliases)) {
                    $text = str_replace("{aliases}", implode(", ", $aliases), $text);
                } else {
                    $text = str_replace("{aliases}", $config->get_string(WikiConfig::EMPTY_TAGINFO), $text);
                }
            }

            if (Extension::is_enabled(AutoTaggerInfo::KEY)) {
                $auto_tags = $database->get_one("
                    SELECT additional_tags
                    FROM auto_tag
                    WHERE tag = :title
                ", ["title" => $tag]);

                if (!empty($auto_tags)) {
                    $text = str_replace("{autotags}", $auto_tags, $text);
                } else {
                    $text = str_replace("{autotags}", $config->get_string(WikiConfig::EMPTY_TAGINFO), $text);
                }
            }
        }

        $text = str_replace("{body}", $page->body, $text);

        return rawHTML(format_text($text));
    }

    protected function create_display_html(WikiPage $page): HTMLElement
    {
        global $user;

        $u_title = url_escape($page->title);
        $owner = $page->get_owner();

        $formatted_body = self::format_wiki_page($page);

        $edit = TR();
        if (Wiki::can_edit($user, $page)) {
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

        return DIV(
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
