<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{INPUT};

class Danbooru2ViewPostTheme extends ViewPostTheme
{
    /**
     * @param HTMLElement[] $editor_parts
     */
    public function display_page(Image $image, array $editor_parts): void
    {
        global $page;
        $page->set_heading($image->get_tag_list());
        $page->add_block(new Block("Search", $this->build_navigation($image), "left", 0));
        $page->add_block(new Block("Information", $this->build_stats($image), "left", 15));
        $page->add_block(new Block(null, $this->build_info($image, $editor_parts), "main", 15));
    }

    protected function build_navigation(Image $image): HTMLElement
    {
        return SHM_FORM(
            action: search_link(),
            method: 'GET',
            children: [
                INPUT([
                    "name" => 'search',
                    "type" => 'text',
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
}
