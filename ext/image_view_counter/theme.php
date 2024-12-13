<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\rawHTML;

class ImageViewCounterTheme extends Themelet
{
    /**
     * @param Image[] $images
     */
    public function view_popular(array $images): void
    {
        global $page, $config;
        $pop_images = "";
        foreach ($images as $image) {
            $pop_images .= $this->build_thumb($image) . "\n";
        }

        $page->set_title($config->get_string(SetupConfig::TITLE));
        $page->add_block(new NavBlock());
        $page->add_block(new Block(null, rawHTML($pop_images), "main", 30));
    }

    public function get_help_html(): string
    {
        return '<p>Search for posts that have received views by users.</p>';
    }
}
