<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{SCRIPT};
use function MicroHTML\A;
use function MicroHTML\AUDIO;
use function MicroHTML\P;
use function MicroHTML\SOURCE;
use function MicroHTML\SPAN;
use function MicroHTML\emptyHTML;

class MP3FileHandlerTheme extends Themelet
{
    public function display_image(Image $image): void
    {
        global $page;
        $ilink = $image->get_image_link();

        $page->add_html_header(SCRIPT([
            'src' => Url::base() . "/ext/handle_mp3/lib/jsmediatags.min.js",
            'type' => 'text/javascript'
        ]));

        $html = emptyHTML(
            AUDIO(
                ["controls" => true, "class" => "shm-main-image audio_image", "id" => "main_image", "alt" => "main image"],
                SOURCE(["id" => "audio_src", "src" => $ilink, "type" => "audio/mpeg"])
            ),
            P(
                "Title: ",
                SPAN(["id" => "audio-title"], "???"),
                " | ",
                "Artist: ",
                SPAN(["id" => "audio-artist"], "???")
            ),
            P(A(["href" => $ilink, "id" => "audio-download"], "Download"))
        );
        $page->add_block(new Block(null, $html, "main", 10));
    }
}
