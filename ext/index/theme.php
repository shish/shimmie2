<?php declare(strict_types=1);

class IndexTheme extends Themelet
{
    protected $page_number;
    protected $total_pages;
    protected $search_terms;

    public function set_page(int $page_number, int $total_pages, array $search_terms)
    {
        $this->page_number = $page_number;
        $this->total_pages = $total_pages;
        $this->search_terms = $search_terms;
    }

    public function display_intro(Page $page)
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
     * #param Image[] $images
     */
    public function display_page(Page $page, array $images)
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
     * #param string[] $parts
     */
    public function display_admin_block(array $parts)
    {
        global $page;
        $page->add_block(new Block("List Controls", join("<br>", $parts), "left", 50));
    }


    /**
     * #param string[] $search_terms
     */
    protected function build_navigation(int $page_number, int $total_pages, array $search_terms): string
    {
        $prev = $page_number - 1;
        $next = $page_number + 1;

        $u_tags = url_escape(Tag::implode($search_terms));
        $query = empty($u_tags) ? "" : '/'.$u_tags;


        $h_prev = ($page_number <= 1) ? "Prev" : '<a href="'.make_link('post/list'.$query.'/'.$prev).'">Prev</a>';
        $h_index = "<a href='".make_link()."'>Index</a>";
        $h_next = ($page_number >= $total_pages) ? "Next" : '<a href="'.make_link('post/list'.$query.'/'.$next).'">Next</a>';

        $h_search_string = html_escape(Tag::implode($search_terms));
        $h_search_link = make_link();
        $h_search = "
			<p><form action='$h_search_link' method='GET'>
				<input type='search' name='search' value='$h_search_string' placeholder='Search' class='autocomplete_tags' autocomplete='off' />
				<input type='hidden' name='q' value='/post/list'>
				<input type='submit' value='Find' style='display: none;' />
			</form>
		";

        return $h_prev.' | '.$h_index.' | '.$h_next.'<br>'.$h_search;
    }

    /**
     * #param Image[] $images
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

    protected function display_shortwiki(Page $page)
    {
        global $config;

        if (class_exists('Wiki') && $config->get_bool(WikiConfig::TAG_SHORTWIKIS)) {
            if (count($this->search_terms) == 1) {
                $st = Tag::implode($this->search_terms);

                $wikiPage = Wiki::get_page($st);
                $short_wiki_description = '';
                if ($wikiPage->id != -1) {
                    // only show first line of wiki
                    $short_wiki_description = explode("\n", $wikiPage->body, 2)[0];

                    $tfe = new TextFormattingEvent($short_wiki_description);
                    send_event($tfe);
                    $short_wiki_description = $tfe->formatted;
                }
                $wikiLink = make_link("wiki/$st");
                if (class_exists('TagCategories')) {
                    $this->tagcategories = new TagCategories;
                    $tag_category_dict = $this->tagcategories->getKeyedDict();
                    $st = $this->tagcategories->getTagHtml(html_escape($st), $tag_category_dict);
                }
                $short_wiki_description = '<h2>'.$st.'&nbsp;<a href="'.$wikiLink.'"><sup>ⓘ</sup></a></h2>'.$short_wiki_description;
                $page->add_block(new Block(null, $short_wiki_description, "main", 0, "short-wiki-description"));
            }
        }
    }

    /**
     * #param Image[] $images
     */
    protected function display_page_header(Page $page, array $images)
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
     * #param Image[] $images
     */
    protected function display_page_images(Page $page, array $images)
    {
        if (count($this->search_terms) > 0) {
            if ($this->page_number > 3) {
                // only index the first pages of each term
                $page->add_html_header('<meta name="robots" content="noindex, nofollow">');
            }
            $query = url_escape(Tag::caret(Tag::implode($this->search_terms)));
            $page->add_block(new Block("Images", $this->build_table($images, "#search=$query"), "main", 10, "image-list"));
            $this->display_paginator($page, "post/list/$query", null, $this->page_number, $this->total_pages, true);
        } else {
            $page->add_block(new Block("Images", $this->build_table($images, null), "main", 10, "image-list"));
            $this->display_paginator($page, "post/list", null, $this->page_number, $this->total_pages, true);
        }
    }

