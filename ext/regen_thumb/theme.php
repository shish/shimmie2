<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{INPUT, LABEL, OPTION, P, SELECT, TABLE, TD, TH, TR, emptyHTML};
use function MicroHTML\{META};

class RegenThumbTheme extends Themelet
{
    /**
     * Show a link to the new thumbnail.
     */
    public function display_results(Image $image): void
    {
        Ctx::$page->set_title("Thumbnail Regenerated");
        Ctx::$page->add_html_header(META(['http-equiv' => 'cache-control', 'content' => 'no-cache']));
        Ctx::$page->add_block(new Block("Thumbnail", $this->build_thumb($image)));
    }

    public function bulk_html(): HTMLElement
    {
        return LABEL(INPUT(["type" => 'checkbox', "name" => 'bulk_regen_thumb_missing_only', "id" => 'bulk_regen_thumb_missing_only', "style" => 'width:13px', "value" => 'true']), "Only missing thumbs");
    }

    public function display_admin_block(): void
    {
        global $database;

        $options = [OPTION(["value" => ''], "All")];
        $results = $database->get_all("SELECT mime, count(*) count FROM images group by mime");
        foreach ($results as $result) {
            $options[] = OPTION(["value" => $result["mime"]], $result["mime"] . " (" . $result["count"] . ")");
        }

        $html = emptyHTML(
            "Will only regenerate missing thumbnails, unless force is selected. Force will override the limit and will likely take a very long time to process.",
            P(SHM_FORM(
                action: make_link("admin/regen_thumbs"),
                children: [
                    TABLE(
                        ["class" => "form"],
                        TR(
                            TH("Force"),
                            TD(INPUT(["type" => "checkbox", "name" => "regen_thumb_force", "id" => "regen_thumb_force", "value" => "true"]))
                        ),
                        TR(
                            TH("Limit"),
                            TD(INPUT(["type" => "number", "name" => "regen_thumb_limit", "id" => "regen_thumb_limit", "value" => "1000"]))
                        ),
                        TR(
                            TH("MIME"),
                            TD(SELECT(["name" => "regen_thumb_mime", "id" => "regen_thumb_mime"], ...$options))
                        ),
                        TR(
                            TD(["colspan" => 2], SHM_SUBMIT("Regenerate Thumbnails"))
                        )
                    )
                ],
            )),
        );
        Ctx::$page->add_block(new Block("Regen Thumbnails", $html));
    }
}
