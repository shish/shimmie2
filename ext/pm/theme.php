<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, B, INPUT, TABLE, TBODY, TD, TEXTAREA, TH, THEAD, TR};

class PrivMsgTheme extends Themelet
{
    /**
     * @param PM[] $pms
     */
    public function display_pms(array $pms): void
    {
        $tbody = TBODY();
        foreach ($pms as $pm) {
            $from = User::by_id_dangerously_cached($pm->from_id);

            $subject = trim($pm->subject) ?: "(No subject)";
            if (!$pm->is_read) {
                $subject = B($subject);
            }
            $tbody->appendChild(TR(
                TD($pm->is_read ? "Y" : "N"),
                TD(A(["href" => make_link("pm/read/".$pm->id)], $subject)),
                TD(A(["href" => make_link("user/".url_escape($from->name))], $from->name)),
                TD(substr($pm->sent_date, 0, 16)),
                TD(SHM_SIMPLE_FORM(
                    make_link("pm/delete"),
                    INPUT(["type" => "hidden", "name" => "pm_id", "value" => $pm->id]),
                    SHM_SUBMIT("Delete")
                ))
            ));
        }
        $html = TABLE(
            ["id" => "pms", "class" => "zebra"],
            THEAD(TR(TH("R?"), TH("Subject"), TH("From"), TH("Date"), TH("Action"))),
            $tbody
        );
        Ctx::$page->add_block(new Block("Private Messages", $html, "main", 40, "private-messages"));
    }

    public function display_composer(User $from, User $to, string $subject = ""): void
    {
        $html = SHM_SIMPLE_FORM(
            make_link("pm/send"),
            INPUT(["type" => "hidden", "name" => "to_id", "value" => $to->id]),
            TABLE(
                ["class" => "form"],
                TR(
                    TH("Subject"),
                    TD(INPUT(["type" => "text", "name" => "subject", "value" => $subject]))
                ),
                TR(TD(["colspan" => 2], TEXTAREA(["name" => "message", "rows" => 6]))),
                TR(TD(["colspan" => 2], SHM_SUBMIT("Send")))
            ),
        );
        Ctx::$page->add_block(new Block("Write a PM", $html, "main", 50));
    }

    public function display_message(User $from, User $to, PM $pm): void
    {
        $page = Ctx::$page;
        $page->set_title("Private Message");
        $page->set_heading($pm->subject);
        $page->add_block(new Block("Message from {$from->name}", format_text($pm->message), "main", 10));
    }
}
