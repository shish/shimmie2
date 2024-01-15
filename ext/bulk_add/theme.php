<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{UL, LI};

class BulkAddTheme extends Themelet
{
    /**
     * Show a standard page for results to be put into
     *
     * @param UploadResult[] $results
     */
    public function display_upload_results(Page $page, array $results): void
    {
        $page->set_title("Adding folder");
        $page->set_heading("Adding folder");
        $page->add_block(new NavBlock());
        $html = UL();
        foreach ($results as $r) {
            if (is_a($r, UploadError::class)) {
                $html->appendChild(LI("{$r->name} failed: {$r->error}"));
            } else {
                $html->appendChild(LI("{$r->name} ok"));
            }
        }
        $page->add_block(new Block("Results", $html));
    }

    /*
     * Add a section to the admin page. This should contain a form which
     * links to bulk_add with POST[dir] set to the name of a server-side
     * directory full of images
     */
    public function display_admin_block(): void
    {
        global $page;
        $html = "
			Add a folder full of images; any subfolders will have their names
			used as tags for the images within.
			<br>Note: this is the folder as seen by the server -- you need to
			upload via FTP or something first.

			<p>".make_form(make_link("bulk_add"))."
				<table class='form'>
					<tr><th>Folder</th><td><input type='text' name='dir' size='40'></td></tr>
					<tr><td colspan='2'><input type='submit' value='Add'></td></tr>
				</table>
			</form>
		";
        $page->add_block(new Block("Bulk Add", $html));
    }
}
