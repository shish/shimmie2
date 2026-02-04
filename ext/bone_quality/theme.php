<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, BR, DIV, IMG, SPAN, emptyHTML};

use MicroHTML\HTMLElement;

class BoneQualityTheme extends Themelet
{
    /**
     * @var array<string, string> $boned_class
     */
    private array $boned_class = ["class" => "boned_color"];

    public function display_page(string $failure_string, bool $boned, ?HTMLElement $results): void
    {
        $bones = emptyHTML();
        $heading = "Congratulations. We appear to be un$failure_string, but remain ever vigilant.";
        if ($boned) {
            $bones = DIV(
                [],
                IMG(["src" => "/ext/bone_quality/images/boned.jpg"])
            );
            $heading = "";
        }

        $html = emptyHTML(
            $bones,
            $results,
        );

        Ctx::$page->set_title("review your fate");
        Ctx::$page->add_block(new Block($heading, $html, "main", 20));
    }

    public function generate_chore_search_html(string $search, bool $search_boned, int $search_count): HTMLElement
    {
        $search_boned_class = $search_boned ? $this->boned_class : [];
        return emptyHTML(
            A(["class" => "search", "href" => search_link(SearchTerm::explode($search))], $search),
            " remaining: ",
            SPAN($search_boned ? $search_boned_class : [], "$search_count"),
            BR()
        );
    }
}
