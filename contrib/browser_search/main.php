<?php
/*
 * Name: Browser Search
 * Author: ATravelingGeek <atg@atravelinggeek.com>
 * Some code (and lots of help) by Artanis (Erik Youngren <artanis.00@gmail.com>) from the 'tagger' extention - Used with permission
 * Link: http://atravelinggeek.com/
 * License: GPLv2
 * Description: Allows the user to add a browser 'plugin' to search the site with real-time suggestions
 * Version: 0.1c, October 26, 2007
 * Documentation:
 *  Once installed, users with an opensearch compatible browser should see
 *  their search box light up with whatever "click here to add a search
 *  engine" notification they have
 */

class BrowserSearch extends Extension {
	public function onInitExt(InitExtEvent $event) {
		global $config;
		$config->set_default_string("search_suggestions_results_order", 'a');
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $config, $database, $page;

		// Add in header code to let the browser know that the search plugin exists
		// We need to build the data for the header
		$search_title = $config->get_string('title');
		$search_file_url = make_link('browser_search/please_dont_use_this_tag_as_it_would_break_stuff__search.xml');
		$page->add_html_header("<link rel='search' type='application/opensearchdescription+xml' title='$search_title' href='$search_file_url'>");

		// The search.xml file that is generated on the fly
		if($event->page_matches("browser_search/please_dont_use_this_tag_as_it_would_break_stuff__search.xml")) {
			// First, we need to build all the variables we'll need
			$search_title = $config->get_string('title');
			$search_form_url =  make_link('post/list/{searchTerms}');
			$suggenton_url = make_link('browser_search/')."{searchTerms}";
			$icon_b64 = base64_encode(file_get_contents("favicon.ico"));

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
			$page->set_mode("data");
			$page->set_type("text/xml");
			$page->set_data($xml);
		}

		else if(
			$event->page_matches("browser_search") &&
			!$config->get_bool("disable_search_suggestions")
		) {
			// We have to build some json stuff
			$tag_search = $event->get_arg(0);

			// Now to get DB results
			if($config->get_string("search_suggestions_results_order") == "a") {
				$tags = $database->execute("SELECT tag FROM tags WHERE tag LIKE ? AND count > 0 ORDER BY tag ASC LIMIT 30",array($tag_search."%"));
			} else {
				$tags = $database->execute("SELECT tag FROM tags WHERE tag LIKE ? AND count > 0 ORDER BY count DESC LIMIT 30",array($tag_search."%"));
			}


			// And to do stuff with it. We want our output to look like:
			// ["shimmie",["shimmies","shimmy","shimmie","21 shimmies","hip shimmies","skea shimmies"],[],[]]
			$json_tag_list = "";

			$tags_array = array();
			foreach($tags as $tag) {
				array_push($tags_array,$tag['tag']);
			}

			$json_tag_list .= implode("\",\"", $tags_array);

			// And now for the final output
			$json_string = "[\"$tag_search\",[\"$json_tag_list\"],[],[]]";
			$page->set_mode("data");
			$page->set_data($json_string);
		}
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sort_by = array();
		$sort_by['Alphabetical'] = 'a';
		$sort_by['Tag Count'] = 't';

		$sb = new SetupBlock("Browser Search");
		$sb->add_bool_option("disable_search_suggestions", "Disable search suggestions: ");
		$sb->add_label("<br>");
		$sb->add_choice_option("search_suggestions_results_order", $sort_by, "Sort the suggestions by:");
		$event->panel->add_block($sb);
	}
}
?>
