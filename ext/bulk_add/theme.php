<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{BR, INPUT, P, TABLE, TD, TH, TR, emptyHTML};
use function MicroHTML\{LI, UL};

class BulkAddTheme extends Themelet
{
    /**
     * Show a standard page for results to be put into
     *
     * @param UploadResult[] $results
     */
    public function display_upload_results(array $results): void
    {
        $html = UL();
        foreach ($results as $r) {
            if (is_a($r, UploadError::class)) {
                $html->appendChild(LI("{$r->name} failed: {$r->error}"));
            } else {
                $html->appendChild(LI("{$r->name} ok"));
            }
        }

        Ctx::$page->set_title("Adding folder");
        Ctx::$page->add_block(new Block("Results", $html));
    }

    /*
     * Add a section to the admin page. This should contain a form which
     * links to bulk_add with POST[dir] set to the name of a server-side
     * directory full of images
     */
    public function display_admin_block(): void
    {
        $html = emptyHTML(
            "Add a folder full of images; any subfolders will have their names
			used as tags for the images within.",
            BR(),
            "Note: this is the folder as seen by the server -- you need to
			upload via FTP or something first.",
            P(),
            SHM_SIMPLE_FORM(
                make_link("bulk_add"),
                TABLE(
                    ["class" => "form"],
                    TR(TH("Folder"), TD(INPUT(["type" => "text", "name" => "dir", "size" => "40"]))),
                    TR(TD(["colspan" => 2], INPUT(["type" => "submit", "value" => "Add"])))
                )
            )
        );
        Ctx::$page->add_block(new Block("Bulk Add", $html));
    }
}
