<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, BR, DIV, H2, H3, HR, INPUT, META, P, SPAN, SUP, emptyHTML};

use MicroHTML\HTMLElement;

class IndexTheme extends Themelet
{
    protected int $page_number;
    protected int $total_pages;
    /** @var search-term-array */
    protected array $search_terms;

    /**
     * @param search-term-array $search_terms
     */
    public function set_page(int $page_number, int $total_pages, array $search_terms): void
    {
        $this->page_number = $page_number;
        $this->total_pages = $total_pages;
        $this->search_terms = $search_terms;
    }

    public function display_intro(): void
    {
        $text = DIV(
            ["class" => "prose"],
            P("The first thing you'll probably want to do is create a new account; note
         that the first account you create will by default be marked as the board's
         administrator, and any further accounts will be regular users."),
            P("Once logged in you can play with the settings, install extra features,
         and of course start organising your images :-)"),
            P("This message will go away once your first image is uploaded~"),
        );
        Ctx::$page->set_title("Welcome to Shimmie ".SysConfig::getVersion(false));
        Ctx::$page->set_heading("Welcome to Shimmie");
        Ctx::$page->add_block(new Block("Nothing here yet!", $text, "main", 0));
    }

    public function build_search(): HTMLElement|string
    {
        return SHM_FORM(
            action: search_link(),
            method: "GET",
            children: [
                P(),
                INPUT([
                    "type" => "search",
                    "name" => "search",
                    "value" => SearchTerm::implode($this->search_terms),
                    "placeholder" => "Search",
                    "class" => "autocomplete_tags"
                ]),
                INPUT([
                    "type" => "submit",
                    "value" => "Find",
                    "style" => "display: none;"
                ])
            ],
        );
    }

    /**
     * @param Image[] $images
     */
    public function display_page(array $images): void
    {
        $this->display_shortwiki();

        $this->display_page_header($images);

        Ctx::$page->set_navigation(
            ($this->page_number <= 1) ? null : search_link($this->search_terms, $this->page_number - 1),
            ($this->page_number >= $this->total_pages) ? null : search_link($this->search_terms, $this->page_number + 1),
        );

        Ctx::$page->add_to_navigation($this->build_search(), 10);

        if (count($images) > 0) {
            $this->display_page_images($images);
        } else {
            $this->display_none_found();
        }
    }

    /**
     * @param Image[] $images
     */
    protected function build_table(array $images, ?string $query): HTMLElement
    {
        $thumbs = array_map(fn ($image) => $this->build_thumb($image), $images);
        return DIV(["class" => "shm-image-list", "data-query" => $query], ...$thumbs);
    }

    protected function display_shortwiki(): void
    {
        if (WikiInfo::is_enabled() && Ctx::$config->get(WikiConfig::TAG_SHORTWIKIS)) {
            if (count($this->search_terms) === 1) {
                $st = SearchTerm::implode($this->search_terms);
                $wikiPage = Wiki::get_page($st);
                if ($wikiPage->id !== -1) {
                    if (TagCategoriesInfo::is_enabled()) {
                        $st = TagCategories::getTagHtml($st);
                    }
                    $short_wiki_description = emptyHTML(
                        H2($st, " ", A(["href" => make_link("wiki/$st")], SUP("ⓘ"))),
                        format_text(explode("\n", $wikiPage->body, 2)[0])
                    );
                    Ctx::$page->add_block(new Block(null, $short_wiki_description, "main", 0, "short-wiki-description"));
                }
            }
        }
    }

    /**
     * @param Image[] $images
     */
    protected function display_page_header(array $images): void
    {
        if (count($this->search_terms) === 0) {
            $page_title = Ctx::$config->get(SetupConfig::TITLE);
        } else {
            $page_title = implode(' ', $this->search_terms);
            if (count($images) > 0) {
                Ctx::$page->set_subheading("Page {$this->page_number} / {$this->total_pages}");
            }
        }
        /*
        if ($this->page_number > 1 || count($this->search_terms) > 0) {
            $page_title .= " / $page_number";
        }
        */

        Ctx::$page->set_title($page_title);
    }

    /**
     * @param Image[] $images
     */
    protected function display_page_images(array $images): void
    {
        if (count($this->search_terms) > 0) {
            if ($this->page_number > 3) {
                // only index the first pages of each term
                Ctx::$page->add_html_header(META(["name" => "robots", "content" => "noindex, nofollow"]));
            }
            $query = url_escape(SearchTerm::implode($this->search_terms));
            Ctx::$page->add_block(new Block(null, $this->build_table($images, "search=$query"), "main", 10, "image-list"));
            $this->display_paginator("post/list/$query", null, $this->page_number, $this->total_pages, true);
        } else {
            Ctx::$page->add_block(new Block(null, $this->build_table($images, null), "main", 10, "image-list"));
            $this->display_paginator("post/list", null, $this->page_number, $this->total_pages, true);
        }
    }

    protected function display_none_found(): void
    {
        Ctx::$page->add_block(new Block(null, emptyHTML(
            SPAN(
                "No posts were found to match the search criteria, ",
                A(
                    ["href" => Url::referer_or(make_link("post/list"))],
                    "go back"
                )
            )
        )));
    }

    public function get_help_html(): HTMLElement
    {
        return emptyHTML(
            H3("Tag Searching"),
            P("Searching is largely based on tags, with a number of special keywords available that allow searching based on properties of the posts."),
            SHM_COMMAND_EXAMPLE("tag_name", 'Returns posts that are tagged with "tag_name".'),
            SHM_COMMAND_EXAMPLE("tag_name other_tag_name", 'Returns posts that are tagged with "tag_name" and "other_tag_name".'),
            //
            BR(),
            P("Most tags and keywords can be prefaced with a negative sign (-) to indicate that you want to search for posts that do not match something."),
            SHM_COMMAND_EXAMPLE("-tag_name", 'Returns posts that are not tagged with "tagname".'),
            SHM_COMMAND_EXAMPLE("-tag_name -other_tag_name", 'Returns posts that are not tagged with "tag_name" or "other_tag_name".'),
            SHM_COMMAND_EXAMPLE("tag_name -other_tag_name", 'Returns posts that are tagged with "tag_name", but are not tagged with "other_tag_name".'),
            //
            BR(),
            P('Wildcard searches are possible as well using *.'),
            SHM_COMMAND_EXAMPLE("tag*", 'Returns posts that are tagged with "tag", "tags", "tagme", "tag_name", or anything else that starts with "tag".'),
            SHM_COMMAND_EXAMPLE("*name", 'Returns posts that are tagged with "name", "tag_name", "other_tag_name" or anything else that ends with "name".'),
            //
            HR(),
            H3("Comparing values (<, <=, >, >=, or =)"),
            P("For example, you can use this to count tags."),
            SHM_COMMAND_EXAMPLE("tags=1", "Returns posts with exactly 1 tag."),
            SHM_COMMAND_EXAMPLE("tags>0", "Returns posts with 1 or more tags."),
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
            P("Searching posts by ID."),
            SHM_COMMAND_EXAMPLE("id=1234", "Find the 1234th thing uploaded."),
            SHM_COMMAND_EXAMPLE("id>1234", "Find more recently posted things."),
            //
            HR(),
            H3("Post attributes."),
            P("Searching by MD5 hash."),
            SHM_COMMAND_EXAMPLE("hash=0D3512CAA964B2BA5D7851AF5951F33B", "Returns post with MD5 hash 0D3512CAA964B2BA5D7851AF5951F33B."),
            SHM_COMMAND_EXAMPLE("md5=0D3512CAA964B2BA5D7851AF5951F33B", "Same as above."),
            //
            BR(),
            P("Searching by file name."),
            SHM_COMMAND_EXAMPLE("filename=picasso.jpg", 'Returns posts that are named "picasso.jpg".'),
            //
            BR(),
            P("Searching for posts by source."),
            SHM_COMMAND_EXAMPLE("source=https://google.com/", 'Returns posts with a source of "https://google.com/".'),
            SHM_COMMAND_EXAMPLE("source=any", "Returns posts with a source set."),
            SHM_COMMAND_EXAMPLE("source=none", "Returns posts without a source set."),
            //
            HR(),
            H3("Sorting search results"),
            P("Sorting can be done using the pattern order:field_direction."),
            P("Supported fields: id, width, height, filesize, filename."),
            P("Direction can be either asc or desc, indicating ascending (123) or descending (321) order."),
            SHM_COMMAND_EXAMPLE("order=id_asc", "Returns posts sorted by ID, smallest first."),
            SHM_COMMAND_EXAMPLE("order=width_desc", "Returns posts sorted by width, largest first."),
        );
    }
}
