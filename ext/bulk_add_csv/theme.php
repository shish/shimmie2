<?php

declare(strict_types=1);

namespace Shimmie2;

class BulkAddCSVTheme extends Themelet
{
    /** @var Block[] */
    private array $messages = [];

    /*
     * Show a standard page for results to be put into
     */
    public function display_upload_results(Page $page): void
    {
        $page->set_title("Adding posts from csv");
        $page->set_heading("Adding posts from csv");
        $page->add_block(new NavBlock());
        foreach ($this->messages as $block) {
            $page->add_block($block);
        }
    }

    /*
     * Add a section to the admin page. This should contain a form which
     * links to bulk_add_csv with POST[csv] set to the name of a server-side
     * csv file
     */
    public function display_admin_block(): void
    {
        global $page;
        $html = "
			Add posts from a csv. Posts will be tagged and have their
			source and rating set (if \"Post Ratings\" is enabled)
			<br>Specify the absolute or relative path to a local .csv file. Check <a href=\"" . make_link("ext_doc/bulk_add_csv") . "\">here</a> for the expected format.

			<p>".make_form(make_link("bulk_add_csv"))."
				<table class='form'>
					<tr><th>CSV</th><td><input type='text' name='csv' size='40'></td></tr>
					<tr><td colspan='2'><input type='submit' value='Add'></td></tr>
				</table>
			</form>
		";
        $page->add_block(new Block("Bulk Add CSV", $html));
    }

    public function add_status(string $title, string $body): void
    {
        $this->messages[] = new Block($title, $body);
    }
}
