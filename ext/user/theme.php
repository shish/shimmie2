<?php declare(strict_types=1);
use function \MicroHTML\emptyHTML;
use function \MicroHTML\rawHTML;
use function \MicroHTML\TABLE;
use function \MicroHTML\TBODY;
use function \MicroHTML\TFOOT;
use function \MicroHTML\TR;
use function \MicroHTML\TH;
use function \MicroHTML\TD;
use function \MicroHTML\LABEL;
use function \MicroHTML\INPUT;
use function \MicroHTML\SMALL;
use function \MicroHTML\A;
use function \MicroHTML\BR;
use function \MicroHTML\P;
use function \MicroHTML\SELECT;
use function \MicroHTML\OPTION;
use \MicroHTML\HTMLElement;

class UserPageTheme extends Themelet
{
    public function display_login_page(Page $page)
    {
        $page->set_title("Login");
        $page->set_heading("Login");
        $page->add_block(new NavBlock());
        $page->add_block(new Block(
            "Login There",
            "There should be a login box to the left"
        ));
    }

    public function display_user_list(Page $page, $table, $paginator)
    {
        $page->set_title("User List");
        $page->set_heading("User List");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Users", $table . $paginator));
    }

    public function display_user_links(Page $page, User $user, $parts)
    {
        # $page->add_block(new Block("User Links", join(", ", $parts), "main", 10));
    }

    public function display_user_block(Page $page, User $user, $parts)
    {
        $html = emptyHTML('Logged in as ', $user->name);
        foreach ($parts as $part) {
            $html->appendChild(BR());
            $html->appendChild(A(["href"=>$part["link"]], $part["name"]));
        }
        $b = new Block("User Links", (string)$html, "left", 90);
        $b->is_content = false;
        $page->add_block($b);
    }

