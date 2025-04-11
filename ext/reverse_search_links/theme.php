<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, IMG, joinHTML};

class ReverseSearchLinksTheme extends Themelet
{
    public function reverse_search_block(Image $image): void
    {
        $url = (string)$image->get_thumb_link()->asAbsolute();
        $links = [
            'SauceNAO' => Url::parse('https://saucenao.com/search.php')->withModifiedQuery(["url" => $url]),
            'TinEye' => Url::parse('https://www.tineye.com/search/')->withModifiedQuery(["url" => $url]),
            'trace.moe' => Url::parse('https://trace.moe/')->withModifiedQuery(["auto" => "", "url" => $url]),
            'ascii2d' => Url::parse('https://ascii2d.net/search/url/' . url_escape($url)),
            'Yandex' => Url::parse('https://yandex.com/images/search')->withModifiedQuery(["rpt" => "imageview", "url" => $url]),
        ];

        // only generate links for enabled reverse search services
        $enabled_services = Ctx::$config->get(ReverseSearchLinksConfig::ENABLED_SERVICES);

        $parts = [];
        foreach ($links as $name => $link) {
            if (in_array($name, $enabled_services)) {
                $parts[] = A(
                    ["href" => $link, "class" => "reverse_image_link", "rel" => "nofollow"],
                    IMG([
                        "title" => "Search with $name",
                        "src" => Url::base() . "/ext/reverse_search_links/icons/" . strtolower($name) . ".ico",
                        "alt" => "$name icon"
                    ])
                );
            }
        }

        Ctx::$page->add_block(new Block("Reverse Image Search", joinHTML("", $parts), "main", 25));
    }
}
