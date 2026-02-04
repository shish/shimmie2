<?php

declare(strict_types=1);

namespace Shimmie2;

use GQLA\{Field, Mutation, Type};

use function MicroHTML\{SPAN, emptyHTML};

final class SendPMEvent extends Event
{
    public function __construct(
        public PM $pm
    ) {
        parent::__construct();
    }
}

#[Type(name: "PrivateMessage")]
final class PM
{
    public int $id = -1;
    public mixed $sent_date;

    public function __construct(
        public int $from_id,
        public IPAddress $from_ip,
        public int $to_id,
        #[Field]
        public string $subject,
        #[Field]
        public string $message,
        #[Field]
        public bool $is_read = false
    ) {
    }

    #[Field]
    public function from(): User
    {
        return User::by_id($this->from_id);
    }

    #[Field(name: "pm_id")]
    public function graphql_oid(): int
    {
        return $this->id;
    }
    #[Field(name: "id")]
    public function graphql_guid(): string
    {
        return "pm:{$this->id}";
    }

    /**
     * @param array{
     *     id: string|int,
     *     from_id: string|int,
     *     from_ip: string,
     *     to_id: string|int,
     *     subject: string,
     *     message: string,
     *     is_read: string|bool,
     *     sent_date: string
     * } $row
     */
    public static function from_row(array $row): PM
    {
        $pm = new PM(
            (int)$row["from_id"],
            IPAddress::parse($row["from_ip"]),
            (int)$row["to_id"],
            $row["subject"],
            $row["message"],
            bool_escape($row["is_read"]),
        );
        $pm->id = (int)$row["id"];
        $pm->sent_date = $row["sent_date"];
        return $pm;
    }

    /**
     * @return PM[]|null
     */
    #[Field(extends: "User", name: "private_messages", type: "[PrivateMessage!]")]
    public static function get_pms(User $duser): ?array
    {
        global $database;

        if (!Ctx::$user->can(PrivMsgPermission::READ_PM)) {
            return null;
        }
        if (($duser->id !== Ctx::$user->id) && !Ctx::$user->can(PrivMsgPermission::VIEW_OTHER_PMS)) {
            return null;
        }

        $pms = [];
        $arr = $database->get_all(
            "SELECT * FROM private_message WHERE to_id = :to_id ORDER BY sent_date DESC",
            ["to_id" => $duser->id]
        );
        foreach ($arr as $pm) {
            $pms[] = PM::from_row($pm);
        }
        return $pms;
    }

    #[Field(extends: "User", name: "private_message_unread_count")]
    public static function count_unread_pms(User $duser): ?int
    {
        global $database;

        if (!Ctx::$user->can(PrivMsgPermission::READ_PM)) {
            return null;
        }
        if (($duser->id !== Ctx::$user->id) && !Ctx::$user->can(PrivMsgPermission::VIEW_OTHER_PMS)) {
            return null;
        }

        return (int)$database->get_one(
            "SELECT COUNT(*) FROM private_message WHERE to_id = :to_id AND is_read = :is_read",
            ["is_read" => false, "to_id" => $duser->id]
        );
    }

    #[Mutation(name: "create_private_message")]
    public static function send_pm(int $to_user_id, string $subject, string $message): bool
    {
        if (!Ctx::$user->can(PrivMsgPermission::SEND_PM)) {
            return false;
        }
        send_event(new SendPMEvent(new PM(Ctx::$user->id, Network::get_real_ip(), $to_user_id, $subject, $message)));
        return true;
    }
}

/** @extends Extension<PrivMsgTheme> */
final class PrivMsg extends Extension
{
    public const KEY = "pm";
    public const VERSION_KEY = "pm_version";

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        // shortcut to latest
        if ($this->get_version() < 1) {
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
            $this->set_version(3);
        }

        if ($this->get_version() < 2) {
            Log::info("pm", "Adding foreign keys to private messages");
            $database->execute("delete from private_message where to_id not in (select id from users);");
            $database->execute("delete from private_message where from_id not in (select id from users);");
            $database->execute("ALTER TABLE private_message
			ADD FOREIGN KEY (from_id) REFERENCES users(id) ON DELETE CASCADE,
			ADD FOREIGN KEY (to_id) REFERENCES users(id) ON DELETE CASCADE;");
            $this->set_version(2);
        }

        if ($this->get_version() < 3) {
            $database->standardise_boolean("private_message", "is_read", true);
            $this->set_version(3);
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "user") {
            if (Ctx::$user->can(PrivMsgPermission::READ_PM)) {
                $count = $this->count_pms(Ctx::$user);
                $h_count = $count > 0 ? SPAN(["class" => 'unread'], "($count)") : "";
                $event->add_nav_link(make_link('user', fragment: 'private-messages'), emptyHTML("Private Messages", $h_count), "private_messages", order: 10);
            }
        }
    }

