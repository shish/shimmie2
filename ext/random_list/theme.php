<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{INPUT};
use function MicroHTML\B;
use function MicroHTML\BR;
use function MicroHTML\DIV;
use function MicroHTML\P;
use function MicroHTML\emptyHTML;

class RandomListTheme extends Themelet
{
    /**
     * @param string[] $search_terms
     * @param Image[] $images
     */
    public function display_page(Page $page, array $search_terms, array $images): void
    {
        $html = emptyHTML(B("Refresh the page to view more posts"));
        if (count($images)) {
            $list = DIV(["class" => "shm-image-list"]);
            foreach ($images as $image) {
                $list->appendChild($this->build_thumb($image));
            }
            $html->appendChild($list);
        } else {
            $html->appendChild(BR());
            $html->appendChild(P("No posts were found to match the search criteria"));
        }

        $page->add_block(new Block("Random Posts", $html));
        $page->set_title("Random Posts");
        $this->display_navigation(extra: SHM_FORM(
            action: make_link("random"),
            method: "GET",
            children: [
                INPUT([
                    "type" => "search",
                    "name" => "search",
                    "value" => Tag::implode($search_terms),
                    "placeholder" => "Search random list",
                    "class" => "autocomplete_tags"
                ]),
                INPUT([
                    "type" => "submit",
                    "value" => "Find",
                    "style" => "display: none;"
                ])
            ]
        ));
    }
}
