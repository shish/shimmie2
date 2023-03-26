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
    public function display_user_block(Page $page, User $user, $parts)
    {
        $h_name = html_escape($user->name);
        $lines = [];
        foreach ($parts as $part) {
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

    public function display_login_block(Page $page)
    {
        global $config;
        $html = "
			<form action='".make_link("user_admin/login")."' method='POST'>
				<table class='form' style='width: 100%;'>
					<tr><th>Name</th><td><input type='text' name='user'></td></tr>
					<tr><th>Password</th><td><input type='password' name='pass'></td></tr>
					<tr><td colspan='2'><input type='submit' name='gobu' value='Log In'></td></tr>
				</table>
			</form>
		";
        if ($config->get_bool("login_signup_enabled")) {
            $html .= "<small><a href='".make_link("user_admin/create")."'>Create Account</a></small>";
        }
        $page->add_block(new Block("Login", $html, "head", 90));
        $page->add_block(new Block("Login", $html, "left", 15));
    }

    public function display_signup_page(Page $page)
    {
        global $config;
        $tac = $config->get_string("login_tac", "");

        if ($config->get_bool("login_tac_bbcode")) {
            $tac = send_event(new TextFormattingEvent($tac))->formatted;
        }

        $form = SHM_SIMPLE_FORM(
            "user_admin/create",
            TABLE(
                ["class"=>"form"],
                TBODY(
                    TR(
                        TH("Name"),
                        TD(INPUT(["type"=>'text', "name"=>'name', "required"=>true]))
                    ),
                    TR(
                        TH("Password"),
                        TD(INPUT(["type"=>'password', "name"=>'pass1', "required"=>true]))
                    ),
                    TR(
                        TH(rawHTML("Repeat&nbsp;Password")),
                        TD(INPUT(["type"=>'password', "name"=>'pass2', "required"=>true]))
                    ),
                    TR(
                        TH(rawHTML("Email")),
                        TD(INPUT(["type"=>'email', "name"=>'email', "required"=>true]))
                    ),
                    TR(
                        TD(["colspan"=>"2"], rawHTML(captcha_get_html()))
                    ),
                ),
                TFOOT(
                    TR(TD(["colspan"=>"2"], INPUT(["type"=>"submit", "value"=>"Create Account"])))
                )
            )
        );

        $html = emptyHTML(
            $tac ? P(rawHTML($tac)) : null,
            $form
        );

        $page->set_title("Create Account");
        $page->set_heading("Create Account");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Signup", (string)$html));
    }

    public function display_user_creator()
    {
        global $page;

        $form = SHM_SIMPLE_FORM(
            "user_admin/create_other",
            TABLE(
                ["class"=>"form"],
                TBODY(
                    TR(
                        TH("Name"),
                        TD(INPUT(["type"=>'text', "name"=>'name', "required"=>true]))
                    ),
                    TR(
                        TH("Password"),
                        TD(INPUT(["type"=>'password', "name"=>'pass1', "required"=>true]))
                    ),
                    TR(
                        TH(rawHTML("Repeat&nbsp;Password")),
                        TD(INPUT(["type"=>'password', "name"=>'pass2', "required"=>true]))
                    ),
                    TR(
                        TH(rawHTML("Email")),
                        TD(INPUT(["type"=>'email', "name"=>'email']))
                    ),
                    TR(
                        TD(["colspan"=>2], rawHTML("(Email is optional for admin-created accounts)")),
                    ),
                ),
                TFOOT(
                    TR(TD(["colspan"=>"2"], INPUT(["type"=>"submit", "value"=>"Create Account"])))
                )
            )
        );
        $page->add_block(new Block("Create User", (string)$form, "main", 75));
    }
}
