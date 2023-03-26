<?php

declare(strict_types=1);

namespace Shimmie2;

class HellBan extends Extension
{
    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $user;

        if ($user->can(Permissions::HELLBANNED)) {
            $s = "";
        } elseif ($user->can(Permissions::VIEW_HELLBANNED)) {
            $s = "DIV.hb, TR.hb TD {border: 1px solid red !important;}";
        } else {
            $s = ".hb {display: none !important;}";
        }

        if ($s) {
            $page->add_html_header("<style>$s</style>");
        }
    }
}
