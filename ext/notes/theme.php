<?php
class NotesTheme extends Themelet {
	public function note_button($image_id) {
		return '		
			<!-- <a href="#" id="addnotelink" >Add a note</a> -->
			<form action="" method="">
				<input type="button" id="addnote" value="Add Note">
				<input type="hidden" name="image_id" value="'.$image_id.'">
			</form>
		';
	}
	public function request_button($image_id) {
		return make_form("note/add_request") . '
						<input id="noterequest" type="submit" value="Add Note Request">
						<input type="hidden" name="image_id" value="'.$image_id.'">
						</form>
					';
	}
	public function nuke_notes_button($image_id) {
		return make_form("note/nuke_notes") . '
							<input id="noterequest" type="submit" value="Nuke Notes" onclick="return confirm_action(\'Are you sure?\')">
							<input type="hidden" name="image_id" value="'.$image_id.'">
							</form>
				';
	}
	public function nuke_requests_button($image_id) {
		return make_form("note/nuke_requests") . '
							<input id="noterequest" type="submit" value="Nuke Requests" onclick="return confirm_action()">
							<input type="hidden" name="image_id" value="'.$image_id.'">
							</form>
						';
	}
	
	public function search_notes_page(Page $page) { //IN DEVELOPMENT, NOT FULLY WORKING
		$html = make_form("post/list/note=", "GET").'
		<input placeholder="Search Notes" type="text" name="search"/>
		<input type="submit" style="display: none;" value="Find"/>
		</form>';
	
		$page->set_title(html_escape("Search Note"));
		$page->set_heading(html_escape("Search Note"));
		$page->add_block(new Block("Search Note", $html, "main", 10));
	}
		
	// check action POST on form
	public function display_note_system(Page $page, $image_id, $recovered_notes, $adminOptions)
	{
		$to_json = array();
		foreach($recovered_notes as $note)
		{
			$parsedNote = $note["note"];
			$parsedNote = str_replace("\n", "\\n", $parsedNote);
			$parsedNote = str_replace("\r", "\\r", $parsedNote);

			$to_json[] = array(
				'x1' => $note["x1"],
				'y1' => $note["y1"],
				'height' => $note["height"],
				'width' => $note["width"],
				'note' => $parsedNote,
				'note_id' => $note["id"],
			);
		}

		$html = "<script type='text/javascript'>";
		$html .= "notes = " . json_encode($to_json);
		$html .= "</script>
	
	<div id='noteform'>
		".make_form("note/add_note")."
			<input type='hidden' name='image_id' value='".$image_id."' />
			<input name='note_x1' type='hidden' value='' id='NoteX1' />
			<input name='note_y1' type='hidden' value='' id='NoteY1' />
			<input name='note_height' type='hidden' value='' id='NoteHeight' />
			<input name='note_width' type='hidden' value='' id='NoteWidth' />
			
			<table>
				<tr>
					<td colspan='2'>
						<textarea name='note_text' id='NoteNote' ></textarea>
					</td>
				</tr>
				<tr>
					<td><input type='submit' value='Add Note' /></td>
					<td><input type='button' value='Cancel' id='cancelnote' /></td>
			  	</tr>
			</table>
			
		</form>
	</div>
		<div id='noteEditForm'>
			".make_form("note/edit_note")."
				<input type='hidden' name='image_id' value='".$image_id."' />
				<input type='hidden' name='note_id' id='EditNoteID' value='' />
				<input name='note_x1' type='hidden' value='' id='EditNoteX1' />
				<input name='note_y1' type='hidden' value='' id='EditNoteY1' />
				<input name='note_height' type='hidden' value='' id='EditNoteHeight' />
				<input name='note_width' type='hidden' value='' id='EditNoteWidth' />
				<table>
					<tr>
						<td colspan='2'>
							<textarea name='note_text' id='EditNoteNote' ></textarea>
						</td>
					</tr>
					<tr>
						<td><input type='submit' value='Save Note' /></td>
						<td><input type='button' value='Cancel' id='EditCancelNote' /></td>
					</tr>
				</table>
			</form>";

		if($adminOptions)
			$html .= "
				".make_form("note/delete_note")."
				<input type='hidden' name='image_id' value='".$image_id."' />
				<input type='hidden' name='note_id' value='' id='DeleteNoteNoteID' />
				<table>
					<tr>
						<td><input type='submit' value='Delete note' /></td>
					</tr>
				</table>
			</form>
";

		$html .= "</div>";

		$page->add_block(new Block(null, $html, "main", 1));
	}
		
	
	public function display_note_list($images, $pageNumber, $totalPages) {
		global $page;
		$pool_images = '';
		foreach($images as $pair) {
			$image = $pair[0];

			$thumb_html = $this->build_thumb_html($image);

			$pool_images .= '<span class="thumb">'.
					   		'<a href="$image_link">'.$thumb_html.'</a>'.
					   '</span>';

			
		}
		$this->display_paginator($page, "note/list", null, $pageNumber, $totalPages);
		
		$page->set_title("Notes");
		$page->set_heading("Notes");
		$page->add_block(new Block("Notes", $pool_images, "main", 20));
	}
	
