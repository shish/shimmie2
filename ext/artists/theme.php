<?php
class ArtistsTheme extends Themelet {

	/**
	 * @param string $author
	 * @return string
	 */
	public function get_author_editor_html(/*string*/ $author) {
		$h_author = html_escape($author);
		return "
			<tr>
				<th>Author</th>
				<td>
					<span class='view'>$h_author</span>
					<input class='edit' type='text' name='tag_edit__author' value='$h_author'>
				</td>
			</tr>
		";
	}

	/**
	 * @param string $mode
	 * @param null|int $artistID
	 * @param bool $is_admin
	 */
	public function sidebar_options(/*string*/ $mode, $artistID=NULL, $is_admin=FALSE) {
		global $page, $user;

		$html = "";

		if($mode == "neutral"){
			$html = "<form method='post' action='".make_link("artist/new_artist")."'>
						".$user->get_auth_html()."
						<input type='submit' name='edit' id='edit' value='New Artist'/>
					</form>";
		}
		
		if($mode == "editor"){
			$html = "<form method='post' action='".make_link("artist/new_artist")."'>
						".$user->get_auth_html()."
						<input type='submit' name='edit' id='edit' value='New Artist'/>
					</form>
					
					<form method='post' action='".make_link("artist/edit_artist")."'>
						".$user->get_auth_html()."
						<input type='submit' name='edit' id='edit' value='Edit Artist'/>
						<input type='hidden' name='artist_id' value='".$artistID."'>
					</form>";
					
			if($is_admin){
				$html .= "<form method='post' action='".make_link("artist/nuke_artist")."'>
							".$user->get_auth_html()."
							<input type='submit' name='edit' id='edit' value='Delete Artist'/>
							<input type='hidden' name='artist_id' value='".$artistID."'>
						</form>";
			}
			
			$html .= "<form method='post' action='".make_link("artist/add_alias")."'>
							".$user->get_auth_html()."
							<input type='submit' name='edit' id='edit' value='Add Alias'/>
							<input type='hidden' name='artist_id' value='".$artistID."'>
						</form>
						
						<form method='post' action='".make_link("artist/add_member")."'>
							".$user->get_auth_html()."
							<input type='submit' name='edit' id='edit' value='Add Member'/>
							<input type='hidden' name='artist_id' value='".$artistID."'>
						</form>
						
						<form method='post' action='".make_link("artist/add_url")."'>
							".$user->get_auth_html()."
							<input type='submit' name='edit' id='edit' value='Add Url'/>
							<input type='hidden' name='artist_id' value='".$artistID."'>
						</form>";
		}

		if($html) $page->add_block(new Block("Manage Artists", $html, "left", 10));
	}

	public function show_artist_editor($artist, $aliases, $members, $urls) {
		global $user;

		$artistName = $artist['name'];
		$artistNotes = $artist['notes'];
		$artistID = $artist['id'];

		// aliases
		$aliasesString = "";
		$aliasesIDsString = "";
		foreach ($aliases as $alias) {
			$aliasesString .= $alias["alias_name"]." ";
			$aliasesIDsString .= $alias["alias_id"]." ";
		}
		$aliasesString = rtrim($aliasesString);
		$aliasesIDsString = rtrim($aliasesIDsString);

		// members
		$membersString = "";
		$membersIDsString = "";
		foreach ($members as $member) {
			$membersString .= $member["name"]." ";
			$membersIDsString .= $member["id"]." ";
		}
		$membersString = rtrim($membersString);
		$membersIDsString = rtrim($membersIDsString);

		// urls
		$urlsString = "";
		$urlsIDsString = "";
		foreach ($urls as $url) {
			$urlsString .= $url["url"]."\n";
			$urlsIDsString .= $url["id"]." ";
		}
		$urlsString = substr($urlsString, 0, strlen($urlsString) -1);
		$urlsIDsString = rtrim($urlsIDsString);

		$html = '
			<form method="POST" action="'.make_link("artist/edited/".$artist['id']).'">
				'.$user->get_auth_html().'
				<table>
					<tr><td>Name:</td><td><input type="text" name="name" value="'.$artistName.'" />
										  <input type="hidden" name="id" value="'.$artistID.'" /></td></tr>
					<tr><td>Alias:</td><td><input type="text" name="aliases" value="'.$aliasesString.'" />
										   <input type="hidden" name="aliasesIDs" value="'.$aliasesIDsString.'" /></td></tr>
					<tr><td>Members:</td><td><input type="text" name="members" value="'.$membersString.'" />
											 <input type="hidden" name="membersIDs" value="'.$membersIDsString.'" /></td></tr>
					<tr><td>URLs:</td><td><textarea name="urls">'.$urlsString.'</textarea>
										  <input type="hidden" name="urlsIDs" value="'.$urlsIDsString.'" /></td></tr>
					<tr><td>Notes:</td><td><textarea name="notes">'.$artistNotes.'</textarea></td></tr>
					<tr><td colspan="2"><input type="submit" value="Submit" /></td></tr>
				</table>
			</form>
		';

		global $page;
		$page->add_block(new Block("Edit artist", $html, "main", 10));
	}
	
