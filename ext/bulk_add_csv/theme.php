<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\A;
use function MicroHTML\INPUT;
use function MicroHTML\TABLE;
use function MicroHTML\TD;
use function MicroHTML\TR;
use function MicroHTML\emptyHTML;

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
        $this->display_navigation();
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
        $html = emptyHTML(
            "Add posts from a csv. Posts will be tagged and have their
			source and rating set (if \"Post Ratings\" is enabled).
			Specify the absolute or relative path to a local .csv file.
			Check ",
            A(["href" => make_link("ext_doc/bulk_add_csv")], "here"),
            " for the expected format.",
            SHM_SIMPLE_FORM(
                make_link("bulk_add_csv"),
                TABLE(
                    ["class" => "form"],
                    TR(
                        TD("CSV"),
                        TD(INPUT(["type" => "text", "name" => "csv", "size" => "40"]))
                    ),
                    TR(
                        TD(["colspan" => "2"], SHM_SUBMIT("Add"))
                    )
                )
            )
        );
        $page->add_block(new Block("Bulk Add CSV", $html));
    }

    public function add_status(string $title, string $body): void
    {
        $this->messages[] = new Block($title, emptyHTML($body));
    }
}
