<?php

declare(strict_types=1);

namespace Shimmie2;

class Themelet extends BaseThemelet
{
    public function build_thumb_html(Image $image): string
    {
        global $cache, $config;

        $cached = $cache->get("thumb-block:{$image->id}");
        if (!is_null($cached)) {
            return $cached;
        }

        $i_id = (int) $image->id;
        $h_view_link = make_link('post/view/'.$i_id);
        $h_image_link = $image->get_image_link();
        $h_thumb_link = $image->get_thumb_link();
        $h_tip = html_escape($image->get_tooltip());
        $h_tags = strtolower($image->get_tag_list());
        $h_ext = strtolower($image->get_ext());

        // If file is flash or svg then sets thumbnail to max size.
        if ($image->get_mime() === MimeType::FLASH || $image->get_mime() === MimeType::SVG) {
            $tsize = get_thumbnail_size($config->get_int('thumb_width'), $config->get_int('thumb_height'));
        } else {
            $tsize = get_thumbnail_size($image->width, $image->height);
        }

        $html = "<div class='shm-thumb thumb' data-ext=\"$h_ext\" data-tags=\"$h_tags\" data-post-id=\"$i_id\"><a class='shm-thumb-link' href='$h_view_link'>".
               '<img id="thumb_'.$i_id.'" title="'.$h_tip.'" alt="'.$h_tip.'" height="'.$tsize[1].'" width="'.$tsize[0].'" src="'.$h_thumb_link.'" loading="lazy"></a>'.
               '<br><a href="'.$h_image_link.'">File Only</a>'.
               "<span class='need-del'> - <a href='#' onclick='image_hash_ban($i_id); return false;'>Ban</a></span>".
               "</div>\n";

        // cache for ages; will be cleared in ext/index:onImageInfoSet
        $cache->set("thumb-block:{$image->id}", $html, rand(43200, 86400));

        return $html;
    }
}
