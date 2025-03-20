<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\DIV;
use function MicroHTML\A;
use function MicroHTML\IMG;

class FeaturedTheme extends Themelet
{
    public function display_featured(Image $image): void
    {
        global $page;
        $page->add_block(new Block("Featured Post", $this->build_featured_html($image), "left", 3));
    }

    public function build_featured_html(Image $image): \MicroHTML\HTMLElement
    {
        $tsize = ThumbnailUtil::get_thumbnail_size($image->width, $image->height);

        return DIV(
            ["style" => "text-align: center;"],
            A(
                ["href" => make_link("post/view/{$image->id}")],
                IMG([
                    "id" => "thumb_rand_{$image->id}",
                    "title" => $image->get_tooltip(),
                    "alt" => $image->get_tooltip(),
                    "class" => 'highlighted',
                    "style" => "max-height: {$tsize[1]}px; max-width: 100%;",
                    "src" => $image->get_thumb_link()
                ])
            )
        );
    }
}
