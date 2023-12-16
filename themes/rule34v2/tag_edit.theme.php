<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{TR, TH, TD, emptyHTML, rawHTML, joinHTML, DIV, INPUT, A};

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

    public function get_tag_editor_html(Image $image): HTMLElement
    {
        $h_tags = html_escape($image->get_tag_list());
        return rawHTML("
			<tr>
				<th width='50px'><a href='".make_link("tag_history/{$image->id}")."'>Tags</a></th>
				<td>
					<input type='text' name='tag_edit__tags' value='$h_tags'>
				</td>
			</tr>
		");
    }

    public function get_source_editor_html(Image $image): HTMLElement
    {
        global $user;
        $h_source = html_escape($image->get_source());
        $f_source = $this->format_source($image->get_source());
        $style = "overflow: hidden; white-space: nowrap; max-width: 350px; text-overflow: ellipsis;";
        return rawHTML("
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

    public function get_user_editor_html(Image $image): HTMLElement
    {
        global $user;
        $owner = $image->get_owner()->name;
        $date = rawHTML(autodate($image->posted));
        $ip = $user->can(Permissions::VIEW_IP) ? rawHTML(" (" . show_ip($image->owner_ip, "Post posted {$image->posted}") . ")") : "";
        $info = SHM_POST_INFO(
            "Uploader",
            $user->can(Permissions::EDIT_IMAGE_OWNER),
            emptyHTML(
                A(["class" => "username", "href" => make_link("user/$owner")], $owner),
                $ip,
                ", ",
                $date,
                INPUT(["type" => "text", "name" => "tag_edit__owner", "value" => $owner])
            ),
        );
        // SHM_POST_INFO returns a TR, let's sneakily append
        // a TD with the avatar in it
        $info->appendChild(
            TD(
                ["width" => "80px", "rowspan" => "4"],
                rawHTML($image->get_owner()->get_avatar_html())
            )
        );
        return $info;
    }

    public function get_lock_editor_html(Image $image): HTMLElement
    {
        global $user;
        return SHM_POST_INFO(
            "Locked",
            $user->can(Permissions::EDIT_IMAGE_LOCK),
            emptyHTML(
                INPUT(["type" => "checkbox", "name" => "tag_edit__locked", "checked" => $image->is_locked()]),
                $image->is_locked() ? "Yes (Only admins may edit these details)" : "No",
            ),
        );
    }
}
