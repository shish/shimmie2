<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{A,BR,DIV,IMG,SPAN,rawHTML};

class Themelet extends BaseThemelet
{
    public function build_thumb_html(Image $image): HTMLElement
    {
        global $cache, $config;

        $cached = $cache->get("thumb-block:{$image->id}");
        if (!is_null($cached)) {
            return rawHTML($cached);
        }

        $id = $image->id;
        $view_link = make_link('post/view/'.$id);
        $image_link = $image->get_image_link();
        $thumb_link = $image->get_thumb_link();
        $tip = $image->get_tooltip();
        $tags = strtolower($image->get_tag_list());
        $ext = strtolower($image->get_ext());

        // If file is flash or svg then sets thumbnail to max size.
        if ($image->get_mime() === MimeType::FLASH || $image->get_mime() === MimeType::SVG) {
            $tsize = get_thumbnail_size($config->get_int('thumb_width'), $config->get_int('thumb_height'));
        } else {
            $tsize = get_thumbnail_size($image->width, $image->height);
        }

        $html = DIV(
            ['class' => 'shm-thumb thumb', 'data-ext' => $ext, 'data-tags' => $tags, 'data-post-id' => $id],
            A(
                ['class' => 'shm-thumb-link', 'href' => $view_link],
                IMG(['id' => "thumb_$id", 'title' => $tip, 'alt' => $tip, 'height' => $tsize[1], 'width' => $tsize[0], 'src' => $thumb_link, 'loading' => 'lazy'])
            ),
            BR(),
            A(['href' => $image_link], 'File Only'),
            SPAN(['class' => 'need-del'], ' - ', A(['href' => '#', 'onclick' => "image_hash_ban($id); return false;"], 'Ban'))
        );

        // cache for ages; will be cleared in ext/index:onImageInfoSet
        $cache->set("thumb-block:{$image->id}", (string)$html, rand(43200, 86400));

        return $html;
    }
}
