<?php declare(strict_types=1);

class AutoCompleteTheme extends Themelet
{
    public static function generate_autocomplete_enable_script(string $selector)
    {
        global $config;

        $limit = $config->get_int(AutoCompleteConfig::SEARCH_LIMIT);
        $search_categories = $config->get_bool(AutoCompleteConfig::SEARCH_CATEGORIES) ? "'true'" : "'false'";



        return "enableTagAutoComplete($('$selector'),$limit,$search_categories);";
    }

    public function build_autocomplete(Page $page)
    {
        global $config;

        $base_href = get_base_href();
        // TODO: AJAX test and fallback.

        $page->add_html_header("<script defer src='$base_href/ext/autocomplete/lib/jquery-ui.min.js' type='text/javascript'></script>");
        $page->add_html_header("<script defer src='$base_href/ext/autocomplete/lib/tag-it.min.js' type='text/javascript'></script>");
        $page->add_html_header('<link rel="stylesheet" type="text/css" href="//ajax.googleapis.com/ajax/libs/jqueryui/1/themes/flick/jquery-ui.css">');
        $page->add_html_header("<link rel='stylesheet' type='text/css' href='$base_href/ext/autocomplete/lib/jquery.tagit.css' />");

        $scripts = "";

        if ($config->get_bool(AutoCompleteConfig::NAVIGATION)) {
            $scripts .= self::generate_autocomplete_enable_script('.autocomplete_tags[name="search"]');
        }
        if ($config->get_bool(AutoCompleteConfig::TAGGING)) {
            $scripts .= self::generate_autocomplete_enable_script('.autocomplete_tags[name^="tags"], .autocomplete_tags[name="bulk_tags"], .autocomplete_tags[name="tag_edit__tags"]');
        }

        $page->add_block(new Block(
            null,
            "<script type='text/javascript'>
				document.addEventListener('DOMContentLoaded', () => {
				    // Autocomplete initializations
                    $scripts
				});
			</script>",
            "main",
            1000
        ));
    }

    public function display_admin_block(SetupBuildingEvent $event)
    {
        $sb = new SetupBlock("Autocomplete");
        $sb->start_table();
        $sb->add_int_option(AutoCompleteConfig::SEARCH_LIMIT, "Search Limit", true);
        $sb->add_bool_option(AutoCompleteConfig::SEARCH_CATEGORIES, "Search Categories", true);
        $sb->add_bool_option(AutoCompleteConfig::NAVIGATION, "Enable For Navigation", true);
        $sb->add_bool_option(AutoCompleteConfig::TAGGING, "Enable For Tagging", true);
        $sb->end_table();
        $event->panel->add_block($sb);
    }
}