	public function new_artist_composer() {
		global $page, $user;

		$html = "<form action=".make_link("artist/create")." method='POST'>
			".$user->get_auth_html()."
			<table>
				<tr><td>Name:</td><td><input type='text' name='name' /></td></tr>
				<tr><td>Aliases:</td><td><input type='text' name='aliases' /></td></tr>
				<tr><td>Members:</td><td><input type='text' name='members' /></td></tr>
				<tr><td>URLs:</td><td><textarea name='urls'></textarea></td></tr>
				<tr><td>Notes:</td><td><textarea name='notes'></textarea></td></tr>
				<tr><td colspan='2'><input type='submit' value='Submit' /></td></tr>
			</table>
		";

		$page->set_title("Artists");
		$page->set_heading("Artists");
		$page->add_block(new Block("Artists", $html, "main", 10));
	}
	
	public function list_artists($artists, $pageNumber, $totalPages) {
		global $user, $page;

		$html = "<table id='poolsList' class='zebra'>".
			"<thead><tr>".
			"<th>Name</th>".
			"<th>Type</th>".
			"<th>Last updater</th>".
			"<th>Posts</th>";

		if(!$user->is_anonymous()) $html .= "<th colspan='2'>Action</th>"; // space for edit link
			
		$html .= "</tr></thead>";

		$deletionLinkActionArray = array(
			'artist' => 'artist/nuke/',
			'alias' => 'artist/alias/delete/',
			'member' => 'artist/member/delete/',
		);

		$editionLinkActionArray = array(
			'artist' => 'artist/edit/',
			'alias' => 'artist/alias/edit/',
			'member' => 'artist/member/edit/',
		);

		$typeTextArray = array(
			'artist' => 'Artist',
			'alias' => 'Alias',
			'member' => 'Member',
		);

		foreach ($artists as $artist) {
			if ($artist['type'] != 'artist')
				$artist['name'] = str_replace("_", " ", $artist['name']);

			$elementLink = "<a href='".make_link("artist/view/".$artist['artist_id'])."'>".str_replace("_", " ", $artist['name'])."</a>";
			//$artist_link = "<a href='".make_link("artist/view/".$artist['artist_id'])."'>".str_replace("_", " ", $artist['artist_name'])."</a>";
			$user_link = "<a href='".make_link("user/".$artist['user_name'])."'>".$artist['user_name']."</a>";
			$edit_link = "<a href='".make_link($editionLinkActionArray[$artist['type']].$artist['id'])."'>Edit</a>";
			$del_link = "<a href='".make_link($deletionLinkActionArray[$artist['type']].$artist['id'])."'>Delete</a>";

			$html .= "<tr>".
				"<td class='left'>".$elementLink;

			//if ($artist['type'] == 'member')
			//	$html .= " (member of ".$artist_link.")";

			//if ($artist['type'] == 'alias')
			//	$html .= " (alias for ".$artist_link.")";

			$html .= "</td>".
				"<td>".$typeTextArray[$artist['type']]."</td>".
				"<td>".$user_link."</td>".
				"<td>".$artist['posts']."</td>";

			if(!$user->is_anonymous()) $html .= "<td>".$edit_link."</td>";
			if($user->is_admin()) $html .= "<td>".$del_link."</td>";

			$html .= "</tr>";
		}

		$html .= "</tbody></table>";

		$page->set_title("Artists");
		$page->set_heading("Artists");
		$page->add_block(new Block("Artists", $html, "main", 10));

		$this->display_paginator($page, "artist/list", null, $pageNumber, $totalPages);
	}

