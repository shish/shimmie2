<?php

/*
 * Name: Report Images
 * Author: ATravelingGeek (atg@atravelinggeek.com
 * Link: http://atravelinggeek.com/
 * License: GPLv2
 * Description: Report images as dupes/illegal/etc
 * Version 0.3a - See changelog in main.php
 * November 06, 2007
 */

class ReportImageTheme extends Themelet {
	public function display_reported_images(Page $page, $reports) {
		global $config;

		$h_reportedimages = "";
		$n = 0;
		foreach($reports as $report) {
			$image = $report['image'];
			$h_reason = format_text($report['reason']);

			if($config->get_bool('report_image_show_thumbs')) {
				$image_link = $this->build_thumb_html($image);
			}
			else {
				$image_link = "<a href=\"".make_link("post/view/{$image->id}")."\">{$image->id}</a>";
			}

			$reporter_name = html_escape($report['reporter_name']);
			$userlink = "<a href='".make_link("user/$reporter_name")."'>$reporter_name</a>";

			global $user;
			$iabbe = new ImageAdminBlockBuildingEvent($image, $user);
			send_event($iabbe);
			ksort($iabbe->parts);
			$actions = join("<br>", $iabbe->parts);

			$oe = ($n++ % 2 == 0) ? "even" : "odd";
			$h_reportedimages .= "
				<tr class='$oe'>
					<td>{$image_link}</td>
					<td>Report by $userlink: $h_reason</td>
					<td class='formstretch'>
						<form action='".make_link("image_report/remove")."' method='POST'>
							<input type='hidden' name='id' value='{$report['id']}'>
							<input type='submit' value='Remove Report'>
						</form>

						<br>$actions
					</td>
				</tr>
			";
		}

		$thumb_width = $config->get_int("thumb_width");
		$html = "
			<table id='reportedimage' class='zebra'>
				<thead><td width='$thumb_width'>Image</td><td>Reason</td><td width='128'>Action</td></thead>
				$h_reportedimages
			</table>
		";

		$page->set_title("Reported Images");
		$page->set_heading("Reported Images");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Reported Images", $html));

	}

	public function display_image_banner(Page $page, Image $image) {
		global $config;

		$i_image = int_escape($image->id);
		$html = "
			<form action='".make_link("image_report/add")."' method='POST'>
				<input type='hidden' name='image_id' value='$i_image'>
				<input type='text' name='reason' value='Please enter a reason' onclick='this.value=\"\";'>
				<input type='submit' value='Report'>
			</form>
		";
		$page->add_block(new Block("Report Image", $html, "left"));
	}
}
?>
