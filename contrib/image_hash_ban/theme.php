<?php
/**
 * Name: Image Hash Ban
 * Author: ATravelingGeek <atg@atravelinggeek.com>
 * Link: http://atravelinggeek.com/
 * License: GPLv2
 * Description: Ban images based on their hash
 * Based on the ResolutionLimit and IPban extensions by Shish
 * Version 0.1
 * October 21, 2007
 */

class ImageBanTheme extends Themelet {
	/*
	 * Show all the bans
	 *
	 * $bans = an array of (
	 *  'hash' => the banned hash
	 *  'reason' => why the hash was banned
	 *  'date' => when the ban started
	 * )
	 */
	public function display_image_hash_bans(Page $page, $page_number, $page_count, $bans) {
		$h_bans = "";
		$n = 0;
		foreach($bans as $ban) {
			$oe = ($n++ % 2 == 0) ? "even" : "odd";
			$h_bans .= "
				<tr class='$oe'>
					<td width='30%'>{$ban['hash']}</td>
					<td>{$ban['reason']}</td>
					<td width='10%'>
						<form action='".make_link("image_hash_ban/remove")."' method='POST'>
							<input type='hidden' name='hash' value='{$ban['hash']}'>
							<input type='submit' value='Remove'>
						</form>
					</td>
				</tr>
			";
		}
		$html = "
			<script>
			$(document).ready(function() {
				$(\"#image_bans\").tablesorter();
			});
			</script>
			<table id='image_bans' class='zebra'>
				<thead><th>Hash</th><th>Reason</th><th>Action</th></thead>
				$h_bans
				<tfoot><tr>
					<form action='".make_link("image_hash_ban/add")."' method='POST'>
						<td><input type='text' name='hash'></td>
						<td><input type='text' name='reason'></td>
						<td><input type='submit' value='Ban'></td>
					</form>
				</tr></tfoot>
			</table>
		";

		$prev = $page_number - 1;
		$next = $page_number + 1;

		$h_prev = ($page_number <= 1) ? "Prev" : "<a href='".make_link("image_hash_ban/list/$prev")."'>Prev</a>";
		$h_index = "<a href='".make_link()."'>Index</a>";
		$h_next = ($page_number >= $page_count) ? "Next" : "<a href='".make_link("image_hash_ban/list/$next")."'>Next</a>";

		$nav = "$h_prev | $h_index | $h_next";

		$page->set_title("Image Bans");
		$page->set_heading("Image Bans");
		$page->add_block(new Block("Edit Image Bans", $html));
		$page->add_block(new Block("Navigation", $nav, "left", 0));
		$this->display_paginator($page, "image_hash_ban/list", null, $page_number, $page_count);
	}

	/*
	 * Display a link to delete an image
	 *
	 * $image_id = the image to delete
	 */
	public function get_buttons_html(Image $image) {
		$html = "
			<form action='".make_link("image_hash_ban/add")."' method='POST'>
				<input type='hidden' name='hash' value='{$image->hash}'>
				<input type='hidden' name='image_id' value='{$image->id}'>
				<input type='text' name='reason'>
				<input type='submit' value='Ban and Delete'>
			</form>
		";
		return $html;
	}
}
?>
