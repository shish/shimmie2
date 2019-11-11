<?php

require_once "events.php";

class UserCreationException extends SCoreException
{
}

class NullUserException extends SCoreException
{
}

class UserPage extends Extension
{
    /** @var UserPageTheme $theme */
    public $theme;

    public function onInitExt(InitExtEvent $event)
    {
        global $config;
        $config->set_default_bool("login_signup_enabled", true);
        $config->set_default_int("login_memory", 365);
        $config->set_default_string("avatar_host", "none");
        $config->set_default_int("avatar_gravatar_size", 80);
        $config->set_default_string("avatar_gravatar_default", "");
        $config->set_default_string("avatar_gravatar_rating", "g");
        $config->set_default_bool("login_tac_bbcode", true);
    }

    public function onUserLogin(UserLoginEvent $event)
    {
        global $user;
        $user = $event->user;
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $config, $database, $page, $user;

        $this->show_user_info();

        if ($event->page_matches("user_admin")) {
            if ($event->get_arg(0) == "login") {
                if (isset($_POST['user']) && isset($_POST['pass'])) {
                    $this->page_login($_POST['user'], $_POST['pass']);
                } else {
                    $this->theme->display_login_page($page);
                }
            } elseif ($event->get_arg(0) == "recover") {
                $this->page_recover($_POST['username']);
            } elseif ($event->get_arg(0) == "create") {
                $this->page_create();
            } elseif ($event->get_arg(0) == "list") {
                $limit = 50;

                $page_num = $event->try_page_num(1);
                $offset = ($page_num-1) * $limit;

                $q = "WHERE 1=1";
                $a = [];

                if (@$_GET['username']) {
                    $q .= " AND SCORE_STRNORM(name) LIKE SCORE_STRNORM(:name)";
                    $a["name"] = '%' . $_GET['username'] . '%';
                }

                if ($user->can(Permissions::DELETE_USER) && @$_GET['email']) {
                    $q .= " AND SCORE_STRNORM(email) LIKE SCORE_STRNORM(:email)";
                    $a["email"] = '%' . $_GET['email'] . '%';
                }

                if (@$_GET['class']) {
                    $q .= " AND class LIKE :class";
                    $a["class"] = $_GET['class'];
                }
                $where = $database->scoreql_to_sql($q);

                $count = $database->get_one("SELECT count(*) FROM users $where", $a);
                $a["offset"] = $offset;
                $a["limit"] = $limit;
                $rows = $database->get_all("SELECT * FROM users $where LIMIT :limit OFFSET :offset", $a);
                $users = array_map("_new_user", $rows);
                $this->theme->display_user_list($page, $users, $user, $page_num, $count/$limit);
            } elseif ($event->get_arg(0) == "logout") {
                $this->page_logout();
            }

            if (!$user->check_auth_token()) {
                return;
            } elseif ($event->get_arg(0) == "change_name") {
                $input = validate_input([
                    'id' => 'user_id,exists',
                    'name' => 'user_name',
                ]);
                $duser = User::by_id($input['id']);
                $this->change_name_wrapper($duser, $input['name']);
            } elseif ($event->get_arg(0) == "change_pass") {
                $input = validate_input([
                    'id' => 'user_id,exists',
                    'pass1' => 'password',
                    'pass2' => 'password',
                ]);
                $duser = User::by_id($input['id']);
                $this->change_password_wrapper($duser, $input['pass1'], $input['pass2']);
            } elseif ($event->get_arg(0) == "change_email") {
                $input = validate_input([
                    'id' => 'user_id,exists',
                    'address' => 'email',
                ]);
                $duser = User::by_id($input['id']);
                $this->change_email_wrapper($duser, $input['address']);
            } elseif ($event->get_arg(0) == "change_class") {
                $input = validate_input([
                    'id' => 'user_id,exists',
                    'class' => 'user_class',
                ]);
                $duser = User::by_id($input['id']);
                $this->change_class_wrapper($duser, $input['class']);
            } elseif ($event->get_arg(0) == "delete_user") {
                $this->delete_user($page, isset($_POST["with_images"]), isset($_POST["with_comments"]));
            }
        }

        if ($event->page_matches("user")) {
            $display_user = ($event->count_args() == 0) ? $user : User::by_name($event->get_arg(0));
            if ($event->count_args() == 0 && $user->is_anonymous()) {
                $this->theme->display_error(
                    401,
                    "Not Logged In",
                    "You aren't logged in. First do that, then you can see your stats."
                );
            } elseif (!is_null($display_user) && ($display_user->id != $config->get_int("anon_id"))) {
                $e = new UserPageBuildingEvent($display_user);
                send_event($e);
                $this->display_stats($e);
            } else {
                $this->theme->display_error(
                    404,
                    "No Such User",
                    "If you typed the ID by hand, try again; if you came from a link on this ".
                    "site, it might be bug report time..."
                );
            }
        }
    }

