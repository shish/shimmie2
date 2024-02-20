<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\emptyHTML;
use function MicroHTML\{BR,H3,HR,P};

class IndexTheme extends Themelet
{
    protected int $page_number;
    protected int $total_pages;
    /** @var string[] */
    protected array $search_terms;

    /**
     * @param string[] $search_terms
     */
    public function set_page(int $page_number, int $total_pages, array $search_terms): void
    {
        $this->page_number = $page_number;
        $this->total_pages = $total_pages;
        $this->search_terms = $search_terms;
    }

    public function display_intro(Page $page): void
    {
        $text = "
<div style='text-align: left;'>
<p>The first thing you'll probably want to do is create a new account; note
that the first account you create will by default be marked as the board's
administrator, and any further accounts will be regular users.

<p>Once logged in you can play with the settings, install extra features,
and of course start organising your images :-)

<p>This message will go away once your first image is uploaded~
</div>
";
        $page->set_title("Welcome to Shimmie ".VERSION);
        $page->set_heading("Welcome to Shimmie");
        $page->add_block(new Block("Nothing here yet!", $text, "main", 0));
    }

    /**
     * @param Image[] $images
     */
    public function display_page(Page $page, array $images): void
    {
        $this->display_shortwiki($page);

        $this->display_page_header($page, $images);

        $nav = $this->build_navigation($this->page_number, $this->total_pages, $this->search_terms);
        $page->add_block(new Block("Navigation", $nav, "left", 0));

        if (count($images) > 0) {
            $this->display_page_images($page, $images);
        } else {
            $this->display_error(404, "No posts Found", "No posts were found to match the search criteria");
        }
    }

    /**
     * @param string[] $parts
     */
    public function display_admin_block(array $parts): void
    {
        global $page;
        $page->add_block(new Block("List Controls", join("<br>", $parts), "left", 50));
    }


    /**
     * @param string[] $search_terms
     */
    protected function build_navigation(int $page_number, int $total_pages, array $search_terms): string
    {
        $prev = $page_number - 1;
        $next = $page_number + 1;

        $h_prev = ($page_number <= 1) ? "Prev" : '<a href="'.search_link($search_terms, $prev).'">Prev</a>';
        $h_index = "<a href='".make_link()."'>Index</a>";
        $h_next = ($page_number >= $total_pages) ? "Next" : '<a href="'.search_link($search_terms, $next).'">Next</a>';

        $h_search_string = html_escape(Tag::implode($search_terms));
        $h_search_link = search_link();
        $h_search = "
			<p><form action='$h_search_link' method='GET'>
				<input type='search' name='search' value='$h_search_string' placeholder='Search' class='autocomplete_tags' />
				<input type='hidden' name='q' value='post/list'>
				<input type='submit' value='Find' style='display: none;' />
			</form>
		";

        return $h_prev.' | '.$h_index.' | '.$h_next.'<br>'.$h_search;
    }

    /**
     * @param Image[] $images
     */
    protected function build_table(array $images, ?string $query): string
    {
        $h_query = html_escape($query);
        $table = "<div class='shm-image-list' data-query='$h_query'>";
        foreach ($images as $image) {
            $table .= $this->build_thumb_html($image);
        }
        $table .= "</div>";
        return $table;
    }

    protected function display_shortwiki(Page $page): void
    {
        global $config;

        if (Extension::is_enabled(WikiInfo::KEY) && $config->get_bool(WikiConfig::TAG_SHORTWIKIS)) {
            if (count($this->search_terms) == 1) {
                $st = Tag::implode($this->search_terms);

                $wikiPage = Wiki::get_page($st);
                $short_wiki_description = '';
                if ($wikiPage->id != -1) {
                    // only show first line of wiki
                    $short_wiki_description = explode("\n", $wikiPage->body, 2)[0];

                    $tfe = send_event(new TextFormattingEvent($short_wiki_description));
                    $short_wiki_description = $tfe->formatted;
                }
                $wikiLink = make_link("wiki/$st");
                if (Extension::is_enabled(TagCategoriesInfo::KEY)) {
                    $tagcategories = new TagCategories();
                    $tag_category_dict = $tagcategories->getKeyedDict();
                    $st = $tagcategories->getTagHtml(html_escape($st), $tag_category_dict);
                }
                $short_wiki_description = '<h2>'.$st.'&nbsp;<a href="'.$wikiLink.'"><sup>ⓘ</sup></a></h2>'.$short_wiki_description;
                $page->add_block(new Block(null, $short_wiki_description, "main", 0, "short-wiki-description"));
            }
        }
    }

    /**
     * @param Image[] $images
     */
    protected function display_page_header(Page $page, array $images): void
    {
        global $config;

        if (count($this->search_terms) == 0) {
            $page_title = $config->get_string(SetupConfig::TITLE);
        } else {
            $search_string = implode(' ', $this->search_terms);
            $page_title = html_escape($search_string);
            if (count($images) > 0) {
                $page->set_subheading("Page {$this->page_number} / {$this->total_pages}");
            }
        }
        /*
        if ($this->page_number > 1 || count($this->search_terms) > 0) {
            $page_title .= " / $page_number";
        }
        */

        $page->set_title($page_title);
        $page->set_heading($page_title);
    }

    /**
     * @param Image[] $images
     */
    protected function display_page_images(Page $page, array $images): void
    {
        if (count($this->search_terms) > 0) {
            if ($this->page_number > 3) {
                // only index the first pages of each term
                $page->add_html_header('<meta name="robots" content="noindex, nofollow">');
            }
            $query = url_escape(Tag::implode($this->search_terms));
            $page->add_block(new Block("Posts", $this->build_table($images, "#search=$query"), "main", 10, "image-list"));
            $this->display_paginator($page, "post/list/$query", null, $this->page_number, $this->total_pages, true);
        } else {
            $page->add_block(new Block("Posts", $this->build_table($images, null), "main", 10, "image-list"));
            $this->display_paginator($page, "post/list", null, $this->page_number, $this->total_pages, true);
        }
    }

    public function get_help_html(): HTMLElement
    {
        return emptyHTML(
            H3("Tag Searching"),
            P("Searching is largely based on tags, with a number of special keywords available that allow searching based on properties of the posts."),
            SHM_COMMAND_EXAMPLE("tagname", 'Returns posts that are tagged with "tagname".'),
            SHM_COMMAND_EXAMPLE("tagname othertagname", 'Returns posts that are tagged with "tagname" and "othertagme".'),
            //
            BR(),
            P("Most tags and keywords can be prefaced with a negative sign (-) to indicate that you want to search for posts that do not match something."),
            SHM_COMMAND_EXAMPLE("-tagname", 'Returns posts that are not tagged with "tagname".'),
            SHM_COMMAND_EXAMPLE("-tagname -othertagname", 'Returns posts that are not tagged with "tagname" or "othertagname".'),
            SHM_COMMAND_EXAMPLE("tagname -othertagname", 'Returns posts that are tagged with "tagname", but are not tagged with "othertagname".'),
            //
            BR(),
            P('Wildcard searches are possible as well using * for "any one, more, or none" and ? for "any one".'),
            SHM_COMMAND_EXAMPLE("tag*", 'Returns posts that are tagged with "tag", "tags", "tagme", "tagname", or anything else that starts with "tag".'),
            SHM_COMMAND_EXAMPLE("*name", 'Returns posts that are tagged with "name", "tagname", "othertagname" or anything else that ends with "name".'),
            SHM_COMMAND_EXAMPLE("tagn?me", 'Returns posts that are tagged with "tagname", "tagnome", or anything else that starts with "tagn", then has one character, and ends with "me".'),
            //
            //
            //
            HR(),
            H3("Comparing values (<, <=, >, >=, or =)"),
            P("For example, you can use this to count tags."),
            SHM_COMMAND_EXAMPLE("tags=1", "Returns posts with exactly 1 tag."),
            SHM_COMMAND_EXAMPLE("tags>0", "Returns posts with 1 or more tags."),
            //
            BR(),
            P("Searching for posts by aspect ratio."),
            P("The relation is calculated as: width / height."),
            SHM_COMMAND_EXAMPLE("ratio=4:3", "Returns posts with an aspect ratio of 4:3."),
            SHM_COMMAND_EXAMPLE("ratio>16:9", "Returns posts with an aspect ratio greater than 16:9."),
            //
            BR(),
            P("Searching by dimentions."),
            SHM_COMMAND_EXAMPLE("size=640x480", "Returns posts exactly 640 pixels wide by 480 pixels high."),
            SHM_COMMAND_EXAMPLE("size>1920x1080", "Returns posts with a width larger than 1920 and a height larger than 1080."),
            SHM_COMMAND_EXAMPLE("width=1000", "Returns posts exactly 1000 pixels wide."),
            SHM_COMMAND_EXAMPLE("height=1000", "Returns posts exactly 1000 pixels high."),
            //
            BR(),
            P("Searching by file size."),
            P("Supported suffixes are kb, mb, and gb. Uses multiples of 1024."),
            SHM_COMMAND_EXAMPLE("filesize=1", "Returns posts exactly 1 byte in size"),
            SHM_COMMAND_EXAMPLE("filesize>100mb", "Returns posts greater than 100 megabytes in size."),
            //
            BR(),
            P("Searching by date posted."),
            P("Date format is yyyy-mm-dd. Date posted includes time component, so = will not work unless the time is exact."),
            SHM_COMMAND_EXAMPLE("posted>=2019-07-19", "Returns posts posted on or after 2019-07-19."),
            //
            BR(),
            P("Searching posts by media length."),
            P("Available suffixes are ms, s, m, h, d, and y. A number by itself will be interpreted as milliseconds. Searches using = are not likely to work unless time is specified down to the millisecond."),
            SHM_COMMAND_EXAMPLE("length>=1h", "Returns posts that are longer than an hour."),
            SHM_COMMAND_EXAMPLE("length<=10h15m", "Returns posts that are shorter than 10 hours and 15 minutes."),
            SHM_COMMAND_EXAMPLE("length>=10000", "Returns posts that are longer than 10,000 milliseconds, or 10 seconds."),
            //
            BR(),
            P("Searching posts by ID."),
            SHM_COMMAND_EXAMPLE("id=1234", "Find the 1234th thing uploaded."),
            SHM_COMMAND_EXAMPLE("id>1234", "Find more recently posted things."),
            //
            //
            //
            HR(),
            H3("Post attributes."),
            P("Searching by MD5 hash."),
            SHM_COMMAND_EXAMPLE("hash=0D3512CAA964B2BA5D7851AF5951F33B", "Returns post with MD5 hash 0D3512CAA964B2BA5D7851AF5951F33B."),
            //
            BR(),
            P("Searching by file name."),
            SHM_COMMAND_EXAMPLE("filename=picasso.jpg", 'Returns posts that are named "picasso.jpg".'),
            //
            BR(),
            P("Searching for posts by source."),
            SHM_COMMAND_EXAMPLE("source=https:///google.com/", 'Returns posts with a source of "https://google.com/".'),
            SHM_COMMAND_EXAMPLE("source=any", "Returns posts with a source set."),
            SHM_COMMAND_EXAMPLE("source=none", "Returns posts without a source set."),
            //
            //
            //
            HR(),
            H3("Sorting search results"),
            P("Sorting can be done using the pattern order:field_direction."),
            P("Supported fields: id, width, height, filesize, filename."),
            P("Direction can be either asc or desc, indicating ascending (123) or descending (321) order."),
            SHM_COMMAND_EXAMPLE("order:id_asc", "Returns posts sorted by ID, smallest first."),
            SHM_COMMAND_EXAMPLE("order:width_desc", "Returns posts sorted by width, largest first."),
        );
    }
}
