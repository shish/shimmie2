<?php

class BulkActionsTheme extends Themelet
{
    private function sort_blocks($a, $b)
    {
        return $a["position"] - $b["position"];
    }

    public function display_selector(Page $page, Event $event, $config, $query)
    {
		global $user;


		if($user->is_logged_in()) {
            $event = new BulkActionBlockBuildingEvent();
            send_event($event);

            if(sizeof($event->actions)==0)
                return;

			$body ="<input type='hidden' name='bulk_selected_ids' id='bulk_selected_ids' />
					<input id='bulk_selector_activate' type='button' onclick='activate_bulk_selector();' value='Activate Selector'/>
					<div id='bulk_selector_controls' style='display: none;'>
    					<input id='bulk_selector_deactivate' type='button' onclick='deactivate_bulk_selector();' value='Deactivate Selector'/>
						Click on images to mark them.
						<br />
						<table><tr><td>
						<input id='bulk_selector_select_all' type='button'
							onclick='select_all();' value='All'/>
						</td><td>
						<input id='bulk_selector_select_invert' type='button'
							onclick='select_invert();' value='Invert'/>
						</td><td>
						<input id='bulk)selector_select_none' type='button'
							onclick='select_none();' value='Clear'/>
						</td></tr></table>
			";

			$hasQuery = ($query!=null&&$query!="");

			if($hasQuery) {
				$body .= "</div>";
			}
            usort($event->actions, array($this, "sort_blocks"));

			foreach($event->actions as $action) {
				$body .= "<div class='bulk_action'>".make_form(make_link("bulk_action"), "POST", False, "", "return validate_selections(this,'".html_escape($action["confirmation_message"])."');").
				"<input type='hidden' name='bulk_query' value='".html_escape($query)."'>".
				"<input type='hidden' name='bulk_selected_ids' />".
				"<input type='hidden' name='bulk_action' value='".$action["action"]."' />".
					$action["block"].
				"<input type='submit' value='".$action["action"]."'/>".
				"</form></div>";
			}

			if(!$hasQuery) {
				$body .= "</div>";
			}
			$block = new Block("Bulk Actions", $body, "left", 30);
			$page->add_block($block);
		}
	}
	
	public function render_tag_input() {
		return "<label><input type='checkbox' style='width:13px;' name='bulk_tags_replace' value='true'/>Replace tags</label>".
		"<input type='text' name='bulk_tags' required='required' placeholder='Enter tags here' />";
	}

    public function render_source_input() {
        return "<input type='text' name='bulk_source' required='required' placeholder='Enter source here' />";
    }

}