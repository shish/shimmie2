<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\A;
use function MicroHTML\INPUT;
use function MicroHTML\P;
use function MicroHTML\emptyHTML;

class AutoTaggerTheme extends Themelet
{
    /**
     * Show a page of auto-tag definitions.
     *
     * Note: $can_manage = whether things like "add new alias" should be shown
     */
    public function display_auto_tagtable(HTMLElement $table, HTMLElement $paginator): void
    {
        global $page, $user;

        $page->set_title("Auto-Tag List");
        $this->display_navigation();

        $page->add_block(new Block("Auto-Tag", emptyHTML(
            $table,
            $paginator,
            P(A(
                ["href" => make_link("auto_tag/export/auto_tag.csv"), "download" => "auto_tag.csv"],
                "Download as CSV"
            ))
        )));

        if ($user->can(AutoTaggerPermission::MANAGE_AUTO_TAG)) {
            $page->add_block(new Block("Bulk Upload", SHM_FORM(
                action: make_link("auto_tag/import"),
                multipart: true,
                children: [
                    INPUT(["type" => "file", "name" => "auto_tag_file"]),
                    SHM_SUBMIT("Upload List")
                ]
            )));
        }
    }
}
