<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{rawHTML, INPUT};

class RandomListTheme extends Themelet
{
    /**
     * @param string[] $search_terms
     * @param Image[] $images
     */
    public function display_page(Page $page, array $search_terms, array $images): void
    {
        $page->title = "Random Posts";

        $html = "<b>Refresh the page to view more posts</b>";
        if (count($images)) {
            $html .= "<div class='shm-image-list'>";

            foreach ($images as $image) {
                $html .= $this->build_thumb($image);
            }

            $html .= "</div>";
        } else {
            $html .= "<br/><br/>No posts were found to match the search criteria";
        }

        $page->add_block(new Block("Random Posts", rawHTML($html)));

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
