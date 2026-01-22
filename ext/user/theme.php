<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, BR, INPUT, LABEL, OPTION, P, SELECT, SMALL, TABLE, TBODY, TD, TFOOT, TH, TR, emptyHTML, joinHTML};

use MicroHTML\HTMLElement;

class UserPageTheme extends Themelet
{
    public function display_login_page(): void
    {
        Ctx::$page->set_title("Login");
        Ctx::$page->add_block(new Block(
            "Login There",
            emptyHTML("There should be a login box to the left")
        ));
    }

    public function display_signup_page(): void
    {
        $tac = Ctx::$config->get(UserAccountsConfig::LOGIN_TAC) ?? "";

        if (Ctx::$config->get(UserAccountsConfig::LOGIN_TAC_BBCODE)) {
            $tac = format_text($tac);
        }

        $email_required = (
            Ctx::$config->get(UserAccountsConfig::USER_EMAIL_REQUIRED) &&
            !Ctx::$user->can(UserAccountsPermission::CREATE_OTHER_USER)
        );
        $captcha = Captcha::get_html(UserAccountsPermission::SKIP_SIGNUP_CAPTCHA);

        $form = SHM_SIMPLE_FORM(
            make_link("user_admin/create"),
            TABLE(
                ["class" => "form"],
                TBODY(
                    TR(
                        TH("Name"),
                        TD(INPUT(["type" => 'text', "name" => 'name', "required" => true]))
                    ),
                    TR(
                        TH("Password"),
                        TD(INPUT(["type" => 'password', "name" => 'pass1', "required" => true]))
                    ),
                    TR(
                        TH(\MicroHTML\rawHTML("Repeat&nbsp;Password")),
                        TD(INPUT(["type" => 'password', "name" => 'pass2', "required" => true]))
                    ),
                    TR(
                        TH($email_required ? "Email" : \MicroHTML\rawHTML("Email&nbsp;(Optional)")),
                        TD(INPUT(["type" => 'email', "name" => 'email', "required" => $email_required]))
                    ),
                    $captcha ? TR(
                        TD(["colspan" => "2"], $captcha)
                    ) : null,
                ),
                TFOOT(
                    TR(TD(["colspan" => "2"], INPUT(["type" => "submit", "value" => "Create Account"])))
                )
            )
        );

        $html = emptyHTML(
            $tac ? P($tac) : null,
            $form
        );

        Ctx::$page->set_title("Create Account");
        Ctx::$page->add_block(new Block("Signup", $html));
    }

    public function display_user_creator(): void
    {
        $form = SHM_SIMPLE_FORM(
            make_link("user_admin/create_other"),
            TABLE(
                ["class" => "form"],
                TBODY(
                    TR(
                        TH("Name"),
                        TD(INPUT(["type" => 'text', "name" => 'name', "required" => true]))
                    ),
                    TR(
                        TH("Password"),
                        TD(INPUT(["type" => 'password', "name" => 'pass1', "required" => true]))
                    ),
                    TR(
                        TH(\MicroHTML\rawHTML("Repeat&nbsp;Password")),
                        TD(INPUT(["type" => 'password', "name" => 'pass2', "required" => true]))
                    ),
                    TR(
                        TH("Email"),
                        TD(INPUT(["type" => 'email', "name" => 'email']))
                    ),
                    TR(
                        TD(["colspan" => 2], "(Email is optional for admin-created accounts)"),
                    ),
                ),
                TFOOT(
                    TR(TD(["colspan" => "2"], INPUT(["type" => "submit", "value" => "Create Account"])))
                )
            )
        );
        Ctx::$page->add_block(new Block("Create User", $form, "main", 75));
    }

    public function display_signups_disabled(): void
    {
        Ctx::$page->set_title("Signups Disabled");
        Ctx::$page->add_block(new Block(
            "Signups Disabled",
            format_text(Ctx::$config->get(UserAccountsConfig::SIGNUP_DISABLED_MESSAGE)),
        ));
    }

    public function display_login_block(): void
    {
        Ctx::$page->add_block(new Block("Login", $this->create_login_block(), "left", 90));
    }

    public function create_login_block(): HTMLElement
    {
        $captcha = Captcha::get_html(UserAccountsPermission::SKIP_LOGIN_CAPTCHA);

        $form = SHM_SIMPLE_FORM(
            make_link("user_admin/login"),
            TABLE(
                ["style" => "width: 100%", "class" => "form"],
                TBODY(
                    TR(
                        TH(LABEL(["for" => "user"], "Name")),
                        TD(INPUT(["id" => "user", "type" => "text", "name" => "user", "autocomplete" => "username", "required" => true]))
                    ),
                    TR(
                        TH(LABEL(["for" => "pass"], "Password")),
                        TD(INPUT(["id" => "pass", "type" => "password", "name" => "pass", "autocomplete" => "current-password", "required" => true]))
                    ),
                    $captcha ? TR(
                        TH(LABEL(["for" => "captcha"], "Captcha")),
                        TD($captcha)
                    ) : null
                ),
                TFOOT(
                    TR(TD(["colspan" => "2"], INPUT(["type" => "submit", "value" => "Log In"])))
                )
            )
        );

        $html = emptyHTML();
        $html->appendChild($form);
        if (Ctx::$config->get(UserAccountsConfig::SIGNUP_ENABLED) && Ctx::$user->can(UserAccountsPermission::CREATE_USER)) {
            $html->appendChild(SMALL(A(["href" => make_link("user_admin/create")], "Create Account")));
        }

        return $html;
    }

