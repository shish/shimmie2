<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\rawHTML;

class ReverseSearchLinksTheme extends Themelet
{
    public function reverse_search_block(Page $page, Image $image): void
    {
        global $config;

        $url = (string)$image->get_thumb_link()->asAbsolute();
        $links = [
            'SauceNAO' => Url::parse('https://saucenao.com/search.php')->withModifiedQuery(["url" => $url]),
            'TinEye' => Url::parse('https://www.tineye.com/search/')->withModifiedQuery(["url" => $url]),
            'trace.moe' => Url::parse('https://trace.moe/')->withModifiedQuery(["auto" => "", "url" => $url]),
            'ascii2d' => Url::parse('https://ascii2d.net/search/url/' . url_escape((string)$url)),
            'Yandex' => Url::parse('https://yandex.com/images/search')->withModifiedQuery(["rpt" => "imageview", "url" => $url]),
        ];

        // only generate links for enabled reverse search services
        $enabled_services = $config->get_array(ReverseSearchLinksConfig::ENABLED_SERVICES);

        $html = "";
        foreach ($links as $name => $link) {
            if (in_array($name, $enabled_services)) {
                $icon_link = Url::base() . "/ext/reverse_search_links/icons/" . strtolower($name) . ".ico";
                $html .= "<a href='$link' class='reverse_image_link' rel='nofollow'><img title='Search with $name' src='$icon_link' alt='$name icon'></a>";
            }
        }

        $page->add_block(new Block("Reverse Image Search", rawHTML($html), "main", 20));
    }
}
