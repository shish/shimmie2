<?php

declare(strict_types=1);

namespace Shimmie2;

class CustomTagEditTheme extends TagEditTheme
{
    public function get_tag_editor_html(Image $image): string
    {
        $h_tags = html_escape($image->get_tag_list());
        return "
			<tr>
				<th width='50px'><a href='".make_link("tag_history/{$image->id}")."'>Tags</a></th>
				<td>
					<input type='text' name='tag_edit__tags' value='$h_tags'>
				</td>
			</tr>
		";
    }

    public function get_source_editor_html(Image $image): string
    {
        global $user;
        $h_source = html_escape($image->get_source());
        $f_source = $this->format_source($image->get_source());
        $style = "overflow: hidden; white-space: nowrap; max-width: 350px; text-overflow: ellipsis;";
        return "
			<tr>
				<th><a href='".make_link("source_history/{$image->id}")."'>Source&nbsp;Link</a></th>
				<td>
		".($user->can("edit_image_source") ? "
					<div class='view' style='$style'>$f_source</div>
					<input class='edit' type='text' name='tag_edit__source' value='$h_source'>
		" : "
					<div style='$style'>$f_source</div>
		")."
				</td>
			</tr>
		";
    }
}
