<?php

declare(strict_types=1);

namespace Shimmie2;

use Jfcherng\Diff\{DiffHelper};

use function MicroHTML\{A, BR, DIV, HR, INPUT, P, STYLE, TABLE, TD, TEXTAREA, TR, emptyHTML, rawHTML};

use MicroHTML\HTMLElement;

class WikiTheme extends Themelet
{
    /**
     * Show a page.
     *
     * $wiki_page The wiki page, has ->title and ->body
     * $nav_page A wiki page object with navigation, has ->body
     */
    public function display_page(WikiPage $wiki_page, ?WikiPage $nav_page = null): void
    {
        if (is_null($nav_page)) {
            $nav_page = new WikiPage();
            $nav_page->body = "";
        }

        $body_html = format_text($nav_page->body);

        // only the admin can edit the sidebar
        if (Ctx::$user->can(WikiPermission::ADMIN)) {
            $link = A(["href" => make_link("wiki/wiki:sidebar/edit")], "Edit");
            $body_html = emptyHTML(
                $body_html,
                P("(", $link, ")")
            );
        }

        $page = Ctx::$page;
        if (!$wiki_page->exists) {
            $page->set_code(404);
        }
        $page->set_title($wiki_page->title);
        $page->add_block(new Block("Wiki Index", $body_html, "left", 20));
        $page->add_block(new Block($wiki_page->title, $this->create_display_html($wiki_page)));
    }

    public function display_list_page(?WikiPage $nav_page = null): void
    {
        global $database;
        if (is_null($nav_page)) {
            $nav_page = new WikiPage();
            $nav_page->body = "";
        }

        $body_html = format_text($nav_page->body);

        $query = "SELECT DISTINCT title FROM wiki_pages
                ORDER BY title ASC";
        $titles = $database->get_col($query);
        $html = DIV(["class" => "wiki-all-grid"]);
        foreach ($titles as $title) {
            $html->appendChild(A(["href" => make_link("wiki/$title")], $title));
        }
        Ctx::$page->set_title("Wiki page list");
        Ctx::$page->add_block(new Block("Wiki Index", $body_html, "left", 20));
        Ctx::$page->add_block(new Block("All Wiki Pages", $html));
    }

    /**
     * @param array<array{revision: string, date: string}> $history
     */
    public function display_page_history(string $title, array $history): void
    {
        $table = TABLE(["class" => "zebra"]);
        foreach ($history as $row) {
            $table->appendChild(TR(
                TD(INPUT(["type" => "radio", "name" => "r1", "value" => $row['revision']])),
                TD(INPUT(["type" => "radio", "name" => "r2", "value" => $row['revision']])),
                TD(A(["href" => make_link("wiki/$title", ["revision" => $row['revision']])], $row['revision'])),
                TD($row['date'])
            ));
        }
        $html = SHM_FORM(
            method: "GET",
            action: make_link("wiki/$title/diff"),
            children: [
                $table,
                SHM_SUBMIT("Compare Revisions", ["class" => "setupsubmit"]),
            ],
        );
        Ctx::$page->set_title($title);
        Ctx::$page->add_block(new Block($title, $html));
    }

    public function display_page_editor(WikiPage $wiki_page): void
    {
        Ctx::$page->set_title($wiki_page->title);
        Ctx::$page->add_block(new Block("Editor", $this->create_edit_html($wiki_page)));
    }

    public function display_page_diff(string $title, WikiPage $page1, WikiPage $page2): void
    {
        Ctx::$page->set_title("Diff for $title");
        $diff_html = DiffHelper::calculate($page1->body, $page2->body, "SideBySide");
        Ctx::$page->add_html_header(STYLE(DiffHelper::getStyleSheet()));
        Ctx::$page->add_block(new Block("Diff for $title", rawHTML($diff_html)));
    }