    public function onUserPageBuilding(UserPageBuildingEvent $event): void
    {
        $duser = $event->display_user;

        $pms = PM::get_pms($duser);
        if (!is_null($pms)) {
            $this->theme->display_pms($pms);
        }

        if (
            Ctx::$user->can(PrivMsgPermission::SEND_PM)
            && $duser->can(PrivMsgPermission::READ_PM)
            && Ctx::$user->id !== $duser->id
        ) {
            $this->theme->display_composer(Ctx::$user, $duser);
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $database;
        $user = Ctx::$user;
        $page = Ctx::$page;
        if ($event->page_matches("pm/read/{pm_id}", permission: PrivMsgPermission::READ_PM)) {
            $pm_id = $event->get_iarg('pm_id');
            $pm = $database->get_row("SELECT * FROM private_message WHERE id = :id", ["id" => $pm_id]);
            if (is_null($pm)) {
                throw new ObjectNotFound("No such PM");
            } elseif (($pm["to_id"] === Ctx::$user->id) || Ctx::$user->can(PrivMsgPermission::VIEW_OTHER_PMS)) {
                $from_user = User::by_id((int)$pm["from_id"]);
                if ($pm["to_id"] === Ctx::$user->id) {
                    $database->execute("UPDATE private_message SET is_read=true WHERE id = :id", ["id" => $pm_id]);
                    Ctx::$cache->delete("pm-count-".Ctx::$user->id);
                }
                $pmo = PM::from_row($pm);
                $this->theme->display_message($from_user, Ctx::$user, $pmo);
                if ($user->can(PrivMsgPermission::SEND_PM)) {
                    $this->theme->display_composer(Ctx::$user, $from_user, "Re: ".$pmo->subject);
                }
            } else {
                throw new PermissionDenied("You do not have permission to view this PM");
            }
        }
        if ($event->page_matches("pm/delete", method: "POST", permission: PrivMsgPermission::READ_PM)) {
            $pm_id = int_escape($event->POST->req("pm_id"));
            $pm = $database->get_row("SELECT * FROM private_message WHERE id = :id", ["id" => $pm_id]);
            if (is_null($pm)) {
                throw new ObjectNotFound("No such PM");
            } elseif (($pm["to_id"] === $user->id) || $user->can(PrivMsgPermission::VIEW_OTHER_PMS)) {
                $database->execute("DELETE FROM private_message WHERE id = :id", ["id" => $pm_id]);
                Ctx::$cache->delete("pm-count-{$user->id}");
                Log::info("pm", "Deleted PM #$pm_id", "PM deleted");
                $page->set_redirect(Url::referer_or());
            }
        }
        if ($event->page_matches("pm/send", method: "POST", permission: PrivMsgPermission::SEND_PM)) {
            $to_id = int_escape($event->POST->req("to_id"));
            $from_id = $user->id;
            $subject = $event->POST->req("subject");
            $message = $event->POST->req("message");
            send_event(new SendPMEvent(new PM($from_id, Network::get_real_ip(), $to_id, $subject, $message)));
            $page->flash("PM sent");
            $page->set_redirect(Url::referer_or());
        }
    }

    public function onSendPM(SendPMEvent $event): void
    {
        Ctx::$database->execute(
            "INSERT INTO private_message(from_id, from_ip, to_id, sent_date, subject, message)
			VALUES(:fromid, :fromip, :toid, now(), :subject, :message)",
            ["fromid" => $event->pm->from_id, "fromip" => (string)$event->pm->from_ip,
            "toid" => $event->pm->to_id, "subject" => $event->pm->subject, "message" => $event->pm->message]
        );
        Ctx::$cache->delete("pm-count-{$event->pm->to_id}");
        Log::info("pm", "Sent PM to User #{$event->pm->to_id}");
    }

    private function count_pms(User $user): int
    {
        return cache_get_or_set(
            "pm-count-{$user->id}",
            fn () => (int)Ctx::$database->get_one("
                SELECT count(*)
                FROM private_message
                WHERE to_id = :to_id
                AND is_read = :is_read
            ", ["to_id" => $user->id, "is_read" => false]),
            600
        );
    }
}
