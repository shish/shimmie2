<?php

declare(strict_types=1);

namespace Shimmie2;

class ReverseSearchLinksTheme extends Themelet
{
    public function reverse_search_block(Page $page, Image $image): void
    {
        global $config;

        $links = [
            'SauceNAO' => 'https://saucenao.com/search.php?url=' . url_escape(make_http($image->get_thumb_link())),
            'TinEye' => 'https://www.tineye.com/search/?url=' . url_escape(make_http($image->get_thumb_link())),
            'trace.moe' => 'https://trace.moe/?auto&url=' . url_escape(make_http($image->get_thumb_link())),
            'ascii2d' => 'https://ascii2d.net/search/url/' . url_escape(make_http($image->get_thumb_link())),
            'Yandex' => 'https://yandex.com/images/search?rpt=imageview&url=' . url_escape(make_http($image->get_thumb_link()))
        ];

        // only generate links for enabled reverse search services
        $enabled_services = $config->get_array(ReverseSearchLinksConfig::ENABLED_SERVICES);

        $html = "";
        foreach($links as $name => $link) {
            if (in_array($name, $enabled_services)) {
                $icon_link = make_link("/ext/reverse_search_links/icons/" . strtolower($name) . ".ico");
                $html .= "<a href='$link' class='reverse_image_link' rel='nofollow'><img title='Search with $name' src='$icon_link' alt='$name icon'></a>";
            }
        }

        $page->add_block(new Block("Reverse Image Search", $html, "main", 20));
    }
}
