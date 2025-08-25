<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, BR, SOURCE, VIDEO, emptyHTML};

class VideoFileHandlerTheme extends Themelet
{
    public function build_media(Image $image): \MicroHTML\HTMLElement
    {
        $src = $image->get_image_link();

        return emptyHTML(
            VIDEO(
                [
                    'controls' => true,
                    'id' => 'main_image',
                    'class' => 'shm-main-image',
                    'alt' => 'main image',
                    'poster' => $image->get_thumb_link()->asAbsolute(),
                    'autoplay' => Ctx::$config->get(VideoFileHandlerConfig::PLAYBACK_AUTOPLAY),
                    'loop' => Ctx::$config->get(VideoFileHandlerConfig::PLAYBACK_LOOP),
                    'muted' => Ctx::$config->get(VideoFileHandlerConfig::PLAYBACK_MUTE),
                    'onloadstart' => 'this.volume = 0.25',
                ],
                SOURCE([
                    'src' => $src,
                    'type' => $image->get_mime()
                ])
            ),
            BR(),
            "Video not playing? ",
            A(['href' => $src], "Click here"),
            " to download the file.",
        );
    }
}
