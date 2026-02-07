<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\LINK;

final class BrowserSearch extends Extension
{
    public const KEY = "browser_search";

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        global $database;

        // Add in header code to let the browser know that the search plugin exists
        // We need to build the data for the header
        $search_title = Ctx::$config->get(SetupConfig::TITLE);
        Ctx::$page->add_html_header(LINK([
            'rel' => 'search',
            'type' => 'application/opensearchdescription+xml',
            'title' => $search_title,
            'href' => make_link('browser_search.xml')
        ]));

        // The search.xml file that is generated on the fly
        if ($event->page_matches("browser_search.xml")) {
            // First, we need to build all the variables we'll need
            $search_title = Ctx::$config->get(SetupConfig::TITLE);
            $search_form_url =  search_link(['{searchTerms}']);
            $suggenton_url = make_link('browser_search/')."{searchTerms}";
            $icon_b64 = base64_encode(\Safe\file_get_contents("ext/static_files/static/favicon.ico"));

            // Now for the XML
            $xml = "
				<SearchPlugin xmlns='http://www.mozilla.org/2006/browser/search/' xmlns:os='http://a9.com/-/spec/opensearch/1.1/'>
				<os:ShortName>$search_title</os:ShortName>
				<os:InputEncoding>UTF-8</os:InputEncoding>
				<os:Image width='16' height='16'>data:image/x-icon;base64,$icon_b64</os:Image>
				<SearchForm>$search_form_url</SearchForm>
				<os:Url type='text/html' method='GET' template='$search_form_url'>
				  <os:Param name='search' value='{searchTerms}'/>
				</os:Url>
				<Url type='application/x-suggestions+json' template='$suggenton_url'/>
				</SearchPlugin>
			";

            // And now to send it to the browser
            Ctx::$page->set_data(MimeType::XML, $xml);
        } elseif ($event->page_matches("browser_search/{tag_search}")) {
            $suggestions = Ctx::$config->get(BrowserSearchConfig::RESULTS_ORDER);
            if ($suggestions === "n") {
                return;
            }

            // We have to build some json stuff
            $tag_search = $event->get_arg('tag_search');

            // Now to get DB results
            if ($suggestions === "a") {
                $order = "tag ASC";
            } else {
                $order = "count DESC";
            }
            $tags = $database->get_col(
                "SELECT tag FROM tags WHERE SCORE_ILIKE(tag, :tag) AND count > 0 ORDER BY $order LIMIT 30",
                ['tag' => Tag::sqlify($tag_search . "*")]
            );

            // And to do stuff with it. We want our output to look like:
            // ["shimmie",["shimmies","shimmy","shimmie","21 shimmies","hip shimmies","skea shimmies"],[],[]]
            Ctx::$page->set_data(MimeType::JSON, \Safe\json_encode([$tag_search, $tags, [], []]));
        }
    }
}
