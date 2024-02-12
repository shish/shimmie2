<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{TR, TH, TD, emptyHTML, rawHTML, joinHTML, DIV, INPUT, A, TEXTAREA};

class PostOwnerTheme extends Themelet
{
    public function get_owner_editor_html(Image $image): HTMLElement
    {
        global $user;
        $owner = $image->get_owner()->name;
        $date = rawHTML(autodate($image->posted));
        $ip = $user->can(Permissions::VIEW_IP) ? rawHTML(" (" . show_ip($image->owner_ip, "Post posted {$image->posted}") . ")") : "";
        $info = SHM_POST_INFO(
            "Uploader",
            emptyHTML(A(["class" => "username", "href" => make_link("user/$owner")], $owner), $ip, ", ", $date),
            $user->can(Permissions::EDIT_IMAGE_OWNER) ? INPUT(["type" => "text", "name" => "owner", "value" => $owner]) : null
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
}
