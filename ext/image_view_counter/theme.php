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
        global $page, $config;
        $pop_images = [];
        foreach ($images as $image) {
            $pop_images[] = $this->build_thumb($image);
        }

        $page->set_title($config->get_string(SetupConfig::TITLE));
        $this->display_navigation();
        $page->add_block(new Block(null, joinHTML(" ", $pop_images), "main", 30));
    }

    public function get_help_html(): HTMLElement
    {
        return P('Search for posts that have received views by users.');
    }
}
