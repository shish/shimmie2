<?php

declare(strict_types=1);

namespace Shimmie2;

class FilterTheme extends Themelet
{
    public function addFilterBox(): void
    {
        global $config, $page, $user, $user_config;

        // If user is not able to set their own filters, use the default filters.
        if ($user->can(Permissions::CHANGE_USER_SETTING)) {
            $tags = $user_config->get_string("filter_tags");
        } else {
            $tags = $config->get_string("filter_tags");
        }
        $html = "<noscript>Post filtering requires JavaScript</noscript>
        <ul id='filter-list' class='list-bulleted'></ul>
        <a id='disable-all-filters' style='display: none;' href='#'>Disable all</a>
        <a id='re-enable-all-filters' style='display: none;' href='#'>Re-enable all</a>
        ";
        $page->add_html_header("<meta id='filter-tags' tags='$tags'>");
        $page->add_block(new Block("Filters", $html, "left", 10));
    }
}
