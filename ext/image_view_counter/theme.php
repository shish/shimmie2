<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{P,joinHTML};

class ImageViewCounterTheme extends Themelet
{
    /**
     * @param Image[] $images
     */
    public function view_popular(array $images): void
    {
        $pop_images = [];
        foreach ($images as $image) {
            $pop_images[] = $this->build_thumb($image);
        }

        Ctx::$page->set_title(Ctx::$config->get(SetupConfig::TITLE));
        Ctx::$page->add_block(new Block(null, joinHTML(" ", $pop_images), "main", 30));
    }

    public function get_help_html(): HTMLElement
    {
        return P('Search for posts that have received views by users.');
    }
}
