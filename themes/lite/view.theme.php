<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

class CustomViewPostTheme extends ViewPostTheme
{
    /**
     * @param HTMLElement[] $editor_parts
     */
    public function display_page(Image $image, array $editor_parts): void
    {
        global $page;
        $page->set_title("Post {$image->id}: ".$image->get_tag_list());
        $page->set_heading(html_escape($image->get_tag_list()));
        $page->add_block(new Block("Navigation", $this->build_navigation($image), "left", 0));
        $page->add_block(new Block("Statistics", $this->build_stats($image), "left", 15));
        $page->add_block(new Block(null, $this->build_info($image, $editor_parts), "main", 11));
        $page->add_block(new Block(null, $this->build_pin($image), "main", 11));
    }

    private function build_stats(Image $image): string
    {
        $h_owner = html_escape($image->get_owner()->name);
        $h_ownerlink = "<a href='".make_link("user/$h_owner")."'>$h_owner</a>";
        $h_ip = html_escape($image->owner_ip);
        $h_type = html_escape($image->get_mime());
        $h_date = autodate($image->posted);
        $h_filesize = to_shorthand_int($image->filesize);

        global $user;
        if ($user->can(Permissions::VIEW_IP)) {
            $h_ownerlink .= " ($h_ip)";
        }

        $html = "
		Id: {$image->id}
		<br>Posted: $h_date by $h_ownerlink
		<br>Size: {$image->width}x{$image->height}
		<br>Filesize: $h_filesize
		<br>Type: ".$h_type."
		";
        if ($image->video_codec != null) {
            $html .= "<br/>Video Codec: $image->video_codec";
        }
        if ($image->length != null) {
            $h_length = format_milliseconds($image->length);
            $html .= "<br/>Length: $h_length";
        }


        if (!is_null($image->source)) {
            $h_source = html_escape(make_http($image->source));
            $html .= "<br>Source: <a href='$h_source'>link</a>";
        }

        if (Extension::is_enabled(RatingsInfo::KEY)) {
            if ($image['rating'] === null || $image['rating'] == "?") {
                $image['rating'] = "?";
            }
            $h_rating = Ratings::rating_to_human($image['rating']);
            $html .= "<br>Rating: $h_rating";
        }

        return $html;
    }
}
