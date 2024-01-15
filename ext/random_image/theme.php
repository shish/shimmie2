<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\DIV;
use function MicroHTML\A;
use function MicroHTML\IMG;

class RandomImageTheme extends Themelet
{
    public function display_random(Page $page, Image $image): void
    {
        $page->add_block(new Block("Random Post", $this->build_random_html($image), "left", 8));
    }

    public function build_random_html(Image $image, ?string $query = null): string
    {
        $tsize = get_thumbnail_size($image->width, $image->height);

        return (string)DIV(
            ["style" => "text-align: center;"],
            A(
                ["href" => make_link("post/view/{$image->id}", $query)],
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
