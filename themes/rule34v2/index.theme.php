<?php

declare(strict_types=1);

namespace Shimmie2;

class CustomIndexTheme extends IndexTheme
{
    protected function build_table(array $images, ?string $query): string
    {
        global $user;

        $candel = $user->can("delete_image") ? "can-del" : "";
        $h_query = html_escape($query);

        $table = "<div class='shm-image-list $candel' data-query='$h_query'>";
        foreach ($images as $image) {
            $table .= $this->build_thumb_html($image);
        }
        $table .= "</div>";
        return $table;
    }

    public function display_page(Page $page, $images)
    {
        $this->display_page_header($page, $images);

        $nav = $this->build_navigation($this->page_number, $this->total_pages, $this->search_terms);
        if (!empty($this->search_terms)) {
            $page->_search_query = $this->search_terms;
        }
        $page->add_block(new Block("Navigation", $nav, "left", 0));

        if (count($images) > 0) {
            $this->display_page_images($page, $images);
        } else {
            $this->display_error(
                404,
                "No Posts Found",
                "No images were found to match the search criteria. Try looking up a character/series/artist by another name if they go by more than one. Remember to use underscores in place of spaces and not to use commas. If you came to this page by following a link, try using the search box directly instead. See the FAQ for more information."
            );
        }
    }
}