	public function display_note_requests($images, $pageNumber, $totalPages) {
		global $page;
		$pool_images = '';
		foreach($images as $pair) {
			$image = $pair[0];

			$thumb_html = $this->build_thumb_html($image);

			$pool_images .= '<span class="thumb">'.
					   		'<a href="$image_link">'.$thumb_html.'</a>'.
					   '</span>';

			
		}
		$this->display_paginator($page, "requests/list", null, $pageNumber, $totalPages);
		
		$page->set_title("Note Requests");
		$page->set_heading("Note Requests");
		$page->add_block(new Block("Note Requests", $pool_images, "main", 20));
	}

	private function get_history($histories) {
		global $user;

		$html = "<table id='poolsList' class='zebra'>".
			"<thead><tr>".
			"<th>Image</th>".
			"<th>Note</th>".
			"<th>Body</th>".
			"<th>Updater</th>".
			"<th>Date</th>";

		if(!$user->is_anonymous()){
			$html .= "<th>Action</th>";
		}

		$html .= "</tr></thead>".
			"<tbody>";

		foreach($histories as $history) {
			$image_link = "<a href='".make_link("post/view/".$history['image_id'])."'>".$history['image_id']."</a>";
			$history_link = "<a href='".make_link("note/history/".$history['note_id'])."'>".$history['note_id'].".".$history['review_id']."</a>";
			$user_link = "<a href='".make_link("user/".$history['user_name'])."'>".$history['user_name']."</a>";
			$revert_link = "<a href='".make_link("note/revert/".$history['note_id']."/".$history['review_id'])."'>Revert</a>";

			$html .= "<tr>".
				"<td>".$image_link."</td>".
				"<td>".$history_link."</td>".
				"<td style='text-align:left;'>".$history['note']."</td>".
				"<td>".$user_link."</td>".
				"<td>".autodate($history['date'])."</td>";

			if(!$user->is_anonymous()){
				$html .= "<td>".$revert_link."</td>";
			}

		}

		$html .= "</tr></tbody></table>";

		return $html;
	}

	public function display_histories($histories, $pageNumber, $totalPages) {
		global $page;

		$html = $this->get_history($histories);

		$page->set_title("Note Updates");
		$page->set_heading("Note Updates");
		$page->add_block(new Block("Note Updates", $html, "main", 10));

		$this->display_paginator($page, "note/updated", null, $pageNumber, $totalPages);
	}
	
	public function display_history($histories, $pageNumber, $totalPages) {
		global $page;

		$html = $this->get_history($histories);

		$page->set_title("Note History");
		$page->set_heading("Note History");
		$page->add_block(new Block("Note History", $html, "main", 10));
		
		$this->display_paginator($page, "note/updated", null, $pageNumber, $totalPages);
	}
}

