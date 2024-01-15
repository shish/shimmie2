<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, BR, VIDEO, SOURCE, emptyHTML};

class VideoFileHandlerTheme extends Themelet
{
    public function display_image(Image $image): void
    {
        global $config, $page;

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
                    'poster' => make_http($image->get_thumb_link()),
                    'autoplay' => $config->get_bool(VideoFileHandlerConfig::PLAYBACK_AUTOPLAY),
                    'loop' => $config->get_bool(VideoFileHandlerConfig::PLAYBACK_LOOP),
                    'muted' => $config->get_bool(VideoFileHandlerConfig::PLAYBACK_MUTE),
                    'style' => "height: $height; width: $width; max-width: 100%; object-fit: contain; background-color: black;",
                    'onloadstart' => 'this.volume = 0.25',
                ],
                SOURCE([
                    'src' => $image->get_image_link(),
                    'type' => strtolower($image->get_mime())
                ])
            )
        );

        $page->add_block(new Block("Video", $html, "main", 10));
    }
}
