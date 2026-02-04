<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A,BR,CODE,INPUT};
use function MicroHTML\emptyHTML;

use MicroHTML\HTMLElement;

class AliasEditorTheme extends Themelet
{
    /**
     * Show a page of aliases.
     */
    public function display_aliases(HTMLElement $table, HTMLElement $paginator): void
    {
        $html = emptyHTML(
            "A tag alias replaces a tag with another tag or tags.",
            BR(),
            "A tag implication (where the old tag stays and adds a new tag) is made by including the old tag in the list of new tags (",
            CODE("fox"),
            " → ",
            CODE("fox canine"),
            ")",
            BR(),
            BR(),
            $table,
            BR(),
            $paginator,
            BR(),
            A(["href" => make_link("alias/export/aliases.csv", ["download" => "aliases.csv"])], "Download as CSV")
        );

        $bulk_form = SHM_FORM(make_link("alias/import"), multipart: true);
        $bulk_form->appendChild(
            INPUT(["type" => "file", "name" => "alias_file"]),
            SHM_SUBMIT("Upload List")
        );
        $bulk_html = emptyHTML($bulk_form);

        $page = Ctx::$page;
        $page->set_title("Alias List");
        $page->add_block(new Block("Aliases", $html));

        if (Ctx::$user->can(AliasEditorPermission::MANAGE_ALIAS_LIST)) {
            $page->add_block(new Block("Bulk Upload", $bulk_html, "main", 51));
        }
    }
}
