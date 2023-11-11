<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\TABLE;
use function MicroHTML\TR;
use function MicroHTML\TD;
use function MicroHTML\SMALL;
use function MicroHTML\rawHTML;
use function MicroHTML\INPUT;
use function MicroHTML\emptyHTML;
use function MicroHTML\NOSCRIPT;
use function MicroHTML\DIV;
use function MicroHTML\BR;
use function MicroHTML\A;
use function MicroHTML\P;

class CustomUploadTheme extends UploadTheme
{
    public function display_block(Page $page): void
    {
        $page->add_block(new Block("Upload", $this->build_upload_block(), "head", 20));
        $page->add_block(new Block("Upload", $this->build_upload_block(), "left", 20));
    }

    public function display_full(Page $page): void
    {
        $page->add_block(new Block("Upload", "Disk nearly full, uploads disabled", "head", 20));
    }

    public function display_page(Page $page): void
    {
        global $config, $page;

        $tl_enabled = ($config->get_string(UploadConfig::TRANSLOAD_ENGINE, "none") != "none");
        $max_size = $config->get_int(UploadConfig::SIZE);
        $max_kb = to_shorthand_int($max_size);
        $upload_list = $this->h_upload_list_1();

        $form = SHM_FORM("upload", "POST", true, "file_upload");
        $form->appendChild(
            TABLE(
                ["id" => "large_upload_form", "class" => "vert"],
                TR(
                    TD(["width" => "20"], rawHTML("Common&nbsp;Tags")),
                    TD(["colspan" => "5"], INPUT(["name" => "tags", "type" => "text", "placeholder" => "tagme", "autocomplete" => "off"]))
                ),
                TR(
                    TD(["width" => "20"], rawHTML("Common&nbsp;Source")),
                    TD(["colspan" => "5"], INPUT(["name" => "source", "type" => "text"]))
                ),
                $upload_list,
                TR(
                    TD(["colspan" => "6"], INPUT(["id" => "uploadbutton", "type" => "submit", "value" => "Post"]))
                ),
            )
        );
        $html = emptyHTML(
            $form,
            SMALL("(Max file size is $max_kb)")
        );

        $page->set_title("Upload");
        $page->set_heading("Upload");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Upload", $html, "main", 20));
        if ($tl_enabled) {
            $page->add_block(new Block("Bookmarklets", (string)$this->h_bookmarklets(), "left", 20));
        }
        $html = "
			<a href='//rule34.paheal.net/wiki/tagging'>Tagging Guide</a>
		";
        $page->add_block(new Block(null, $html, "main", 19));
    }

    protected function build_upload_block(): HTMLElement
    {
        return A(["href" => make_link("upload"), "style" => 'font-size: 2em; display: block;'], "Upload");
    }

    protected function h_upload_list_1(): HTMLElement
    {
        global $config;
        $upload_list = emptyHTML();
        $upload_count = $config->get_int(UploadConfig::COUNT);
        $tl_enabled = ($config->get_string(UploadConfig::TRANSLOAD_ENGINE, "none") != "none");
        $accept = $this->get_accept();

        $upload_list->appendChild(
            TR(
                TD(["colspan" => $tl_enabled ? 2 : 4], "Files"),
                $tl_enabled ? TD(["colspan" => "2"], "URLs") : emptyHTML(),
                TD(["colspan" => "2"], "Post-Specific Tags"),
            )
        );

        for ($i = 0; $i < $upload_count; $i++) {
            $upload_list->appendChild(
                TR(
                    TD(["colspan" => $tl_enabled ? 2 : 4], INPUT(["type" => "file", "name" => "data{$i}[]", "accept" => $accept, "multiple" => true])),
                    $tl_enabled ? TD(["colspan" => "2"], INPUT(["type" => "text", "name" => "url{$i}"])) : emptyHTML(),
                    TD(["colspan" => "2"], INPUT(["type" => "text", "name" => "tags{$i}", "autocomplete" => "off"])),
                )
            );
        }

        return $upload_list;
    }
}