    public function display_signup_page(Page $page)
    {
        global $config;
        $tac = $config->get_string("login_tac", "");

        if ($config->get_bool("login_tac_bbcode")) {
            $tfe = new TextFormattingEvent($tac);
            send_event($tfe);
            $tac = $tfe->formatted;
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
                        TH(rawHTML("Email&nbsp;(Optional)")),
                        TD(INPUT(["type"=>'email', "name"=>'email']))
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
                        TH(rawHTML("Email&nbsp;(Optional)")),
                        TD(INPUT(["type"=>'email', "name"=>'email']))
                    ),
                ),
                TFOOT(
                    TR(TD(["colspan"=>"2"], INPUT(["type"=>"submit", "value"=>"Create Account"])))
                )
            )
        );
        $page->add_block(new Block("Create User", (string)$form, "main", 75));
    }

    public function display_signups_disabled(Page $page)
    {
        $page->set_title("Signups Disabled");
        $page->set_heading("Signups Disabled");
        $page->add_block(new NavBlock());
        $page->add_block(new Block(
            "Signups Disabled",
            "The board admin has disabled the ability to create new accounts~"
        ));
    }

    public function display_login_block(Page $page)
    {
        global $config, $user;
        $form = SHM_SIMPLE_FORM(
            "user_admin/login",
            TABLE(
                ["style"=>"width: 100%", "class"=>"form"],
                TBODY(
                    TR(
                        TH(LABEL(["for"=>"user"], "Name")),
                        TD(INPUT(["id"=>"user", "type"=>"text", "name"=>"user", "autocomplete"=>"username"]))
                    ),
                    TR(
                        TH(LABEL(["for"=>"pass"], "Password")),
                        TD(INPUT(["id"=>"pass", "type"=>"password", "name"=>"pass", "autocomplete"=>"current-password"]))
                    )
                ),
                TFOOT(
                    TR(TD(["colspan"=>"2"], INPUT(["type"=>"submit", "value"=>"Log In"])))
                )
            )
        );

        $html = emptyHTML();
        $html->appendChild($form);
        if ($config->get_bool("login_signup_enabled") && $user->can(Permissions::CREATE_USER)) {
            $html->appendChild(SMALL(A(["href"=>make_link("user_admin/create")], "Create Account")));
        }

        $page->add_block(new Block("Login", (string)$html, "left", 90));
    }

    private function _ip_list(string $name, array $ips): HTMLElement
    {
        $td = TD("$name: ");
        $n = 0;
        foreach ($ips as $ip => $count) {
            $td->appendChild(BR());
            $td->appendChild("$ip ($count)");
            if (++$n >= 20) {
                $td->appendChild(BR());
                $td->appendChild("...");
                break;
            }
        }
        return $td;
    }

    public function display_ip_list(Page $page, array $uploads, array $comments, array $events)
    {
        $html = TABLE(
            ["id"=>"ip-history"],
            TR(
                $this->_ip_list("Uploaded from", $uploads),
                $this->_ip_list("Commented from", $comments),
                $this->_ip_list("Logged Events", $events)
            ),
            TR(
                TD(["colspan"=>"3"], "(Most recent at top)")
            )
        );

        $page->add_block(new Block("IPs", (string)$html, "main", 70));
    }

    public function display_user_page(User $duser, $stats)
    {
        global $page;
        assert(is_array($stats));
        $stats[] = 'User ID: '.$duser->id;

        $page->set_title(html_escape($duser->name)."'s Page");
        $page->set_heading(html_escape($duser->name)."'s Page");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Stats", join("<br>", $stats), "main", 10));
    }


    public function build_operations(User $duser, UserOperationsBuildingEvent $event): string
    {
        global $config, $user;
        $html = emptyHTML();

        // just a fool-admin protection so they dont mess around with anon users.
        if ($duser->id != $config->get_int('anon_id')) {
            if ($user->can(Permissions::EDIT_USER_NAME)) {
                $html->appendChild(SHM_USER_FORM(
                    $duser,
                    "user_admin/change_name",
                    "Change Name",
                    TBODY(TR(
                        TH("New name"),
                        TD(INPUT(["type"=>'text', "name"=>'name', "value"=>$duser->name]))
                    )),
                    "Set"
                ));
            }

            $html->appendChild(SHM_USER_FORM(
                $duser,
                "user_admin/change_pass",
                "Change Password",
                TBODY(
                    TR(
                        TH("Password"),
                        TD(INPUT(["type"=>'password', "name"=>'pass1', "autocomplete"=>'new-password']))
                    ),
                    TR(
                        TH("Repeat Password"),
                        TD(INPUT(["type"=>'password', "name"=>'pass2', "autocomplete"=>'new-password']))
                    ),
                ),
                "Set"
            ));

            $html->appendChild(SHM_USER_FORM(
                $duser,
                "user_admin/change_email",
                "Change Email",
                TBODY(TR(
                    TH("Address"),
                    TD(INPUT(["type"=>'text', "name"=>'address', "value"=>$duser->email, "autocomplete"=>'email', "inputmode"=>'email']))
                )),
                "Set"
            ));

            if ($user->can(Permissions::EDIT_USER_CLASS)) {
                global $_shm_user_classes;
                $select = SELECT(["name"=>"class"]);
                foreach ($_shm_user_classes as $name => $values) {
                    $select->appendChild(
                        OPTION(["value"=>$name, "selected"=>$name == $duser->class->name], ucwords($name))
                    );
                }
                $html->appendChild(SHM_USER_FORM(
                    $duser,
                    "user_admin/change_class",
                    "Change Class",
                    TBODY(TR(TD($select))),
                    "Set"
                ));
            }

            if ($user->can(Permissions::DELETE_USER)) {
                $html->appendChild(SHM_USER_FORM(
                    $duser,
                    "user_admin/delete_user",
                    "Delete User",
                    TBODY(
                        TR(TD(LABEL(INPUT(["type"=>'checkbox', "name"=>'with_images']), "Delete images"))),
                        TR(TD(LABEL(INPUT(["type"=>'checkbox', "name"=>'with_comments']), "Delete comments"))),
                    ),
                    TFOOT(
                        TR(TD(INPUT(["type"=>'button', "class"=>'shm-unlocker', "data-unlock-sel"=>'.deluser', "value"=>'Unlock']))),
                        TR(TD(INPUT(["type"=>'submit', "class"=>'deluser', "value"=>'Delete User', "disabled"=>'true']))),
                    )
                ));
            }

            foreach ($event->parts as $part) {
                $html .= $part;
            }
        }
        return (string)$html;
    }

    public function get_help_html(): HTMLElement
    {
        global $user;
        $output = emptyHTML(P("Search for posts posted by particular individuals."));
        $output->appendChild(SHM_COMMAND_EXAMPLE(
            "poster=username",
            'Returns posts posted by "username".'
        ));
        $output->appendChild(SHM_COMMAND_EXAMPLE(
            "poster_id=123",
            'Returns posts posted by user 123.'
        ));

        if ($user->can(Permissions::VIEW_IP)) {
            $output->appendChild(SHM_COMMAND_EXAMPLE(
                "poster_ip=127.0.0.1",
                "Returns posts posted from IP 127.0.0.1."
            ));
        }
        return $output;
    }
}
