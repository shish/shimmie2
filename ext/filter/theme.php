<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, NOSCRIPT, UL, emptyHTML};
use function MicroHTML\{META};

class FilterTheme extends Themelet
{
    public function addFilterBox(): void
    {
        // If user is not able to set their own filters, use the default filters.
        if (Ctx::$user->can(UserAccountsPermission::CHANGE_USER_SETTING)) {
            $tags = Ctx::$user->get_config()->get(FilterUserConfig::TAGS)
                ?? Ctx::$config->get(FilterConfig::TAGS);
        } else {
            $tags = Ctx::$config->get(FilterConfig::TAGS);
        }
        $html = emptyHTML(
            NOSCRIPT("Post filtering requires JavaScript"),
            UL(["id" => "filter-list", "class" => "list-bulleted"]),
            A(["id" => "disable-all-filters", "href" => "#", "style" => "display: none;"], "Disable all"),
            A(["id" => "re-enable-all-filters", "href" => "#", "style" => "display: none;"], "Re-enable all")
        );
        Ctx::$page->add_html_header(META(['id' => 'filter-tags', 'tags' => $tags]));
        Ctx::$page->add_block(new Block("Filters", $html, "left", 10));
    }
}
