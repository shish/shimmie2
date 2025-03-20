<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\A;
use function MicroHTML\INPUT;
use function MicroHTML\LABEL;
use function MicroHTML\SMALL;
use function MicroHTML\TABLE;
use function MicroHTML\TD;
use function MicroHTML\TR;
use function MicroHTML\joinHTML;

class LiteUserPageTheme extends UserPageTheme
{
    public function display_login_page(): void
    {
        global $config, $page;
        $page->set_title("Login");
        $page->set_layout("no-left");
        $html = SHM_SIMPLE_FORM(
            make_link("user_admin/login"),
            TABLE(
                ["summary" => "Login Form"],
                TR(
                    TD(["width" => "70"], LABEL(["for" => "user"], "Name")),
                    TD(["width" => "70"], INPUT(["type" => "text", "name" => "user", "id" => "user"]))
                ),
                TR(
                    TD(LABEL(["for" => "pass"], "Password")),
                    TD(INPUT(["type" => "password", "name" => "pass", "id" => "pass"]))
                ),
                TR(
                    TD(["colspan" => "2"], SHM_SUBMIT("Log In"))
                )
            )
        );
        if ($config->get_bool(UserAccountsConfig::SIGNUP_ENABLED)) {
            $html->appendChild(SMALL(A(["href" => make_link("user_admin/create")], "Create Account")));
        }
        $page->add_block(new Block("Login", $html, "main", 90));
    }

    /**
     * @param array<int, array{name: string, link: Url}> $parts
     */
    public function display_user_links(User $user, array $parts): void
    {
        // no block in this theme
    }

    public function display_login_block(): void
    {
        // no block in this theme
    }

    /**
     * @param array<array{link: Url, name: string}> $parts
     */
    public function display_user_block(User $user, array $parts): void
    {
        global $page;
        $html = [];
        $blocked = ["Pools", "Pool Changes", "Alias Editor", "My Profile"];
        foreach ($parts as $part) {
            if (in_array($part["name"], $blocked)) {
                continue;
            }
            $html[] = A(["href" => $part["link"], "class" => "tab"], $part["name"]);
        }
        $b = new Block("User Links", joinHTML(" ", $html), "user", 90);
        $b->is_content = false;
        $page->add_block($b);
    }

    public function display_signup_page(): void
    {
        global $page;
        $page->set_layout("no-left");
        parent::display_signup_page();
    }

    /**
     * @param \MicroHTML\HTMLElement[] $stats
     */
    public function display_user_page(User $duser, array $stats): void
    {
        global $page;
        $page->set_layout("no-left");
        parent::display_user_page($duser, $stats);
    }
}
