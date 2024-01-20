<?php

declare(strict_types=1);

namespace Shimmie2;

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
            $pop_images .= $this->build_thumb_html($image) . "\n";
        }

        $nav_html = "<a href=".make_link().">Index</a>";

        $page->set_heading($config->get_string(SetupConfig::TITLE));
        $page->add_block(new Block("Navigation", $nav_html, "left", 10));
        $page->add_block(new Block(null, $pop_images, "main", 30));
    }

    public function get_help_html(): string
    {
        return '<p>Search for posts that have received views by users.</p>';
    }
}