	public function show_new_alias_composer($artistID) {
		global $user;

		$html = '
			<form method="POST" action='.make_link("artist/alias/add").'>
				'.$user->get_auth_html().'
				  <table>
					<tr><td>Alias:</td><td><input type="text" name="aliases" />
										   <input type="hidden" name="artistID" value='.$artistID.' /></td></tr>
					<tr><td colspan="2"><input type="submit" value="Submit" /></td></tr>
				</table>
			</form>		
		';

		global $page;
		$page->add_block(new Block("Artist Aliases", $html, "main", 20));
	}

	public function show_new_member_composer($artistID) {
		global $user;

		$html = '
			<form method="POST" action='.make_link("artist/member/add").'>
				'.$user->get_auth_html().'
				<table>
					<tr><td>Members:</td><td><input type="text" name="members" />
										   <input type="hidden" name="artistID" value='.$artistID.' /></td></tr>
					<tr><td colspan="2"><input type="submit" value="Submit" /></td></tr>
				</table>
			</form>
		';

		global $page;
		$page->add_block(new Block("Artist members", $html, "main", 30));
	}

	public function show_new_url_composer($artistID) {
		global $user;

		$html = '
			<form method="POST" action='.make_link("artist/url/add").'>
				'.$user->get_auth_html().'
				<table>
					<tr><td>URL:</td><td><textarea name="urls"></textarea>
									   <input type="hidden" name="artistID" value='.$artistID.' /></td></tr>
					<tr><td colspan="2"><input type="submit" value="Submit" /></td></tr>
				</table>
			</form>
		';

		global $page;
		$page->add_block(new Block("Artist URLs", $html, "main", 40));
	}

	public function show_alias_editor($alias) {
		global $user;

		$html = '
			<form method="POST" action="'.make_link("artist/alias/edited/".$alias['id']).'">
				'.$user->get_auth_html().'
				<label for="alias">Alias:</label>
				<input type="text" name="alias" value="'.$alias['alias'].'" />
				<input type="hidden" name="aliasID" value="'.$alias['id'].'" />
				<input type="submit" value="Submit" />
			</form>
		';

		global $page;
		$page->add_block(new Block("Edit Alias", $html, "main", 10));
	}

	public function show_url_editor($url) {
		global $user;

		$html = '
			<form method="POST" action="'.make_link("artist/url/edited/".$url['id']).'">
				'.$user->get_auth_html().'
				<label for="url">URL:</label>
				<input type="text" name="url" value="'.$url['url'].'" />
				<input type="hidden" name="urlID" value="'.$url['id'].'" />
				<input type="submit" value="Submit" />
			</form>
		';

		global $page;
		$page->add_block(new Block("Edit URL", $html, "main", 10));
	}

	public function show_member_editor($member) {
		global $user;

		$html = '
			<form method="POST" action="'.make_link("artist/member/edited/".$member['id']).'">
				'.$user->get_auth_html().'
				<label for="member">Member name:</label>
				<input type="text" name="name" value="'.$member['name'].'" />
				<input type="hidden" name="memberID" value="'.$member['id'].'" />
				<input type="submit" value="Submit" />
			</form>
		';

		global $page;
		$page->add_block(new Block("Edit Member", $html, "main", 10));
	}

