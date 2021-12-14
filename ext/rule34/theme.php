<?php

declare(strict_types=1);
use function MicroHTML\INPUT;
use function MicroHTML\LABEL;
use function MicroHTML\BR;

class Rule34Theme extends Themelet
{
    public function show_comic_changer(User $duser, bool $current_state): void
    {
        global $page;
        $html = (string)SHM_SIMPLE_FORM(
            "rule34/comic_admin",
            INPUT(["type"=>'hidden', "name"=>'user_id', "value"=>$duser->id]),
            LABEL(INPUT(["type"=>'checkbox', "name"=>'is_admin', "checked"=>$current_state]), "Comic Admin"),
            BR(),
            SHM_SUBMIT("Set")
        );

        $page->add_block(new Block("Rule34 Comic Options", $html));
    }
}
