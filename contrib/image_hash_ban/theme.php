<?php

/**
 * Name: Image Hash Ban
 * Author: ATravelingGeek (atg@atravelinggeek.com
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
	public function display_image_hash_bans($page, $bans) {
		$h_bans = "";
		foreach($bans as $ban) {
			$h_bans .= "
				<tr>
					<td>{$ban['hash']}</td>
					<td>{$ban['reason']}</td>
					<td>
						<form action='".make_link("image_hash_ban/remove")."' method='POST'>
							<input type='hidden' name='hash' value='{$ban['hash']}'>
							<input type='submit' value='Remove'>
						</form>
					</td>
				</tr>
			";
		}
		$html = "
			<table border='1'>
				<thead><td>Hash</td><td>Reason</td><td>Action</td></thead>
				$h_bans
				<tr>
					<form action='".make_link("image_hash_ban/add")."' method='POST'>
						<td><input type='text' name='hash'></td>
						<td><input type='text' name='reason'></td>
						<td><input type='submit' value='Ban'></td>
					</form>
				</tr>
			</table>
		";
		$page->add_block(new Block("Edit Image Bans", $html));
	}
	
		public function display_page($page) {
		$page->set_title("Image Ban");
		$page->set_heading("Image Ban");
		$page->add_block(new NavBlock());
	}

	/*
	 * Display a link to delete an image
	 *
	 * $image_id = the image to delete
	 */
	public function display_image_banner($page, $image_hash) {

/* I'll fix this soon 
		$i_image_hash = int_escape($image_hash);
		$html = "
			<form action='".make_link("admin/image_hash_ban")."' method='POST'>
				<input type='hidden' name='image_hash' value='$i_image_hash'>
				<input type='field' name='reason'>
				<input type='submit' value='Ban and Delete'>
			</form>
		";
		$page->add_block(new Block("Image Ban", $html, "left"));
*/
	}
}
?>