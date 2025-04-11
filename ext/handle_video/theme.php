<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, BR, SOURCE, VIDEO, emptyHTML};

class VideoFileHandlerTheme extends Themelet
{
    public function display_image(Image $image): void
    {
        $width = "auto";
        if ($image->width > 1) {
            $width = $image->width."px";
        }
        $height = "auto";
        if ($image->height > 1) {
            $height = $image->height."px";
        }

        $html = emptyHTML(
            "Video not playing? ",
            A(['href' => $image->get_image_link()], "Click here"),
            " to download the file.",
            BR(),
            VIDEO(
                [
                    'controls' => true,
                    'class' => 'shm-main-image',
                    'id' => 'main_image',
                    'alt' => 'main image',
                    'poster' => $image->get_thumb_link()->asAbsolute(),
                    'autoplay' => Ctx::$config->get(VideoFileHandlerConfig::PLAYBACK_AUTOPLAY),
                    'loop' => Ctx::$config->get(VideoFileHandlerConfig::PLAYBACK_LOOP),
                    'muted' => Ctx::$config->get(VideoFileHandlerConfig::PLAYBACK_MUTE),
                    'style' => "height: $height; width: $width; max-width: 100%; object-fit: contain; background-color: black;",
                    'onloadstart' => 'this.volume = 0.25',
                ],
                SOURCE([
                    'src' => $image->get_image_link(),
                    'type' => $image->get_mime()
                ])
            )
        );

        Ctx::$page->add_block(new Block(null, $html, "main", 10));
    }
}
