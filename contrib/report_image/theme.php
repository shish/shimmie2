<?php

/*
 * Name: Report Images
 * Author: ATravelingGeek (atg@atravelinggeek.com
 * Link: http://atravelinggeek.com/
 * License: GPLv2
 * Description: Report images as dupes/illegal/etc
 * Version 0.2a - See changelog in main.php
 * October 24, 2007
 */
 
class ReportImageTheme extends Themelet {
	public function display_reported_images($page, $reportedimages) {
		$h_reportedimages = "";
		foreach($reportedimages as $reportedimage) {
			// If the reason is 'Duplicate' make the 'reason' field a link to the reported image
			if ($reportedimage['reason_type'] == "Duplicate")
			{
				$reason = "<a href=\"".make_link("post/view/{$reportedimage['reason']}")."\">".$reportedimage['reason']."</a>";
			} else {
				$reason = $reportedimage['reason'];
			}
			
			$image_link = "<a href=\"".make_link("post/view/{$reportedimage['image_id']}")."\">".$reportedimage['image_id']."</a>";
			$userlink = "<a href='".make_link("user/{$reportedimage['reporter_name']}")."'>{$reportedimage['reporter_name']}</a>";
			
				$h_reportedimages .= "
					<tr>
						<td>{$image_link}</td>
						<td>{$userlink}</td>
						<td>{$reportedimage['reason_type']}</td>
						<td>{$reason}</td>
						<td>
							<form action='".make_link("ReportImage/remove")."' method='POST'>
								<input type='hidden' name='id' value='{$reportedimage['id']}'>
								<input type='submit' value='Remove'>
							</form>
						</td>
					</tr>
				";
		}
		$html = "
			<table border='1'>
				<thead><td>Image</td><td>Reporter</td><td>Reason Type</td><td>Reason / Image ID</td><td>Action</td></thead>
				$h_reportedimages
			</table>
		";
		
		$page->add_block(new Block("Reported Images", $html));
	}
	
		public function display_page($page) {
		$page->set_title("Reported Images");
		$page->set_heading("Reported Images");
		$page->add_block(new NavBlock());
	}

	public function display_image_banner($page, $image) {
	
	global $config;

		$page->add_header("<script type='text/javascript' src='".get_base_href()."/ext/report_image/report_image.js'></script>");
	
		$i_image = int_escape($image);
		$html = "
			<form name='ReportImage' action='".make_link("ReportImage/add")."' onsubmit='return validate_report()' method='POST'>
				<input type='hidden' name='image_id' value='$i_image'>
				<select onchange='change_reason()' name='reason_type'>
				<option style='font-weight:bold' selected>Select a reason...</option>
				<option value='Other'>Other</option>
				<option value='Violates Rules'>Violates Rules</option>
				<option value='Illegal'>Illegal</option>
				<option value='Duplicate'>Duplicate</option>
				<input type='field' name='reason' value='Please enter a reason' onclick='document.ReportImage.reason.select()'>
				</select>
				<input type='submit' value='Report'>
			</form>
		";
		$page->add_block(new Block("Report Image", $html, "left"));

	}
}
?>