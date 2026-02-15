<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, INPUT, TABLE, TD, TR, emptyHTML};

use MicroHTML\HTMLElement;

class BulkAddCSVTheme extends Themelet
{
    /** @var Block[] */
    private array $messages = [];

    /*
     * Show a standard page for results to be put into
     */
    public function display_upload_results(): void
    {
        Ctx::$page->set_title("Adding posts from csv");
        $this->display_navigation();
        foreach ($this->messages as $block) {
            Ctx::$page->add_block($block);
        }
    }

    /*
     * Add a section to the admin page. This should contain a form which
     * links to bulk_add_csv with POST[csv] set to the name of a server-side
     * csv file
     */
    public function display_admin_block(): void
    {
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
        Ctx::$page->add_block(new Block("Bulk Add CSV", $html));
    }

    public function add_status(string $title, HTMLElement $body): void
    {
        $this->messages[] = new Block($title, $body);
    }
}
