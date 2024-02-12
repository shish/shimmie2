<?php

declare(strict_types=1);

namespace Shimmie2;

class RandomListTheme extends Themelet
{
    /** @var string[] */
    protected array $search_terms;

    /**
     * @param string[] $search_terms
     */
    public function set_page(array $search_terms): void
    {
        $this->search_terms = $search_terms;
    }

    /**
     * @param Image[] $images
     */
    public function display_page(Page $page, array $images): void
    {
        $page->title = "Random Posts";

        $html = "<b>Refresh the page to view more posts</b>";
        if (count($images)) {
            $html .= "<div class='shm-image-list'>";

            foreach ($images as $image) {
                $html .= $this->build_thumb_html($image);
            }

            $html .= "</div>";
        } else {
            $html .= "<br/><br/>No posts were found to match the search criteria";
        }

        $page->add_block(new Block("Random Posts", $html));

        $nav = $this->build_navigation($this->search_terms);
        $page->add_block(new Block("Navigation", $nav, "left", 0));
    }

    /**
     * @param string[] $search_terms
     */
    protected function build_navigation(array $search_terms): string
    {
        $h_search_string = html_escape(Tag::implode($search_terms));
        $h_search_link = make_link("random");
        $h_search = "
			<p><form action='$h_search_link' method='GET'>
				<input type='search' name='search' value='$h_search_string' placeholder='Search random list' class='autocomplete_tags' />
				<input type='hidden' name='q' value='random'>
				<input type='submit' value='Find' style='display: none;' />
			</form>
		";

        return $h_search;
    }
}
