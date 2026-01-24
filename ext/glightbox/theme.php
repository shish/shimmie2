<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A};

class GLightboxViewerTheme extends Themelet
{
    public function build_media(Image $image): \MicroHTML\HTMLElement
    {
        $title = null;
        if (PostTitles::is_enabled() && Ctx::$config->get(GLightboxConfig::SHOW_TITLE)) {
            $title = PostTitles::get_title($image) ?: null;
        }

        $desc = null;
        if (PostDescription::is_enabled() && Ctx::$config->get(GLightboxConfig::SHOW_DESCRIPTION)) {
            $desc = PostDescription::get_description($image) ?: null;
        }

        return A(
            [
                "data-glightbox" => "type: image; title: $title; description: $desc",
                "href" => $image->get_image_link()
            ],
            (new ImageFileHandlerTheme())->build_media($image)
        );
    }
}
