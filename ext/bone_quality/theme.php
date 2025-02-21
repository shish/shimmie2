<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\A;
use function MicroHTML\B;
use function MicroHTML\BR;
use function MicroHTML\DIV;
use function MicroHTML\SPAN;
use function MicroHTML\IMG;
use function MicroHTML\emptyHTML;
use function MicroHTML\rawHTML;

class BoneQualityTheme extends Themelet
{
    /**
     * @var array<string, string> $boned_class
     */
    private array $boned_class = ["class" => "boned_color"];

    public function display_page(Page $page, string $failure_string, bool $boned, ?HTMLElement $results): void
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

        $page->set_title("review your fate");
        $page->add_block(new NavBlock());
        $page->add_block(new Block($heading, $html, "main", 20));
    }

    public function generate_chore_search_html(string $search, bool $search_boned, int $search_count): HTMLElement
    {
        $search_boned_class = $search_boned ? $this->boned_class : [];
        return emptyHTML(
            A(["class" => "search","href" => "/post/list/$search/1"], $search),
            rawHTML(" posts remaining: "),
            SPAN($search_boned ? $search_boned_class : [], "$search_count"),
            BR()
        );
    }
}