    public function onUserPageBuilding(UserPageBuildingEvent $event)
    {
        global $user, $config;

        $h_join_date = autodate($event->display_user->join_date);
        if ($event->display_user->can(Permissions::HELLBANNED)) {
            $h_class = $event->display_user->class->parent->name;
        } else {
            $h_class = $event->display_user->class->name;
        }

        $event->add_stats("Joined: $h_join_date", 10);
        $event->add_stats("Class: $h_class", 90);

        $av = $event->display_user->get_avatar_html();
        if ($av) {
            $event->add_stats($av, 0);
        } elseif ((
            $config->get_string("avatar_host") == "gravatar"
        ) &&
            ($user->id == $event->display_user->id)
        ) {
            $event->add_stats(
                "No avatar? This gallery uses <a href='https://gravatar.com'>Gravatar</a> for avatar hosting, use the".
                "<br>same email address here and there to have your avatar synced<br>",
                0
            );
        }
    }

    public function onPageNavBuilding(PageNavBuildingEvent $event)
    {
        global $user;
        if ($user->is_anonymous()) {
            $event->add_nav_link("user", new Link('user_admin/login'), "Account", null, 10);
        } else {
            $event->add_nav_link("user", new Link('user'), "Account", null, 10);
        }
    }


    private function display_stats(UserPageBuildingEvent $event)
    {
        global $user, $page, $config;

        ksort($event->stats);
        $this->theme->display_user_page($event->display_user, $event->stats);

        if (!$user->is_anonymous()) {
            if ($user->id == $event->display_user->id || $user->can("edit_user_info")) {
                $uobe = new UserOptionsBuildingEvent();
                send_event($uobe);

                $page->add_block(new Block("Options", $this->theme->build_options($event->display_user, $uobe), "main", 60));
            }
        }


        if ($user->id == $event->display_user->id) {
            $ubbe = new UserBlockBuildingEvent();
            send_event($ubbe);
            ksort($ubbe->parts);
            $this->theme->display_user_links($page, $user, $ubbe->parts);
        }
        if (
            ($user->can(Permissions::VIEW_IP) || ($user->is_logged_in() && $user->id == $event->display_user->id)) && # admin or self-user
            ($event->display_user->id != $config->get_int('anon_id')) # don't show anon's IP list, it is le huge
        ) {
            $this->theme->display_ip_list(
                $page,
                $this->count_upload_ips($event->display_user),
                $this->count_comment_ips($event->display_user),
                $this->count_log_ips($event->display_user)
            );
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        global $config;

        $hosts = [
            "None" => "none",
            "Gravatar" => "gravatar"
        ];

        $sb = new SetupBlock("User Options");
        $sb->add_bool_option("login_signup_enabled", "Allow new signups: ");
        $sb->add_longtext_option("login_tac", "<br>Terms &amp; Conditions:<br>");
        $sb->add_choice_option("avatar_host", $hosts, "<br>Avatars: ");

        if ($config->get_string("avatar_host") == "gravatar") {
            $sb->add_label("<br>&nbsp;<br><b>Gravatar Options</b>");
            $sb->add_choice_option(
                "avatar_gravatar_type",
                [
                    'Default'=>'default',
                    'Wavatar'=>'wavatar',
                    'Monster ID'=>'monsterid',
                    'Identicon'=>'identicon'
                ],
                "<br>Type: "
            );
            $sb->add_choice_option(
                "avatar_gravatar_rating",
                ['G'=>'g', 'PG'=>'pg', 'R'=>'r', 'X'=>'x'],
                "<br>Rating: "
            );
        }

        $sb->add_choice_option(
            "user_loginshowprofile",
            [
                            "return to previous page" => 0, // 0 is default
                            "send to user profile" => 1],
            "<br>When user logs in/out"
        );
        $event->panel->add_block($sb);
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event)
    {
        global $user;
        if ($event->parent==="system") {
            if ($user->can(Permissions::EDIT_USER_CLASS)) {
                $event->add_nav_link("user_admin", new Link('user_admin/list'), "User List", NavLink::is_active(["user_admin"]));
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event)
    {
        global $user;
        $event->add_link("My Profile", make_link("user"));
        if ($user->can(Permissions::EDIT_USER_CLASS)) {
            $event->add_link("User List", make_link("user_admin/list"), 98);
        }
        $event->add_link("Log Out", make_link("user_admin/logout"), 99);
    }

    public function onUserCreation(UserCreationEvent $event)
    {
        $this->check_user_creation($event);
        $this->create_user($event);
    }

    public function onSearchTermParse(SearchTermParseEvent $event)
    {
        global $user;

        $matches = [];
        if (preg_match("/^(?:poster|user)[=|:](.*)$/i", $event->term, $matches)) {
            $duser = User::by_name($matches[1]);
            if (!is_null($duser)) {
                $user_id = $duser->id;
            } else {
                $user_id = -1;
            }
            $event->add_querylet(new Querylet("images.owner_id = $user_id"));
        } elseif (preg_match("/^(?:poster|user)_id[=|:]([0-9]+)$/i", $event->term, $matches)) {
            $user_id = int_escape($matches[1]);
            $event->add_querylet(new Querylet("images.owner_id = $user_id"));
        } elseif ($user->can(Permissions::VIEW_IP) && preg_match("/^(?:poster|user)_ip[=|:]([0-9\.]+)$/i", $event->term, $matches)) {
            $user_ip = $matches[1]; // FIXME: ip_escape?
            $event->add_querylet(new Querylet("images.owner_ip = '$user_ip'"));
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event)
    {
        if ($event->key===HelpPages::SEARCH) {
            $block = new Block();
            $block->header = "Users";
            $block->body = $this->theme->get_help_html();
            $event->add_block($block);
        }
    }


    private function show_user_info()
    {
        global $user, $page;
        // user info is shown on all pages
        if ($user->is_anonymous()) {
            $this->theme->display_login_block($page);
        } else {
            $ubbe = new UserBlockBuildingEvent();
            send_event($ubbe);
            ksort($ubbe->parts);
            $this->theme->display_user_block($page, $user, $ubbe->parts);
        }
    }

    private function page_login($name, $pass)
    {
        global $config, $page;

        if (empty($name) || empty($pass)) {
            $this->theme->display_error(400, "Error", "Username or password left blank");
            return;
        }

        $duser = User::by_name_and_pass($name, $pass);
        if (!is_null($duser)) {
            send_event(new UserLoginEvent($duser));
            $this->set_login_cookie($duser->name, $pass);
            $page->set_mode(PageMode::REDIRECT);

            // Try returning to previous page
            if ($config->get_int("user_loginshowprofile", 0) == 0 &&
                            isset($_SERVER['HTTP_REFERER']) &&
                            strstr($_SERVER['HTTP_REFERER'], "post/")) {
                $page->set_redirect($_SERVER['HTTP_REFERER']);
            } else {
                $page->set_redirect(make_link("user"));
            }
        } else {
            $this->theme->display_error(401, "Error", "No user with those details was found");
        }
    }

    private function page_logout()
    {
        global $page, $config;
        $page->add_cookie("session", "", time() + 60 * 60 * 24 * $config->get_int('login_memory'), "/");
        if (CACHE_HTTP || SPEED_HAX) {
            # to keep as few versions of content as possible,
            # make cookies all-or-nothing
            $page->add_cookie("user", "", time() + 60 * 60 * 24 * $config->get_int('login_memory'), "/");
        }
        log_info("user", "Logged out");
        $page->set_mode(PageMode::REDIRECT);

        // Try forwarding to same page on logout unless user comes from registration page
        if ($config->get_int("user_loginshowprofile", 0) == 0 &&
            isset($_SERVER['HTTP_REFERER']) &&
            strstr($_SERVER['HTTP_REFERER'], "post/")
        ) {
            $page->set_redirect($_SERVER['HTTP_REFERER']);
        } else {
            $page->set_redirect(make_link());
        }
    }

    private function page_recover(string $username)
    {
        $my_user = User::by_name($username);
        if (is_null($my_user)) {
            $this->theme->display_error(404, "Error", "There's no user with that name");
        } elseif (is_null($my_user->email)) {
            $this->theme->display_error(400, "Error", "That user has no registered email address");
        } else {
            // send email
        }
    }

    private function page_create()
    {
        global $config, $page;
        if (!$config->get_bool("login_signup_enabled")) {
            $this->theme->display_signups_disabled($page);
        } elseif (!isset($_POST['name'])) {
            $this->theme->display_signup_page($page);
        } elseif ($_POST['pass1'] != $_POST['pass2']) {
            $this->theme->display_error(400, "Password Mismatch", "Passwords don't match");
        } else {
            try {
                if (!captcha_check()) {
                    throw new UserCreationException("Error in captcha");
                }

                $uce = new UserCreationEvent($_POST['name'], $_POST['pass1'], $_POST['email']);
                send_event($uce);
                $this->set_login_cookie($uce->username, $uce->password);
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link("user"));
            } catch (UserCreationException $ex) {
                $this->theme->display_error(400, "User Creation Error", $ex->getMessage());
            }
        }
    }

    private function check_user_creation(UserCreationEvent $event)
    {
        $name = $event->username;
        //$pass = $event->password;
        //$email = $event->email;

        if (strlen($name) < 1) {
            throw new UserCreationException("Username must be at least 1 character");
        } elseif (!preg_match('/^[a-zA-Z0-9-_]+$/', $name)) {
            throw new UserCreationException(
                "Username contains invalid characters. Allowed characters are ".
                    "letters, numbers, dash, and underscore"
            );
        } elseif (User::by_name($name)) {
            throw new UserCreationException("That username is already taken");
        }
    }

    private function create_user(UserCreationEvent $event)
    {
        global $database, $user;

        $email = (!empty($event->email)) ? $event->email : null;

        // if there are currently no admins, the new user should be one
        $need_admin = ($database->get_one("SELECT COUNT(*) FROM users WHERE class='admin'") == 0);
        $class = $need_admin ? 'admin' : 'user';

        $database->Execute(
            "INSERT INTO users (name, pass, joindate, email, class) VALUES (:username, :hash, now(), :email, :class)",
            ["username"=>$event->username, "hash"=>'', "email"=>$email, "class"=>$class]
        );
        $uid = $database->get_last_insert_id('users_id_seq');
        $user = User::by_name($event->username);
        $user->set_password($event->password);
        send_event(new UserLoginEvent($user));

        log_info("user", "Created User #$uid ({$event->username})");
    }

    private function set_login_cookie(string $name, string $pass)
    {
        global $config, $page;

        $addr = get_session_ip($config);
        $hash = User::by_name($name)->passhash;

        $page->add_cookie(
            "user",
            $name,
            time()+60*60*24*365,
            '/'
        );
        $page->add_cookie(
            "session",
            md5($hash.$addr),
            time()+60*60*24*$config->get_int('login_memory'),
            '/'
        );
    }

    private function user_can_edit_user(User $a, User $b): bool
    {
        if ($a->is_anonymous()) {
            $this->theme->display_error(401, "Error", "You aren't logged in");
            return false;
        }

        if (
            ($a->name == $b->name) ||
            ($b->can(Permissions::PROTECTED) && $a->class->name == "admin") ||
            (!$b->can(Permissions::PROTECTED) && $a->can(Permissions::EDIT_USER_INFO))
        ) {
            return true;
        } else {
            $this->theme->display_error(401, "Error", "You need to be an admin to change other people's details");
            return false;
        }
    }

    private function redirect_to_user(User $duser)
    {
        global $page, $user;

        if ($user->id == $duser->id) {
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("user"));
        } else {
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("user/{$duser->name}"));
        }
    }

    private function change_name_wrapper(User $duser, $name)
    {
        global $user;

        if ($user->can(Permissions::EDIT_USER_NAME) && $this->user_can_edit_user($user, $duser)) {
            $duser->set_name($name);
            flash_message("Username changed");
            // TODO: set login cookie if user changed themselves
            $this->redirect_to_user($duser);
        } else {
            $this->theme->display_error(400, "Error", "Permission denied");
        }
    }

    private function change_password_wrapper(User $duser, string $pass1, string $pass2)
    {
        global $user;

        if ($this->user_can_edit_user($user, $duser)) {
            if ($pass1 != $pass2) {
                $this->theme->display_error(400, "Error", "Passwords don't match");
            } else {
                // FIXME: send_event()
                $duser->set_password($pass1);

                if ($duser->id == $user->id) {
                    $this->set_login_cookie($duser->name, $pass1);
                }

                flash_message("Password changed");
                $this->redirect_to_user($duser);
            }
        }
    }

    private function change_email_wrapper(User $duser, string $address)
    {
        global $user;

        if ($this->user_can_edit_user($user, $duser)) {
            $duser->set_email($address);

            flash_message("Email changed");
            $this->redirect_to_user($duser);
        }
    }

    private function change_class_wrapper(User $duser, string $class)
    {
        global $user;

        if ($user->class->name == "admin") {
            $duser->set_class($class);
            flash_message("Class changed");
            $this->redirect_to_user($duser);
        }
    }

    private function count_upload_ips(User $duser): array
    {
        global $database;
        $rows = $database->get_pairs("
				SELECT
					owner_ip,
					COUNT(images.id) AS count
				FROM images
				WHERE owner_id=:id
				GROUP BY owner_ip
				ORDER BY max(posted) DESC", ["id"=>$duser->id]);
        return $rows;
    }

    private function count_comment_ips(User $duser): array
    {
        global $database;
        $rows = $database->get_pairs("
				SELECT
					owner_ip,
					COUNT(comments.id) AS count
				FROM comments
				WHERE owner_id=:id
				GROUP BY owner_ip
				ORDER BY max(posted) DESC", ["id"=>$duser->id]);
        return $rows;
    }

    private function count_log_ips(User $duser): array
    {
        if (!class_exists('LogDatabase')) {
            return [];
        }
        global $database;
        $rows = $database->get_pairs("
				SELECT
					address,
					COUNT(id) AS count
				FROM score_log
				WHERE username=:username
				GROUP BY address
				ORDER BY MAX(date_sent) DESC", ["username"=>$duser->name]);
        return $rows;
    }

    private function delete_user(Page $page, bool $with_images=false, bool $with_comments=false)
    {
        global $user, $config, $database;

        $page->set_title("Error");
        $page->set_heading("Error");
        $page->add_block(new NavBlock());

        if (!$user->can(Permissions::DELETE_USER)) {
            $page->add_block(new Block("Not Admin", "Only admins can delete accounts"));
        } elseif (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
            $page->add_block(new Block(
                "No ID Specified",
                "You need to specify the account number to edit"
            ));
        } else {
            log_warning("user", "Deleting user #{$_POST['id']}");

            if ($with_images) {
                log_warning("user", "Deleting user #{$_POST['id']}'s uploads");
                $rows = $database->get_all("SELECT * FROM images WHERE owner_id = :owner_id", ["owner_id" => $_POST['id']]);
                foreach ($rows as $key => $value) {
                    $image = Image::by_id($value['id']);
                    if ($image) {
                        send_event(new ImageDeletionEvent($image));
                    }
                }
            } else {
                $database->Execute(
                    "UPDATE images SET owner_id = :new_owner_id WHERE owner_id = :old_owner_id",
                    ["new_owner_id" => $config->get_int('anon_id'), "old_owner_id" => $_POST['id']]
                );
            }

            if ($with_comments) {
                log_warning("user", "Deleting user #{$_POST['id']}'s comments");
                $database->execute("DELETE FROM comments WHERE owner_id = :owner_id", ["owner_id" => $_POST['id']]);
            } else {
                $database->Execute(
                    "UPDATE comments SET owner_id = :new_owner_id WHERE owner_id = :old_owner_id",
                    ["new_owner_id" => $config->get_int('anon_id'), "old_owner_id" => $_POST['id']]
                );
            }

            send_event(new UserDeletionEvent($_POST['id']));

            $database->execute(
                "DELETE FROM users WHERE id = :id",
                ["id" => $_POST['id']]
            );

            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/list"));
        }
    }
}