	public function show_artist($artist, $aliases, $members, $urls, $images, $userIsLogged, $userIsAdmin) {
		global $page;

		$artist_link = "<a href='".make_link("post/list/".$artist['name']."/1")."'>".str_replace("_", " ", $artist['name'])."</a>";

		$html = "<table id='poolsList' class='zebra'>
					<thead>
						<tr>
							<th></th>
							<th></th>";
			
		if ($userIsLogged) $html .= "<th></th>";
		if ($userIsAdmin) $html .= "<th></th>";

		$html .= "  <tr>
					</thead>

					<tr>
						<td class='left'>Name:</td>
						<td class='left'>".$artist_link."</td>";
		if ($userIsLogged) $html .= "<td></td>";
		if ($userIsAdmin) $html .= "<td></td>";
		$html .= "</tr>";

		$html .= $this->render_aliases($aliases, $userIsLogged, $userIsAdmin);
		$html .= $this->render_members($members, $userIsLogged, $userIsAdmin);
		$html .= $this->render_urls($urls, $userIsLogged, $userIsAdmin);

		$html .= "<tr>
						<td class='left'>Notes:</td>
						<td class='left'>".$artist["notes"]."</td>";
		if ($userIsLogged) $html .= "<td></td>";
		if ($userIsAdmin) $html .= "<td></td>";
		//TODO how will notes be edited? On edit artist? (should there be an editartist?) or on a editnotes?
		//same question for deletion
		$html .= "</tr>
		</table>";

		$page->set_title("Artist");
		$page->set_heading("Artist");
		$page->add_block(new Block("Artist", $html, "main", 10));

		//we show the images for the artist
		$artist_images = "";
		foreach($images as $image) {
			$thumb_html = $this->build_thumb_html($image);
				
			$artist_images .= '<span class="thumb">'.
				'<a href="$image_link">'.$thumb_html.'</a>'.
				'</span>';
		}
			
		$page->add_block(new Block("Artist Images", $artist_images, "main", 20));
	}

	/**
	 * @param $aliases
	 * @param $userIsLogged
	 * @param $userIsAdmin
	 * @return string
	 */
	private function render_aliases($aliases, $userIsLogged, $userIsAdmin) {
		$html = "";
		if(count($aliases) > 0) {
			$aliasViewLink = str_replace("_", " ", $aliases[0]['alias_name']); // no link anymore
			$aliasEditLink = "<a href='" . make_link("artist/alias/edit/" . $aliases[0]['alias_id']) . "'>Edit</a>";
			$aliasDeleteLink = "<a href='" . make_link("artist/alias/delete/" . $aliases[0]['alias_id']) . "'>Delete</a>";

			$html .= "<tr>
							  <td class='left'>Aliases:</td>
							  <td class='left'>" . $aliasViewLink . "</td>";

			if ($userIsLogged)
				$html .= "<td class='left'>" . $aliasEditLink . "</td>";

			if ($userIsAdmin)
				$html .= "<td class='left'>" . $aliasDeleteLink . "</td>";

			$html .= "</tr>";

			if (count($aliases) > 1) {
				for ($i = 1; $i < count($aliases); $i++) {
					$aliasViewLink = str_replace("_", " ", $aliases[$i]['alias_name']); // no link anymore
					$aliasEditLink = "<a href='" . make_link("artist/alias/edit/" . $aliases[$i]['alias_id']) . "'>Edit</a>";
					$aliasDeleteLink = "<a href='" . make_link("artist/alias/delete/" . $aliases[$i]['alias_id']) . "'>Delete</a>";

					$html .= "<tr>
									  <td class='left'>&nbsp;</td>
									  <td class='left'>" . $aliasViewLink . "</td>";
					if ($userIsLogged)
						$html .= "<td class='left'>" . $aliasEditLink . "</td>";
					if ($userIsAdmin)
						$html .= "<td class='left'>" . $aliasDeleteLink . "</td>";

					$html .= "</tr>";
				}
			}
		}
		return $html;
	}

