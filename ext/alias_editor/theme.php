<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\emptyHTML;
use function MicroHTML\{BR,INPUT};

class AliasEditorTheme extends Themelet
{
    /**
     * Show a page of aliases.
     */
    public function display_aliases(HTMLElement $table, HTMLElement $paginator): void
    {
        global $page, $user;

        $html = emptyHTML($table, BR(), $paginator, BR(), SHM_A("alias/export/aliases.csv", "Download as CSV", args: ["download" => "aliases.csv"]));

        $bulk_form = SHM_FORM("alias/import", multipart: true);
        $bulk_form->appendChild(
            INPUT(["type" => "file", "name" => "alias_file"]),
            SHM_SUBMIT("Upload List")
        );
        $bulk_html = emptyHTML($bulk_form);

        $page->set_title("Alias List");
        $page->set_heading("Alias List");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Aliases", $html));

        if ($user->can(Permissions::MANAGE_ALIAS_LIST)) {
            $page->add_block(new Block("Bulk Upload", $bulk_html, "main", 51));
        }
    }
}
