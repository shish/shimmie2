<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{AUDIO, SOURCE, emptyHTML};

class AudioFileHandlerTheme extends Themelet
{
    public function build_media(Image $image): \MicroHTML\HTMLElement
    {
        $ilink = $image->get_image_link();

        return emptyHTML(
            AUDIO(
                ["controls" => true, "class" => "shm-main-image", "id" => "main_image", "alt" => "main image"],
                SOURCE(["id" => "audio_src", "src" => $ilink, "type" => $image->get_mime()])
            ),
        );
    }
}
