<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\A;
use function MicroHTML\INPUT;
use function MicroHTML\SMALL;
use function MicroHTML\TABLE;
use function MicroHTML\TD;
use function MicroHTML\TR;
use function MicroHTML\joinHTML;

class WarmUserPageTheme extends UserPageTheme
{
    /**
     * @param array<array{link: Url, name: string}> $parts
     */
    public function display_user_block(User $user, array $parts): void
    {
        global $page;
        $parts_html = [];
        foreach ($parts as $part) {
            $parts_html[] = A(["href" => $part["link"]], $part["name"]);
        }
        $page->add_block(new Block("Logged in as {$user->name}", joinHTML(" | ", $parts_html), "head", 90));
    }

    public function display_login_block(): void
    {
        global $config, $page;
        $html = SHM_SIMPLE_FORM(
            make_link("user_admin/login"),
            TABLE(
                ["summary" => "Login Form", "align" => "center"],
                TR(
                    TD(["width" => "70"], "Name"),
                    TD(["width" => "70"], INPUT(["type" => "text", "name" => "user"]))
                ),
                TR(
                    TD("Password"),
                    TD(INPUT(["type" => "password", "name" => "pass"]))
                ),
                TR(
                    TD(["colspan" => "2"], SHM_SUBMIT("Log In"))
                )
            )
        );
        if ($config->get_bool(UserAccountsConfig::SIGNUP_ENABLED)) {
            $html->appendChild(SMALL(A(["href" => make_link("user_admin/create")], "Create Account")));
        }
        $page->add_block(new Block("Login", $html, "head", 90));
    }
}
