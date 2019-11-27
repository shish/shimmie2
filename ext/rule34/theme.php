<?php

class Rule34Theme extends Themelet
{
    public function show_comic_changer(User $duser, bool $current_state): void
    {
        global $page;
        $checked = $current_state ? 'checked="checked"' : '';
        $html = make_form(make_link("rule34/comic_admin"), "POST");
        $html .= "<input type='hidden' name='user_id' value='{$duser->id}'>";
        $html .= "<label><input type='checkbox' name='is_admin' $checked> Comic Admin</label>";
        $html .= "<br/><input type='submit' id='Set'>";
        $html .= "</form>\n";
        
        $page->add_block(new Block("Rule34 Comic Options", $html));
    }
}
