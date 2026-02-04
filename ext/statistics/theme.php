<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, B, DIV, SPAN, emptyHTML};

use MicroHTML\HTMLElement;

use function MicroHTML\{TABLE, TBODY, TD, TH, THEAD, TR};

class StatisticsTheme extends Themelet
{
    public function display_page(int $limit, ?HTMLElement $tag_table, ?HTMLElement $upload_table, ?HTMLElement $comment_table, ?HTMLElement $favorite_table): void
    {
        $html = emptyHTML(
            $tag_table,
            $upload_table,
            $comment_table,
            $favorite_table,
        );

        Ctx::$page->set_title("Stats - Top $limit");
        Ctx::$page->add_block(new Block("Stats", $html, "main", 20));
    }

    /**
     * @param array<string, int|HTMLElement> $data
     */
    public function build_table(array $data, string $id, string $title, ?int $limit = 10): HTMLElement
    {
        $rows = emptyHTML();
        $n = 1;
        foreach ($data as $user => $value) {
            $rows->appendChild(
                TR(
                    TD([], $n),
                    TD([], $value),
                    TD([], A(["class" => "username", "href" => make_link('user/'.$user)], $user)),
                )
            );
            $n++;
            if ($n > $limit) {
                break;
            }
        }
        $table = TABLE(
            ["class" => "zebra stats-table"],
            THEAD(
                TR(TH(["colspan" => 3], B($title))),
                TR(TH([], "Place"), TH([], "Amount"), TH([], "User"))
            ),
            TBODY($rows)
        );
        return DIV(
            ["id" => "table$id", "class" => "stats-container"],
            $table
        );
    }

    public function build_tag_field(int $tally, int $diff): HTMLElement
    {
        return emptyHTML(
            SPAN(["title" => "Tags changed (ignoring aliases) edits"], $diff),
            " ",
            SPAN(["class" => "tag_count", "title" => "Total edits"], $tally)
        );
    }
}
