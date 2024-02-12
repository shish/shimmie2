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
        $page->set_heading(html_escape($image->get_tag_list()));
        $page->add_block(new Block("Search", $this->build_navigation($image), "left", 0));
        $page->add_block(new Block("Information", $this->build_information($image), "left", 15));
        $page->add_block(new Block(null, $this->build_info($image, $editor_parts), "main", 15));
    }

    private function build_information(Image $image): string
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
		ID: {$image->id}
		<br>Uploader: $h_ownerlink
		<br>Date: $h_date
		<br>Size: $h_filesize ({$image->width}x{$image->height})
		<br>Type: $h_type
		";

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
            // @phpstan-ignore-next-line - ???
            if (Extension::is_enabled(RatingsInfo::KEY)) {
                $h_rating = Ratings::rating_to_human($image['rating']);
                $html .= "<br>Rating: $h_rating";
            }
        }

        return $html;
    }

    protected function build_navigation(Image $image): string
    {
        //$h_pin = $this->build_pin($image);
        $h_search = "
			<form action='".search_link()."' method='GET'>
				<input name='search' type='text'  style='width:75%'>
				<input type='submit' value='Go' style='width:20%'>
				<input type='hidden' name='q' value='post/list'>
			</form>
		";

        return "$h_search";
    }
}
