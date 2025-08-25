<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, DIV, IMG, SCRIPT, SELECT, SPAN, emptyHTML};

class CBZFileHandlerTheme extends Themelet
{
    public function build_media(Image $image): \MicroHTML\HTMLElement
    {
        $data_href = Url::base();
        $ilink = $image->get_image_link();
        return emptyHTML(
            DIV(
                ["id" => "comicMain"],
                DIV(
                    ["class" => "comicPager"],
                    SELECT(["id" => "comicPageList"])
                ),
                DIV(
                    ["id" => "comicView"],
                    A(["id" => "comicPrev"], SPAN("<")),
                    IMG(["alt" => "comic", "id" => "comicPage", "src" => "{$data_href}/ext/handle_cbz/spinner.gif"]),
                    A(["id" => "comicNext"], SPAN(">"))
                )
            ),
            SCRIPT(["src" => "{$data_href}/ext/handle_cbz/jszip-utils.min.js"]),
            SCRIPT(["src" => "{$data_href}/ext/handle_cbz/jszip.min.js"]),
            SCRIPT(["src" => "{$data_href}/ext/handle_cbz/comic.js"]),
            SCRIPT("window.comic = new Comic('comicMain', '$ilink');")
        );
    }
}