    protected function create_edit_html(WikiPage $page): HTMLElement
    {
        $lock = Ctx::$user->can(WikiPermission::ADMIN) ?
            emptyHTML(
                BR(),
                "Lock page: ",
                INPUT(["type" => "checkbox", "name" => "lock", "checked" => $page->is_locked()])
            ) :
            emptyHTML();

        $u_title = url_escape($page->title);
        return SHM_SIMPLE_FORM(
            make_link("wiki/$u_title/save"),
            INPUT(["type" => "hidden", "name" => "revision", "value" => $page->revision + 1]),
            TEXTAREA(["name" => "body", "style" => "width: 100%", "rows" => 20], $page->body),
            $lock,
            BR(),
            SHM_SUBMIT("Save", ["class" => "setupsubmit"])
        );
    }

    protected function format_wiki_page(WikiPage $page): HTMLElement
    {
        global $database;

        $text = "{body}";

        // if this is a tag page, add tag info
        $tag = $database->get_one("SELECT tag FROM tags WHERE tag = :tag", ["tag" => $page->title]);
        if (!is_null($tag)) {
            $text = Ctx::$config->get(WikiConfig::TAG_PAGE_TEMPLATE);

            if (AliasEditorInfo::is_enabled()) {
                $aliases = $database->get_col("
                    SELECT oldtag
                    FROM aliases
                    WHERE newtag = :title
                    ORDER BY oldtag ASC
                ", ["title" => $tag]);

                if (!empty($aliases)) {
                    $text = str_replace("{aliases}", implode(", ", $aliases), $text);
                } else {
                    $text = str_replace("{aliases}", Ctx::$config->get(WikiConfig::EMPTY_TAGINFO), $text);
                }
            }

            if (AutoTaggerInfo::is_enabled()) {
                $auto_tags = $database->get_one("
                    SELECT additional_tags
                    FROM auto_tag
                    WHERE tag = :title
                ", ["title" => $tag]);

                if (!empty($auto_tags)) {
                    $text = str_replace("{autotags}", $auto_tags, $text);
                } else {
                    $text = str_replace("{autotags}", Ctx::$config->get(WikiConfig::EMPTY_TAGINFO), $text);
                }
            }
        }

        $text = str_replace("{body}", $page->body, $text);

        return format_text($text);
    }

    protected function create_display_html(WikiPage $page): HTMLElement
    {
        $u_title = url_escape($page->title);
        $owner = $page->get_owner();
        $revisions_enabled = Ctx::$config->get(WikiConfig::ENABLE_REVISIONS);

        $formatted_body = self::format_wiki_page($page);

        $edit = TR();
        if (Wiki::can_edit(Ctx::$user, $page)) {
            $edit->appendChild(TD(SHM_SIMPLE_FORM(
                make_link("wiki/$u_title/edit"),
                INPUT(["type" => "hidden", "name" => "revision", "value" => $page->revision]),
                INPUT(["type" => "submit", "value" => "Edit"])
            )));
        }
        if (Ctx::$user->can(WikiPermission::ADMIN)) {
            if ($revisions_enabled) {
                $edit->appendChild(
                    TD(SHM_SIMPLE_FORM(
                        make_link("wiki/$u_title/delete_revision"),
                        INPUT(["type" => "hidden", "name" => "revision", "value" => $page->revision]),
                        SHM_SUBMIT("Delete")
                    ))
                );
            }
            $edit->appendChild(TD(SHM_SIMPLE_FORM(
                make_link("wiki/$u_title/delete_all"),
                SHM_SUBMIT($revisions_enabled ? "Delete All" : "Delete")
            )));
        }

        return DIV(
            ["class" => "wiki-page"],
            $formatted_body,
            HR(),
            P(
                ["class" => "wiki-footer"],
                ... $revisions_enabled ? [
                    A(["href" => make_link("wiki/$u_title/history")], "Revision {$page->revision}"),
                    " by ",
                ] : [],
                ... [
                    A(["href" => make_link("user/{$owner->name}")], $owner->name),
                    " at {$page->date}",
                    TABLE($edit),
                ]
            )
        );
    }
}
