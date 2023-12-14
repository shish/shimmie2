<?php

declare(strict_types=1);

namespace Shimmie2;

class CustomTagEditTheme extends TagEditTheme
{
    public function display_mass_editor()
    {
        global $page;
        $html = "
		".make_form(make_link("tag_edit/replace"))."
			<table class='form'>
				<tr><th>Search</th><td><input type='text' name='search' autocomplete='off'></tr>
				<tr><th>Replace</th><td><input type='text' name='replace' autocomplete='off'></td></tr>
				<tr><td colspan='2'><input type='submit' value='Replace'></td></tr>
			</table>
		</form>
		";
        $page->add_block(new Block("Mass Tag Edit", $html));
    }

    public function get_tag_editor_html(Image $image): \MicroHTML\HTMLElement
    {
        $h_tags = html_escape($image->get_tag_list());
        return \MicroHTML\rawHTML("
			<tr>
				<th width='50px'><a href='".make_link("tag_history/{$image->id}")."'>Tags</a></th>
				<td>
					<input type='text' name='tag_edit__tags' value='$h_tags'>
				</td>
			</tr>
		");
    }

    public function get_source_editor_html(Image $image): \MicroHTML\HTMLElement
    {
        global $user;
        $h_source = html_escape($image->get_source());
        $f_source = $this->format_source($image->get_source());
        $style = "overflow: hidden; white-space: nowrap; max-width: 350px; text-overflow: ellipsis;";
        return \MicroHTML\rawHTML("
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
		");
    }
}
