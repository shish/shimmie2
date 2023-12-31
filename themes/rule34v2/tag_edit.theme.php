<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{TR, TH, TD, emptyHTML, rawHTML, joinHTML, DIV, INPUT, A};

class CustomTagEditTheme extends TagEditTheme
{
    public function get_tag_editor_html(Image $image): HTMLElement
    {
        global $user;
        return SHM_POST_INFO(
            "Tags",
            INPUT([
                "type" => "text",
                "name" => "tag_edit__tags",
                "value" => $image->get_tag_list(),
                "class" => "autocomplete_tags"
            ])
        );
    }

    public function get_source_editor_html(Image $image): HTMLElement
    {
        global $user;
        return SHM_POST_INFO(
            A(["href" => make_link("source_history/{$image->id}")], rawHTML("Source&nbsp;Link")),
            emptyHTML(
                DIV(
                    ["style" => "overflow: hidden; white-space: nowrap; max-width: 350px; text-overflow: ellipsis;"],
                    $this->format_source($image->get_source())
                ),
                $user->can(Permissions::EDIT_IMAGE_SOURCE) ? INPUT(["type" => "text", "name" => "tag_edit__source", "value" => $image->get_source()]) : null
            )
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
            emptyHTML(
                A(["class" => "username", "href" => make_link("user/$owner")], $owner),
                $ip,
                ", ",
                $date,
                $user->can(Permissions::EDIT_IMAGE_OWNER) ? INPUT(["type" => "text", "name" => "tag_edit__owner", "value" => $owner]) : null
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
            $user->can(Permissions::EDIT_IMAGE_LOCK) ?
                INPUT(["type" => "checkbox", "name" => "tag_edit__locked", "checked" => $image->is_locked()]) :
                emptyHTML($image->is_locked() ? "Yes (Only admins may edit these details)" : "No")
        );
    }
}
