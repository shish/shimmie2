<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{TR, TH, TD, emptyHTML, rawHTML, joinHTML, DIV, INPUT, A, TEXTAREA};

class TagEditTheme extends Themelet
{
    /*
     * Display a form which links to tag_edit/replace with POST[search]
     * and POST[replace] set appropriately
     */
    public function display_mass_editor()
    {
        global $page;
        $html = "
		" . make_form(make_link("tag_edit/replace")) . "
			<table class='form'>
				<tr><th>Search</th><td><input type='text' name='search' class='autocomplete_tags'></tr>
				<tr><th>Replace</th><td><input type='text' name='replace' class='autocomplete_tags'></td></tr>
				<tr><td colspan='2'><input type='submit' value='Replace'></td></tr>
			</table>
		</form>
		";
        $page->add_block(new Block("Mass Tag Edit", $html));
    }

    public function mss_html($terms): string
    {
        $h_terms = html_escape($terms);
        $html = make_form(make_link("tag_edit/mass_source_set"), "POST") . "
				<input type='hidden' name='tags' value='$h_terms'>
				<input type='text' name='source' value=''>
				<input type='submit' value='Set Source For All' onclick='return confirm(\"This will mass-edit all sources on the page.\\nAre you sure you want to do this?\")'>
			</form>
		";
        return $html;
    }

    public function get_tag_editor_html(Image $image): HTMLElement
    {
        global $user;

        $tag_links = [];
        foreach ($image->get_tag_array() as $tag) {
            $tag_links[] = A([
                "href" => search_link([$tag]),
                "class" => "tag",
                "title" => "View all posts tagged $tag"
            ], $tag);
        }

        return SHM_POST_INFO(
            "Tags",
            joinHTML(", ", $tag_links),
            $user->can(Permissions::EDIT_IMAGE_TAG) ? TEXTAREA([
                "class" => "autocomplete_tags",
                "type" => "text",
                "name" => "tag_edit__tags",
                "id" => "tag_editor",
            ], $image->get_tag_list()) : null,
            link: Extension::is_enabled(TagHistoryInfo::KEY) ?
                make_link("tag_history/{$image->id}") :
                null,
        );
    }

    public function get_user_editor_html(Image $image): HTMLElement
    {
        global $user;
        $owner = $image->get_owner()->name;
        $date = rawHTML(autodate($image->posted));
        $ip = $user->can(Permissions::VIEW_IP) ? rawHTML(" (" . show_ip($image->owner_ip, "Post posted {$image->posted}") . ")") : "";
        $info = SHM_POST_INFO(
            "Uploader",
            emptyHTML(A(["class" => "username", "href" => make_link("user/$owner")], $owner), $ip, ", ", $date),
            $user->can(Permissions::EDIT_IMAGE_OWNER) ? INPUT(["type" => "text", "name" => "tag_edit__owner", "value" => $owner]) : null
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

    public function get_source_editor_html(Image $image): HTMLElement
    {
        global $user;
        return SHM_POST_INFO(
            "Source Link",
            DIV(
                ["style" => "overflow: hidden; white-space: nowrap; max-width: 350px; text-overflow: ellipsis;"],
                $this->format_source($image->get_source())
            ),
            $user->can(Permissions::EDIT_IMAGE_SOURCE) ? INPUT(["type" => "text", "name" => "tag_edit__source", "value" => $image->get_source()]) : null,
            link: Extension::is_enabled(SourceHistoryInfo::KEY) ? make_link("source_history/{$image->id}") : null,
        );
    }

    protected function format_source(string $source = null): HTMLElement
    {
        if (!empty($source)) {
            if (!str_starts_with($source, "http://") && !str_starts_with($source, "https://")) {
                $source = "http://" . $source;
            }
            $proto_domain = explode("://", $source);
            $h_source = $proto_domain[1];
            if (str_ends_with($h_source, "/")) {
                $h_source = substr($h_source, 0, -1);
            }
            return A(["href" => $source], $h_source);
        }
        return rawHTML("Unknown");
    }

    public function get_lock_editor_html(Image $image): HTMLElement
    {
        global $user;
        return SHM_POST_INFO(
            "Locked",
            $image->is_locked() ? "Yes (Only admins may edit these details)" : "No",
            $user->can(Permissions::EDIT_IMAGE_LOCK) ? INPUT(["type" => "checkbox", "name" => "tag_edit__locked", "checked" => $image->is_locked()]) : null
        );
    }
}
