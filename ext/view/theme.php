<?php declare(strict_types=1);

class ViewImageTheme extends Themelet
{
    public function display_meta_headers(Image $image)
    {
        global $page;

        $h_metatags = str_replace(" ", ", ", html_escape($image->get_tag_list()));
        $page->add_html_header("<meta name=\"keywords\" content=\"$h_metatags\">");
        $page->add_html_header("<meta property=\"og:title\" content=\"$h_metatags\">");
        $page->add_html_header("<meta property=\"og:type\" content=\"article\">");
        $page->add_html_header("<meta property=\"og:image\" content=\"".make_http($image->get_thumb_link())."\">");
        $page->add_html_header("<meta property=\"og:url\" content=\"".make_http(make_link("post/view/{$image->id}"))."\">");
    }

    /*
     * Build a page showing $image and some info about it
     */
    public function display_page(Image $image, $editor_parts)
    {
        global $page;
        $page->set_title("Post {$image->id}: ".$image->get_tag_list());
        $page->set_heading(html_escape($image->get_tag_list()));
        $page->add_block(new Block("Navigation", $this->build_navigation($image), "left", 0));
        $page->add_block(new Block(null, $this->build_info($image, $editor_parts), "main", 20, "ImageInfo"));
        //$page->add_block(new Block(null, $this->build_pin($image), "main", 11));

        $query = $this->get_query();
        $page->add_html_header("<link id='nextlink' rel='next' href='".make_link("post/next/{$image->id}", $query)."'>");
        $page->add_html_header("<link id='prevlink' rel='previous' href='".make_link("post/prev/{$image->id}", $query)."'>");
    }

    public function display_admin_block(Page $page, $parts)
    {
        if (count($parts) > 0) {
            $page->add_block(new Block("Post Controls", join("<br>", $parts), "left", 50));
        }
    }

    protected function get_query(): ?string
    {
        if (isset($_GET['search'])) {
            $query = "search=".url_escape(Tag::caret($_GET['search']));
        } else {
            $query = null;
        }
        return $query;
    }

    protected function build_pin(Image $image): string
    {
        $query = $this->get_query();
        $h_prev = "<a id='prevlink' href='".make_link("post/prev/{$image->id}", $query)."'>Prev</a>";
        $h_index = "<a href='".make_link()."'>Index</a>";
        $h_next = "<a id='nextlink' href='".make_link("post/next/{$image->id}", $query)."'>Next</a>";

        return "$h_prev | $h_index | $h_next";
    }

    protected function build_navigation(Image $image): string
    {
        $h_pin = $this->build_pin($image);
        $h_search = "
			<p><form action='".make_link()."' method='GET'>
				<input type='hidden' name='q' value='/post/list'>
				<input type='search' name='search' placeholder='Search' class='autocomplete_tags' autocomplete='off'>
				<input type='submit' value='Find' style='display: none;'>
			</form>
		";

        return "$h_pin<br>$h_search";
    }

    protected function build_info(Image $image, $editor_parts): string
    {
        global $user;

        if (count($editor_parts) == 0) {
            return ($image->is_locked() ? "<br>[Post Locked]" : "");
        }

        $html = make_form(make_link("post/set"))."
					<input type='hidden' name='image_id' value='{$image->id}'>
					<table style='width: 500px; max-width: 100%;' class='image_info form'>
		";
        foreach ($editor_parts as $part) {
            $html .= $part;
        }
        if (
            (!$image->is_locked() || $user->can(Permissions::EDIT_IMAGE_LOCK)) &&
            $user->can(Permissions::EDIT_IMAGE_TAG)
        ) {
            $html .= "
						<tr><td colspan='4'>
							<input class='view' type='button' value='Edit' onclick='$(\".view\").hide(); $(\".edit\").show();'>
							<input class='edit' type='submit' value='Set'>
						</td></tr>
			";
        }
        $html .= "
					</table>
				</form>
		";
        return $html;
    }
}
