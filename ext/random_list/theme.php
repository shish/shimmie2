<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{B, BR, DIV, P, emptyHTML};
use function MicroHTML\{INPUT};

class RandomListTheme extends Themelet
{
    /**
     * @param search-term-array $search_terms
     * @param Image[] $images
     */
    public function display_page(array $search_terms, array $images): void
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

        Ctx::$page->add_block(new Block("Random Posts", $html));
        Ctx::$page->set_title("Random Posts");
        Ctx::$page->add_to_navigation(SHM_FORM(
            action: make_link("random"),
            method: "GET",
            children: [
                INPUT([
                    "type" => "search",
                    "name" => "search",
                    "value" => SearchTerm::implode($search_terms),
                    "placeholder" => "Search random list",
                    "class" => "autocomplete_tags"
                ]),
                INPUT([
                    "type" => "submit",
                    "value" => "Find",
                    "style" => "display: none;"
                ])
            ]
        ), 10);
    }
}