	/**
	 * @param $members
	 * @param $userIsLogged
	 * @param $userIsAdmin
	 * @return string
	 */
	private function render_members($members, $userIsLogged, $userIsAdmin) {
		$html = "";
		if(count($members) > 0) {
			$memberViewLink = str_replace("_", " ", $members[0]['name']); // no link anymore
			$memberEditLink = "<a href='" . make_link("artist/member/edit/" . $members[0]['id']) . "'>Edit</a>";
			$memberDeleteLink = "<a href='" . make_link("artist/member/delete/" . $members[0]['id']) . "'>Delete</a>";

			$html .= "<tr>
							<td class='left'>Members:</td>
							<td class='left'>" . $memberViewLink . "</td>";
			if ($userIsLogged)
				$html .= "<td class='left'>" . $memberEditLink . "</td>";
			if ($userIsAdmin)
				$html .= "<td class='left'>" . $memberDeleteLink . "</td>";

			$html .= "</tr>";

			if (count($members) > 1) {
				for ($i = 1; $i < count($members); $i++) {
					$memberViewLink = str_replace("_", " ", $members[$i]['name']); // no link anymore
					$memberEditLink = "<a href='" . make_link("artist/member/edit/" . $members[$i]['id']) . "'>Edit</a>";
					$memberDeleteLink = "<a href='" . make_link("artist/member/delete/" . $members[$i]['id']) . "'>Delete</a>";

					$html .= "<tr>
							<td class='left'>&nbsp;</td>
							<td class='left'>" . $memberViewLink . "</td>";
					if ($userIsLogged)
						$html .= "<td class='left'>" . $memberEditLink . "</td>";
					if ($userIsAdmin)
						$html .= "<td class='left'>" . $memberDeleteLink . "</td>";

					$html .= "</tr>";
				}
			}
		}
		return $html;
	}

	/**
	 * @param $urls
	 * @param $userIsLogged
	 * @param $userIsAdmin
	 * @return string
	 */
	private function render_urls($urls, $userIsLogged, $userIsAdmin) {
		$html = "";
		if(count($urls) > 0) {
			$urlViewLink = "<a href='" . str_replace("_", " ", $urls[0]['url']) . "' target='_blank'>" . str_replace("_", " ", $urls[0]['url']) . "</a>";
			$urlEditLink = "<a href='" . make_link("artist/url/edit/" . $urls[0]['id']) . "'>Edit</a>";
			$urlDeleteLink = "<a href='" . make_link("artist/url/delete/" . $urls[0]['id']) . "'>Delete</a>";

			$html .= "<tr>
							<td class='left'>URLs:</td>
							<td class='left'>" . $urlViewLink . "</td>";

			if ($userIsLogged)
				$html .= "<td class='left'>" . $urlEditLink . "</td>";

			if ($userIsAdmin)
				$html .= "<td class='left'>" . $urlDeleteLink . "</td>";

			$html .= "</tr>";

			if (count($urls) > 1) {
				for ($i = 1; $i < count($urls); $i++) {
					$urlViewLink = "<a href='" . str_replace("_", " ", $urls[$i]['url']) . "' target='_blank'>" . str_replace("_", " ", $urls[$i]['url']) . "</a>";
					$urlEditLink = "<a href='" . make_link("artist/url/edit/" . $urls[$i]['id']) . "'>Edit</a>";
					$urlDeleteLink = "<a href='" . make_link("artist/url/delete/" . $urls[$i]['id']) . "'>Delete</a>";

					$html .= "<tr>
								<td class='left'>&nbsp;</td>
								<td class='left'>" . $urlViewLink . "</td>";
					if ($userIsLogged)
						$html .= "<td class='left'>" . $urlEditLink . "</td>";

					if ($userIsAdmin)
						$html .= "<td class='left'>" . $urlDeleteLink . "</td>";

					$html .= "</tr>";
				}
				return $html;
			}
		}
		return $html;
	}

}

