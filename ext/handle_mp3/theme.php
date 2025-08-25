<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, AUDIO, P, SOURCE, SPAN, emptyHTML};
use function MicroHTML\{SCRIPT};

class MP3FileHandlerTheme extends Themelet
{
    public function build_media(Image $image): \MicroHTML\HTMLElement
    {
        $ilink = $image->get_image_link();

        Ctx::$page->add_html_header(SCRIPT([
            'src' => Url::base() . "/ext/handle_mp3/lib/jsmediatags.min.js",
            'type' => 'text/javascript'
        ]));

        return emptyHTML(
            AUDIO(
                ["controls" => true, "class" => "shm-main-image", "id" => "main_image", "alt" => "main image"],
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
    }
}
