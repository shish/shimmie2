<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\emptyHTML;
use function MicroHTML\rawHTML;
use function MicroHTML\TABLE;
use function MicroHTML\TBODY;
use function MicroHTML\TFOOT;
use function MicroHTML\TR;
use function MicroHTML\TH;
use function MicroHTML\TD;
use function MicroHTML\LABEL;
use function MicroHTML\INPUT;
use function MicroHTML\SMALL;
use function MicroHTML\A;
use function MicroHTML\BR;
use function MicroHTML\P;
use function MicroHTML\SELECT;
use function MicroHTML\OPTION;

class CustomUserPageTheme extends UserPageTheme
{
    // Override to display user block in the head and in the left column
    // (with css media queries deciding which one is visible), and also
    // to switch between new-line and inline display depending on the
    // number of links.
    public function display_user_block(Page $page, User $user, $parts)
    {
        $h_name = html_escape($user->name);
        $lines = [];
        foreach ($parts as $part) {
            if ($part["name"] == "User Options") {
                continue;
            }
            $lines[] = "<a href='{$part["link"]}'>{$part["name"]}</a>";
        }
        if (count($lines) < 6) {
            $html = implode("\n<br>", $lines);
        } else {
            $html = implode(" | \n", $lines);
        }
        $page->add_block(new Block("Logged in as $h_name", $html, "head", 90, "UserBlockhead"));
        $page->add_block(new Block("Logged in as $h_name", $html, "left", 15, "UserBlockleft"));
    }

    // Override to display login block in the head and in the left column
    // (with css media queries deciding which one is visible)
    public function display_login_block(Page $page)
    {
        $page->add_block(new Block("Login", $this->create_login_block(), "head", 90));
        $page->add_block(new Block("Login", $this->create_login_block(), "left", 15));
    }
}
