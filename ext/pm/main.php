<?php declare(strict_types=1);

class SendPMEvent extends Event
{
    public PM $pm;

    public function __construct(PM $pm)
    {
        parent::__construct();
        $this->pm = $pm;
    }
}

class PM
{
    public int $id;
    public int $from_id;
    public string $from_ip;
    public int $to_id;
    /** @var mixed */
    public $sent_date;
    public string $subject;
    public string $message;
    public bool $is_read;

    public function __construct($from_id=0, string $from_ip="0.0.0.0", int $to_id=0, string $subject="A Message", string $message="Some Text", bool $read=false)
    {
        # PHP: the P stands for "really", the H stands for "awful" and the other P stands for "language"
        if (is_array($from_id)) {
            $a = $from_id;
            $this->id      = (int)$a["id"];
            $this->from_id = (int)$a["from_id"];
            $this->from_ip = $a["from_ip"];
            $this->to_id   = (int)$a["to_id"];
            $this->sent_date = $a["sent_date"];
            $this->subject = $a["subject"];
            $this->message = $a["message"];
            $this->is_read = bool_escape($a["is_read"]);
        } else {
            $this->id      = -1;
            $this->from_id = $from_id;
            $this->from_ip = $from_ip;
            $this->to_id   = $to_id;
            $this->subject = $subject;
            $this->message = $message;
            $this->is_read = $read;
        }
    }
}

