<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, INPUT, emptyHTML};

use MicroHTML\HTMLElement;

class PostOwnerTheme extends Themelet
{
    public function get_owner_editor_html(Image $image): HTMLElement
    {
        $owner = $image->get_owner()->name;
        $date = SHM_DATE($image->posted);
        $ip = Ctx::$user->can(IPBanPermission::VIEW_IP) ? emptyHTML(" (", SHM_IP($image->owner_ip, "Post posted {$image->posted}"), ")") : null;
        return SHM_POST_INFO(
            "Uploader",
            emptyHTML(A(["class" => "username", "href" => make_link("user/$owner")], $owner), $ip, ", ", $date),
            Ctx::$user->can(PostOwnerPermission::EDIT_IMAGE_OWNER) ? INPUT(["type" => "text", "name" => "owner", "value" => $owner]) : null
        );
    }
}
