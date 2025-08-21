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

        $serve_mode = Ctx::$config->get(VideoFileHandlerConfig::SERVE_MODE);
        $src = $image->get_image_link();
        if ($serve_mode === "auto") {
            // Unit doesn't support range requests, so if:
            // - the server is Unit
            // - we're trying to serve a video file from the filesystem
            // - there's no reverse-proxy in front of it
            // then we need to serve the video ourselves
            if (
                str_starts_with($_SERVER["SERVER_SOFTWARE"] ?? 'unknown', "Unit/")
                && str_starts_with((string)$src, "/_images/")
                && (string)Network::get_real_ip() === ($_SERVER["REMOTE_ADDR"] ?? '')
            ) {
                $serve_mode = "shimmie";
            } else {
                $serve_mode = "ilink";
            }
        }
        if ($serve_mode === "shimmie") {
            $src = $image->parse_link_template('image/$id/$id%20-%20$tags.$ext');
        }


        $html = emptyHTML(
            "Video not playing? ",
            A(['href' => $src], "Click here"),
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
                    'src' => $src,
                    'type' => $image->get_mime()
                ])
            )
        );

        Ctx::$page->add_block(new Block(null, $html, "main", 10));
    }
}
