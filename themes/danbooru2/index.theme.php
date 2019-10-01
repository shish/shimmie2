<?php

class CustomIndexTheme extends IndexTheme
{
    /**
     * #param Image[] $images
     */
    public function display_page(Page $page, array $images)
    {
        $this->display_page_header($page, $images);

        $nav = $this->build_navigation($this->page_number, $this->total_pages, $this->search_terms);
        $page->add_block(new Block("Search", $nav, "left", 0));

        if (count($images) > 0) {
            $this->display_page_images($page, $images);
        } else {
            $this->display_error(404, "No Images Found", "No images were found to match the search criteria");
        }
    }

    /**
     * #param string[] $search_terms
     */
    protected function build_navigation(int $page_number, int $total_pages, array $search_terms): string
    {
        $h_search_string = count($search_terms) == 0 ? "" : html_escape(implode(" ", $search_terms));
        $h_search_link = make_link();
        $h_search = "
			<p><form action='$h_search_link' method='GET'>
				<input name='search' type='text' value='$h_search_string' class='autocomplete_tags' placeholder=''  style='width:75%'/>
				<input type='submit' value='Go' style='width:20%'>
				<input type='hidden' name='q' value='/post/list'>
			</form>
			<div id='search_completions'></div>";

        return $h_search;
    }

    /**
     * #param Image[] $images
     */
    protected function build_table(array $images, ?string $query): string
    {
        $h_query = html_escape($query);
        $table = "<div class='shm-image-list' data-query='$h_query'>";
        foreach ($images as $image) {
            $table .= "\t<span class=\"thumb\">" . $this->build_thumb_html($image) . "</span>\n";
        }
        $table .= "</div>";
        return $table;
    }
}
