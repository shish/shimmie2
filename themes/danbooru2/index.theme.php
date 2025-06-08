<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\DIV;

use MicroHTML\HTMLElement;

use function MicroHTML\{INPUT,P};

class Danbooru2IndexTheme extends IndexTheme
{
    /**
     * @param Image[] $images
     */
    public function display_page(array $images): void
    {
        $this->display_shortwiki();

        $this->display_page_header($images);

        $nav = $this->build_navigation($this->page_number, $this->total_pages, $this->search_terms);
        Ctx::$page->add_block(new Block("Search", $nav, "left", 0));

        if (count($images) > 0) {
            $this->display_page_images($images);
        } else {
            throw new PostNotFound("No posts were found to match the search criteria");
        }
    }

    /**
     * @param search-term-array $search_terms
     */
    protected function build_navigation(int $page_number, int $total_pages, array $search_terms): HTMLElement
    {
        return SHM_FORM(
            action: search_link(),
            method: 'GET',
            children: [
                P(),
                INPUT([
                    "name" => 'search',
                    "type" => 'text',
                    "value" => SearchTerm::implode($search_terms),
                    "class" => 'autocomplete_tags',
                    "style" => 'width:75%'
                ]),
                INPUT([
                    "type" => 'submit',
                    "value" => 'Go',
                    "style" => 'width:20%'
                ]),
            ]
        );
    }

    /**
     * @param Image[] $images
     */
    protected function build_table(array $images, ?string $query): HTMLElement
    {
        $table = DIV(["class" => "shm-image-list", "data-query" => $query]);
        foreach ($images as $image) {
            $table->appendChild($this->build_thumb($image));
        }
        return $table;
    }
}
