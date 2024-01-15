<?php

declare(strict_types=1);

namespace Shimmie2;

class CBZFileHandlerTheme extends Themelet
{
    public function display_image(Image $image): void
    {
        global $page;
        $data_href = get_base_href();
        $ilink = $image->get_image_link();
        $html = "
            <div id='comicMain'>
                <div class='comicPager'>
                    <select id='comicPageList'></select>
                </div>
                <div id='comicView'>
                    <a id='comicPrev'><span>&lt;</span></a>
                    <img alt='comic' id='comicPage' src='{$data_href}/ext/handle_cbz/spinner.gif' />
                    <a id='comicNext'><span>&gt;</span></a>
                </div>
            </div>
            <script src='{$data_href}/ext/handle_cbz/jszip-utils.min.js'></script>
            <script src='{$data_href}/ext/handle_cbz/jszip.min.js'></script>
            <script src='{$data_href}/ext/handle_cbz/comic.js'></script>
            <script>window.comic = new Comic('comicMain', '$ilink');</script>
        ";
        $page->add_block(new Block("Comic", $html, "main", 10, "comicBlock"));
    }
}
