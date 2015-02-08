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
	/**
	 * @param Page $page
	 * @param array $reports
	 */
	public function display_reported_images(Page $page, $reports) {
		global $config;

		$h_reportedimages = "";
		foreach($reports as $report) {
			$image = $report['image'];
			$h_reason = format_text($report['reason']);
			$image_link = $this->build_thumb_html($image);

			$reporter_name = html_escape($report['reporter_name']);
			$userlink = "<a href='".make_link("user/$reporter_name")."'>$reporter_name</a>";

			global $user;
			$iabbe = new ImageAdminBlockBuildingEvent($image, $user);
			send_event($iabbe);
			ksort($iabbe->parts);
			$actions = join("<br>", $iabbe->parts);

			$h_reportedimages .= "
				<tr>
					<td>{$image_link}</td>
					<td>Report by $userlink: $h_reason</td>
					<td class='formstretch'>
						".make_form(make_link("image_report/remove"))."
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

	/**
	 * @param Image $image
	 * @param array $reporters
	 */
	public function display_image_banner(Image $image, /*array*/ $reporters) {
		global $page;

		$i_image = int_escape($image->id);
		$html = "";
		if(count($reporters) > 0) {
			$html .= "<b>Image reported by ".html_escape(implode(", ", $reporters))."</b><p>";
		}
		$html .= "
			".make_form(make_link("image_report/add"))."
				<input type='hidden' name='image_id' value='$i_image'>
				<input type='text' name='reason' placeholder='Please enter a reason'>
				<input type='submit' value='Report'>
			</form>
		";
		$page->add_block(new Block("Report Image", $html, "left"));
	}

	public function get_nuller(User $duser) {
		global $user, $page;
		$html = "
			<form action='".make_link("image_report/remove_reports_by")."' method='POST'>
			".$user->get_auth_html()."
			<input type='hidden' name='user_id' value='{$duser->id}'>
			<input type='submit' value='Delete all reports by this user'>
			</form>
		";
		$page->add_block(new Block("Reports", $html, "main", 80));
	}
}