class PrivMsg extends Extension
{
    /** @var PrivMsgTheme */
    protected ?Themelet $theme;

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event)
    {
        global $database;

        // shortcut to latest
        if ($this->get_version("pm_version") < 1) {
            $database->create_table("private_message", "
				id SCORE_AIPK,
				from_id INTEGER NOT NULL,
				from_ip SCORE_INET NOT NULL,
				to_id INTEGER NOT NULL,
				sent_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				subject VARCHAR(64) NOT NULL,
				message TEXT NOT NULL,
				is_read BOOLEAN NOT NULL DEFAULT FALSE,
				FOREIGN KEY (from_id) REFERENCES users(id) ON DELETE CASCADE,
				FOREIGN KEY (to_id) REFERENCES users(id) ON DELETE CASCADE
			");
            $database->execute("CREATE INDEX private_message__to_id ON private_message(to_id)");
            $this->set_version("pm_version", 3);
        }

        if ($this->get_version("pm_version") < 2) {
            log_info("pm", "Adding foreign keys to private messages");
            $database->execute("delete from private_message where to_id not in (select id from users);");
            $database->execute("delete from private_message where from_id not in (select id from users);");
            $database->execute("ALTER TABLE private_message
			ADD FOREIGN KEY (from_id) REFERENCES users(id) ON DELETE CASCADE,
			ADD FOREIGN KEY (to_id) REFERENCES users(id) ON DELETE CASCADE;");
            $this->set_version("pm_version", 2);
        }

        if ($this->get_version("pm_version") < 3) {
            $database->standardise_boolean("private_message", "is_read", true);
            $this->set_version("pm_version", 3);
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event)
    {
        global $user;
        if ($event->parent==="user") {
            if ($user->can(Permissions::READ_PM)) {
                $count = $this->count_pms($user);
                $h_count = $count > 0 ? " <span class='unread'>($count)</span>" : "";
                $event->add_nav_link("pm", new Link('user#private-messages'), "Private Messages$h_count");
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event)
    {
        global $user;
        if ($user->can(Permissions::READ_PM)) {
            $count = $this->count_pms($user);
            $h_count = $count > 0 ? " <span class='unread'>($count)</span>" : "";
            $event->add_link("Private Messages$h_count", make_link("user", null, "private-messages"));
        }
    }

    public function onUserPageBuilding(UserPageBuildingEvent $event)
    {
        global $page, $user;
        $duser = $event->display_user;
        if (!$user->is_anonymous() && !$duser->is_anonymous()) {
            if (($user->id == $duser->id) || $user->can(Permissions::VIEW_OTHER_PMS)) {
                $this->theme->display_pms($page, $this->get_pms($duser));
            }
            if ($user->id != $duser->id) {
                $this->theme->display_composer($page, $user, $duser);
            }
        }
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $cache, $database, $page, $user;
        if ($event->page_matches("pm")) {
            switch ($event->get_arg(0)) {
                case "read":
                    if ($user->can(Permissions::READ_PM)) {
                        $pm_id = int_escape($event->get_arg(1));
                        $pm = $database->get_row("SELECT * FROM private_message WHERE id = :id", ["id" => $pm_id]);
                        if (is_null($pm)) {
                            $this->theme->display_error(404, "No such PM", "There is no PM #$pm_id");
                        } elseif (($pm["to_id"] == $user->id) || $user->can(Permissions::VIEW_OTHER_PMS)) {
                            $from_user = User::by_id((int)$pm["from_id"]);
                            if ($pm["to_id"] == $user->id) {
                                $database->execute("UPDATE private_message SET is_read=true WHERE id = :id", ["id" => $pm_id]);
                                $cache->delete("pm-count-{$user->id}");
                            }
                            $this->theme->display_message($page, $from_user, $user, new PM($pm));
                        } else {
                            $this->theme->display_permission_denied();
                        }
                    }
                    break;
                case "delete":
                    if ($user->can(Permissions::READ_PM)) {
                        if ($user->check_auth_token()) {
                            $pm_id = int_escape($_POST["pm_id"]);
                            $pm = $database->get_row("SELECT * FROM private_message WHERE id = :id", ["id" => $pm_id]);
                            if (is_null($pm)) {
                                $this->theme->display_error(404, "No such PM", "There is no PM #$pm_id");
                            } elseif (($pm["to_id"] == $user->id) || $user->can(Permissions::VIEW_OTHER_PMS)) {
                                $database->execute("DELETE FROM private_message WHERE id = :id", ["id" => $pm_id]);
                                $cache->delete("pm-count-{$user->id}");
                                log_info("pm", "Deleted PM #$pm_id", "PM deleted");
                                $page->set_mode(PageMode::REDIRECT);
                                $page->set_redirect(referer_or(make_link()));
                            }
                        }
                    }
                    break;
                case "send":
                    if ($user->can(Permissions::SEND_PM)) {
                        if ($user->check_auth_token()) {
                            $to_id = int_escape($_POST["to_id"]);
                            $from_id = $user->id;
                            $subject = $_POST["subject"];
                            $message = $_POST["message"];
                            send_event(new SendPMEvent(new PM($from_id, $_SERVER["REMOTE_ADDR"], $to_id, $subject, $message)));
                            $page->flash("PM sent");
                            $page->set_mode(PageMode::REDIRECT);
                            $page->set_redirect(referer_or(make_link()));
                        }
                    }
                    break;
                default:
                    $this->theme->display_error(400, "Invalid action", "That's not something you can do with a PM");
                    break;
            }
        }
    }

    public function onSendPM(SendPMEvent $event)
    {
        global $cache, $database;
        $database->execute(
            "
				INSERT INTO private_message(
					from_id, from_ip, to_id,
					sent_date, subject, message)
				VALUES(:fromid, :fromip, :toid, now(), :subject, :message)",
            ["fromid" => $event->pm->from_id, "fromip" => $event->pm->from_ip,
            "toid" => $event->pm->to_id, "subject" => $event->pm->subject, "message" => $event->pm->message]
        );
        $cache->delete("pm-count-{$event->pm->to_id}");
        log_info("pm", "Sent PM to User #{$event->pm->to_id}");
    }


    private function get_pms(User $user): array
    {
        global $database;

        $arr = $database->get_all(
            "
				SELECT private_message.*,user_from.name AS from_name
				FROM private_message
				JOIN users AS user_from ON user_from.id=from_id
				WHERE to_id = :toid
				ORDER BY sent_date DESC",
            ["toid" => $user->id]
        );
        $pms = [];
        foreach ($arr as $pm) {
            $pms[] = new PM($pm);
        }
        return $pms;
    }

    private function count_pms(User $user)
    {
        global $cache, $database;

        $count = $cache->get("pm-count:{$user->id}");
        if (is_null($count) || $count === false) {
            $count = $database->get_one("
					SELECT count(*)
					FROM private_message
					WHERE to_id = :to_id
					AND is_read = :is_read
			", ["to_id" => $user->id, "is_read" => false]);
            $cache->set("pm-count:{$user->id}", $count, 600);
        }
        return $count;
    }
}
