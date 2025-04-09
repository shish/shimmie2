<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, DIV, IMG};

class FeaturedTheme extends Themelet
{
    public function display_featured(Image $image): void
    {
        Ctx::$page->add_block(new Block("Featured Post", $this->build_featured_html($image), "left", 3));
    }

    public function build_featured_html(Image $image): \MicroHTML\HTMLElement
    {
        $tsize = $image->get_thumb_size();

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
