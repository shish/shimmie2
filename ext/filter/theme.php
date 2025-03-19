<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{META};
use function MicroHTML\A;
use function MicroHTML\NOSCRIPT;
use function MicroHTML\UL;
use function MicroHTML\emptyHTML;

class FilterTheme extends Themelet
{
    public function addFilterBox(): void
    {
        global $config, $page, $user;

        // If user is not able to set their own filters, use the default filters.
        if ($user->can(UserAccountsPermission::CHANGE_USER_SETTING)) {
            $tags = $user->get_config()->get_string(
                FilterUserConfig::TAGS,
                $config->get_string(FilterConfig::TAGS)
            );
        } else {
            $tags = $config->get_string(FilterConfig::TAGS);
        }
        $html = emptyHTML(
            NOSCRIPT("Post filtering requires JavaScript"),
            UL(["id" => "filter-list", "class" => "list-bulleted"]),
            A(["id" => "disable-all-filters", "href" => "#", "style" => "display: none;"], "Disable all"),
            A(["id" => "re-enable-all-filters", "href" => "#", "style" => "display: none;"], "Re-enable all")
        );
        $page->add_html_header(META(['id' => 'filter-tags', 'tags' => $tags]));
        $page->add_block(new Block("Filters", $html, "left", 10));
    }
}
