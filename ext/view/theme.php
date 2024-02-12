<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{A, joinHTML, TABLE, TR, TD, INPUT, emptyHTML, DIV, BR};

class ViewPostTheme extends Themelet
{
    public function display_meta_headers(Image $image): void
    {
        global $page;

        $h_metatags = str_replace(" ", ", ", html_escape($image->get_tag_list()));
        $page->add_html_header("<meta name=\"keywords\" content=\"$h_metatags\">");
        $page->add_html_header("<meta property=\"og:title\" content=\"$h_metatags\">");
        $page->add_html_header("<meta property=\"og:type\" content=\"article\">");
        $page->add_html_header("<meta property=\"og:image\" content=\"".make_http($image->get_thumb_link())."\">");
        $page->add_html_header("<meta property=\"og:url\" content=\"".make_http(make_link("post/view/{$image->id}"))."\">");
    }

    /**
     * Build a page showing $image and some info about it
     *
     * @param HTMLElement[] $editor_parts
     */
    public function display_page(Image $image, array $editor_parts): void
    {
        global $page;
        $page->set_title("Post {$image->id}: ".$image->get_tag_list());
        $page->set_heading(html_escape($image->get_tag_list()));
        $page->add_block(new Block("Post {$image->id}", $this->build_navigation($image), "left", 0, "Navigationleft"));
        $page->add_block(new Block(null, $this->build_info($image, $editor_parts), "main", 20, "ImageInfo"));
        //$page->add_block(new Block(null, $this->build_pin($image), "main", 11));

        $query = $this->get_query();
        if(!$this->is_ordered_search()) {
            $page->add_html_header("<link id='nextlink' rel='next' href='".make_link("post/next/{$image->id}", $query)."'>");
            $page->add_html_header("<link id='prevlink' rel='previous' href='".make_link("post/prev/{$image->id}", $query)."'>");
        }
    }

    /**
     * @param HTMLElement[] $parts
     */
    public function display_admin_block(Page $page, array $parts): void
    {
        if (count($parts) > 0) {
            $page->add_block(new Block("Post Controls", DIV(["class" => "post_controls"], joinHTML("", $parts)), "left", 50));
        }
    }

    protected function get_query(): ?string
    {
        if (isset($_GET['search'])) {
            $query = "search=".url_escape($_GET['search']);
        } else {
            $query = null;
        }
        return $query;
    }

    /**
     * prev/next only work for default-ordering searches - if the user
     * has specified a custom order, we can't show prev/next.
     */
    protected function is_ordered_search(): bool
    {
        if(isset($_GET['search'])) {
            $tags = Tag::explode($_GET['search']);
            foreach($tags as $tag) {
                if(preg_match("/^order[=:]/", $tag) == 1) {
                    return true;
                }
            }
        }
        return false;
    }

    protected function build_pin(Image $image): HTMLElement
    {
        $query = $this->get_query();
        if($this->is_ordered_search()) {
            return A(["href" => make_link()], "Index");
        } else {
            return joinHTML(" | ", [
                A(["href" => make_link("post/prev/{$image->id}", $query), "id" => "prevlink"], "Prev"),
                A(["href" => make_link()], "Index"),
                A(["href" => make_link("post/next/{$image->id}", $query), "id" => "nextlink"], "Next"),
            ]);
        }
    }

    protected function build_navigation(Image $image): string
    {
        $h_pin = $this->build_pin($image);
        $h_search = "
			<p><form action='".search_link()."' method='GET'>
				<input type='hidden' name='q' value='post/list'>
				<input type='search' name='search' placeholder='Search' class='autocomplete_tags'>
				<input type='submit' value='Find' style='display: none;'>
			</form>
		";

        return "$h_pin<br>$h_search";
    }

    /**
     * @param HTMLElement[] $editor_parts
     */
    protected function build_info(Image $image, array $editor_parts): HTMLElement
    {
        global $user;

        if (count($editor_parts) == 0) {
            return emptyHTML($image->is_locked() ? "[Post Locked]" : "");
        }

        if(
            (!$image->is_locked() || $user->can(Permissions::EDIT_IMAGE_LOCK)) &&
            $user->can(Permissions::EDIT_IMAGE_TAG)
        ) {
            $editor_parts[] = TR(TD(
                ["colspan" => 4],
                INPUT(["class" => "view", "type" => "button", "value" => "Edit", "onclick" => "clearViewMode()"]),
                INPUT(["class" => "edit", "type" => "submit", "value" => "Set"])
            ));
        }

        return SHM_SIMPLE_FORM(
            "post/set",
            INPUT(["type" => "hidden", "name" => "image_id", "value" => $image->id]),
            TABLE(
                [
                    "class" => "image_info form",
                ],
                ...$editor_parts,
            ),
        );
    }
}