    /**
     * @param array<string, int> $ips
     */
    protected function _ip_list(string $name, array $ips): HTMLElement
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

    /**
     * @param array<string, int> $uploads
     * @param array<string, int> $comments
     * @param array<string, int> $events
     */
    public function display_ip_list(array $uploads, array $comments, array $events): void
    {
        $html = TABLE(
            ["id" => "ip-history"],
            TR(
                $this->_ip_list("Uploaded from", $uploads),
                $this->_ip_list("Commented from", $comments),
                $this->_ip_list("Logged Events", $events)
            ),
            TR(
                TD(["colspan" => "3"], "(Most recent at top)")
            )
        );

        Ctx::$page->add_block(new Block("IPs", $html, "main", 70));
    }

    /**
     * @param array<HTMLElement|string> $stats
     */
    public function display_user_page(User $duser, array $stats): void
    {
        $stats[] = emptyHTML('User ID: '.$duser->id);

        Ctx::$page->set_title("{$duser->name}'s Page");
        Ctx::$page->add_block(new Block("Stats", joinHTML(BR(), $stats), "main", 10));
    }


    public function build_operations(User $duser, UserOperationsBuildingEvent $event): HTMLElement
    {
        $html = emptyHTML();

        // just a fool-admin protection so they dont mess around with anon users.
        if ($duser->id !== Ctx::$config->get(UserAccountsConfig::ANON_ID)) {
            if (Ctx::$user->can(UserAccountsPermission::EDIT_USER_NAME)) {
                $html->appendChild(SHM_USER_FORM(
                    $duser,
                    make_link("user_admin/change_name"),
                    "Change Name",
                    TBODY(TR(
                        TH("New name"),
                        TD(INPUT(["type" => 'text', "name" => 'name', "value" => $duser->name]))
                    )),
                    "Set"
                ));
            }

            $html->appendChild(SHM_USER_FORM(
                $duser,
                make_link("user_admin/change_pass"),
                "Change Password",
                TBODY(
                    TR(
                        TH("Password"),
                        TD(INPUT(["type" => 'password', "name" => 'pass1', "autocomplete" => 'new-password']))
                    ),
                    TR(
                        TH("Repeat password"),
                        TD(INPUT(["type" => 'password', "name" => 'pass2', "autocomplete" => 'new-password']))
                    ),
                ),
                "Set"
            ));

            $html->appendChild(SHM_USER_FORM(
                $duser,
                make_link("user_admin/change_email"),
                "Change Email",
                TBODY(TR(
                    TH("Address"),
                    TD(INPUT(["type" => 'text', "name" => 'address', "value" => $duser->email, "autocomplete" => 'email', "inputmode" => 'email']))
                )),
                "Set"
            ));

            if (Ctx::$user->can(UserAccountsPermission::EDIT_USER_CLASS)) {
                $select = SELECT(["name" => "class"]);
                foreach (UserClass::$known_classes as $name => $values) {
                    $select->appendChild(
                        OPTION(["value" => $name, "selected" => $name === $duser->class->name], ucwords($name))
                    );
                }
                $html->appendChild(SHM_USER_FORM(
                    $duser,
                    make_link("user_admin/change_class"),
                    "Change Class",
                    TBODY(TR(TD($select))),
                    "Set"
                ));
            }

            if (Ctx::$user->can(UserAccountsPermission::DELETE_USER)) {
                $html->appendChild(SHM_USER_FORM(
                    $duser,
                    make_link("user_admin/delete_user"),
                    "Delete User",
                    TBODY(
                        TR(TD(LABEL(INPUT(["type" => 'checkbox', "name" => 'with_images']), "Delete images"))),
                        TR(TD(LABEL(INPUT(["type" => 'checkbox', "name" => 'with_comments']), "Delete comments"))),
                    ),
                    TFOOT(
                        TR(TD(INPUT(["type" => 'button', "class" => 'shm-unlocker', "data-unlock-sel" => '.deluser', "value" => 'Unlock']))),
                        TR(TD(INPUT(["type" => 'submit', "class" => 'deluser', "value" => 'Delete User', "disabled" => 'true']))),
                    )
                ));
            }

            foreach ($event->get_parts() as $part) {
                $html->appendChild($part);
            }
        }
        return $html;
    }

    public function get_help_html(): HTMLElement
    {
        return emptyHTML(
            P("Search for posts posted by particular individuals."),
            SHM_COMMAND_EXAMPLE("poster=username", 'Returns posts posted by "username"'),
            // SHM_COMMAND_EXAMPLE("poster_id=123", 'Returns posts posted by user 123'),
            Ctx::$user->can(IPBanPermission::VIEW_IP)
                ? SHM_COMMAND_EXAMPLE("poster_ip=127.0.0.1", "Returns posts posted from IP 127.0.0.1.")
                : null
        );
    }
}
