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

	/*
	 * Show a form which links to admin_utils with POST[action] set to one of:
	 *  'lowercase all tags'
	 *  'recount tag use'
	 *  'purge unused tags'
	 */
	public function display_form(Page $page) {
		global $user;

		$html = "
			".make_form(make_link("admin_utils"))."
				<select name='action'>
					<option value='lowercase all tags'>All tags to lowercase</option>
					<option value='recount tag use'>Recount tag use</option>
					<option value='purge unused tags'>Purge unused tags</option>
					<option value='database dump'>Download database contents</option>
					<!--<option value='convert to innodb'>Convert database to InnoDB (MySQL only)</option>-->
				</select>
				<input type='submit' value='Go'>
			</form>
		";
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
				if(confirm('Are you sure you wish to delete all images using these tags?')){
					return true;
				}else{
					return false;
				}
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
