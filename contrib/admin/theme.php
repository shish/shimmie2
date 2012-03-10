<?php

class AdminPageTheme extends Themelet {
	/*
	 * Show the basics of a page, for other extensions to add to
	 */
	public function display_page(Page $page) {
		$page->set_title("Admin Tools");
		$page->set_heading("Admin Tools");
		$page->add_block(new NavBlock());
	}

	protected function button(/*string*/ $name, /*string*/ $action, /*boolean*/ $protected=false) {
		$c_protected = $protected ? " protected" : "";
		$html = make_form(make_link("admin_utils"), "POST", false, false, false, "admin$c_protected");
		if($protected) {
			$html .= "<input type='checkbox' onclick='$(\"#$action\").attr(\"disabled\", !$(this).is(\":checked\"))'>";
		}
		$html .= "<input type='hidden' value='$action'>";
		if($protected) {
			$html .= "<input type='submit' id='$action' value='$name' disabled='true'>";
		}
		else {
			$html .= "<input type='submit' id='$action' value='$name'>";
		}
		$html .= "</form>\n";
		return $html;
	}

	/*
	 * Show a form which links to admin_utils with POST[action] set to one of:
	 *  'lowercase all tags'
	 *  'recount tag use'
	 *  'purge unused tags'
	 */
	public function display_form(Page $page) {
		global $user;

		$html = "";
		$html .= $this->button("All tags to lowercase", "lowercase_all_tags", true);
		$html .= $this->button("Recount tag use", "recount_tag_user", false);
		$html .= $this->button("Purge unused tags", "purge_unused_tags", true);
		$html .= $this->button("Download database contents", "database_dump", false);
		$html .= $this->button("Reset image IDs", "reset_image_ids", true);
		$html .= $this->button("Download all images", "image_dump", false);
		$page->add_block(new Block("Misc Admin Tools", $html));
		
		/* First check
		Requires you to click the checkbox to enable the delete by query form */
		$dbqcheck = 'javascript:$(function() {
			if($("#dbqcheck:checked").length != 0){
				$("#dbqtags").attr("disabled", false);
				$("#dbqsubmit").attr("disabled", false);
			}else{
				$("#dbqtags").attr("disabled", true);
				$("#dbqsubmit").attr("disabled", true);
			}
		});';
				
		/* Second check
		Requires you to confirm the deletion by clicking ok. */
		$html = "
			<script type='text/javascript'>
			function checkform(){
				return confirm('Are you sure you wish to delete all images using these tags?');
			}		
			</script>"
			
		.make_form(make_link("admin_utils"),"post",false,false,"return checkform()")."
				<input type='checkbox' id='dbqcheck' name='action' onclick='$dbqcheck'>
				<input type='hidden' name='action' value='delete by query'>
				<input type='text' id='dbqtags' disabled='true' name='query'>
				<input type='submit' id='dbqsubmit' disabled='true' value='Go'>
			</form>
		";
		$page->add_block(new Block("Delete by Query", $html));
	}
}
?>