    public function get_help_html()
    {
        return '<p>Searching is largely based on tags, with a number of special keywords available that allow searching based on properties of the posts.</p>

        <div class="command_example">
        <pre>tagname</pre>
        <p>Returns posts that are tagged with "tagname".</p>
        </div>

        <div class="command_example">
        <pre>tagname othertagname</pre>
        <p>Returns posts that are tagged with "tagname" and "othertagname".</p>
        </div>

        <p>Most tags and keywords can be prefaced with a negative sign (-) to indicate that you want to search for posts that do not match something.</p>

        <div class="command_example">
        <pre>-tagname</pre>
        <p>Returns posts that are not tagged with "tagname".</p>
        </div>

        <div class="command_example">
        <pre>-tagname -othertagname</pre>
        <p>Returns posts that are not tagged with "tagname" and "othertagname". This is different than without the negative sign, as posts with "tagname" or "othertagname" can still be returned as long as the other one is not present.</p>
        </div>

        <div class="command_example">
        <pre>tagname -othertagname</pre>
        <p>Returns posts that are tagged with "tagname", but are not tagged with "othertagname".</p>
        </div>

        <p>Wildcard searches are possible as well using * for "any one, more, or none" and ? for "any one".</p>

        <div class="command_example">
        <pre>tagn*</pre>
        <p>Returns posts that are tagged with "tagname", "tagnot", or anything else that starts with "tagn".</p>
        </div>

        <div class="command_example">
        <pre>tagn?me</pre>
        <p>Returns posts that are tagged with "tagname", "tagnome", or anything else that starts with "tagn", has one character, and ends with "me".</p>
        </div>

        <div class="command_example">
        <pre>tags=1</pre>
        <p>Returns posts with exactly 1 tag.</p>
        </div>

        <div class="command_example">
        <pre>tags>0</pre>
        <p>Returns posts with 1 or more tags. </p>
        </div>

        <p>Can use &lt;, &lt;=, &gt;, &gt;=, or =.</p>

        <hr/>

        <p>Search for posts by aspect ratio</p>

        <div class="command_example">
        <pre>ratio=4:3</pre>
        <p>Returns posts with an aspect ratio of 4:3.</p>
        </div>

        <div class="command_example">
        <pre>ratio>16:9</pre>
        <p>Returns posts with an aspect ratio greater than 16:9. </p>
        </div>

        <p>Can use &lt;, &lt;=, &gt;, &gt;=, or =. The relation is calculated by dividing width by height.</p>

        <hr/>

        <p>Search for posts by file size</p>

        <div class="command_example">
        <pre>filesize=1</pre>
        <p>Returns posts exactly 1 byte in size.</p>
        </div>

        <div class="command_example">
        <pre>filesize>100mb</pre>
        <p>Returns posts greater than 100 megabytes in size. </p>
        </div>

        <p>Can use &lt;, &lt;=, &gt;, &gt;=, or =. Supported suffixes are kb, mb, and gb. Uses multiples of 1024.</p>

        <hr/>

        <p>Search for posts by MD5 hash</p>

        <div class="command_example">
        <pre>hash=0D3512CAA964B2BA5D7851AF5951F33B</pre>
        <p>Returns image with an MD5 hash 0D3512CAA964B2BA5D7851AF5951F33B.</p>
        </div>

        <hr/>

        <p>Search for posts by file name</p>

        <div class="command_example">
        <pre>filename=picasso.jpg</pre>
        <p>Returns posts that are named "picasso.jpg".</p>
        </div>

        <hr/>

        <p>Search for posts by source</p>

        <div class="command_example">
        <pre>source=http://google.com/</pre>
        <p>Returns posts with a source of "http://google.com/".</p>
        </div>

        <div class="command_example">
        <pre>source=any</pre>
        <p>Returns posts with a source set.</p>
        </div>

        <div class="command_example">
        <pre>source=none</pre>
        <p>Returns posts without a source set.</p>
        </div>

        <hr/>

        <p>Search for posts by date posted.</p>

        <div class="command_example">
        <pre>posted>=07-19-2019</pre>
        <p>Returns posts posted on or after 07-19-2019.</p>
        </div>

        <p>Can use &lt;, &lt;=, &gt;, &gt;=, or =. Date format is mm-dd-yyyy. Date posted includes time component, so = will not work unless the time is exact.</p>

        <hr/>

        <p>Search for posts by length.</p>

        <div class="command_example">
        <pre>length>=1h</pre>
        <p>Returns posts that are longer than an hour.</p>
        </div>

        <div class="command_example">
        <pre>length<=10h15m</pre>
        <p>Returns posts that are shorter than 10 hours and 15 minutes.</p>
        </div>

        <div class="command_example">
        <pre>length>=10000</pre>
        <p>Returns posts that are longer than 10,000 milliseconds, or 10 seconds.</p>
        </div>

        <p>Can use &lt;, &lt;=, &gt;, &gt;=, or =. Available suffixes are ms, s, m, h, d, and y. A number by itself will be interpreted as milliseconds. Searches using = are not likely to work unless time is specified down to the millisecond.</p>

        <hr/>

        <p>Search for posts by dimensions</p>

        <div class="command_example">
        <pre>size=640x480</pre>
        <p>Returns posts exactly 640 pixels wide by 480 pixels high.</p>
        </div>

        <div class="command_example">
        <pre>size>1920x1080</pre>
        <p>Returns posts with a width larger than 1920 and a height larger than 1080.</p>
        </div>

        <div class="command_example">
        <pre>width=1000</pre>
        <p>Returns posts exactly 1000 pixels wide.</p>
        </div>

        <div class="command_example">
        <pre>height=1000</pre>
        <p>Returns posts exactly 1000 pixels high.</p>
        </div>

        <p>Can use &lt;, &lt;=, &gt;, &gt;=, or =.</p>

        <hr/>

        <p>Sorting search results can be done using the pattern order:field_direction. _direction can be either _asc or _desc, indicating ascending (123) or descending (321) order.</p>

        <div class="command_example">
        <pre>order:id_asc</pre>
        <p>Returns posts sorted by ID, smallest first.</p>
        </div>

        <div class="command_example">
        <pre>order:width_desc</pre>
        <p>Returns posts sorted by width, largest first.</p>
        </div>

        <p>These fields are supported:
            <ul>
            <li>id</li>
            <li>width</li>
            <li>height</li>
            <li>filesize</li>
            <li>filename</li>
            </ul>
        </p>
        ';
    }
}
