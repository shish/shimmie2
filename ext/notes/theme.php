<?php declare(strict_types=1);
class NotesTheme extends Themelet
{
    public function note_button(int $image_id): string
    {
        return '
			<!-- <a href="#" id="addnotelink" >Add a note</a> -->
			<form action="" method="">
			<input type="button" id="addnote" value="Add Note">
			<input type="hidden" name="image_id" value="'.$image_id.'">
			</form>
		';
    }
    public function request_button(int $image_id): string
    {
        return make_form(make_link("note/add_request")) . '
						<input id="noterequest" type="submit" value="Add Note Request">
						<input type="hidden" name="image_id" value="'.$image_id.'">
						</form>
					';
    }
    public function nuke_notes_button(int $image_id): string
    {
        return make_form(make_link("note/nuke_notes")) . '
							<input id="noterequest" type="submit" value="Nuke Notes" onclick="return confirm_action(\'Are you sure?\')">
							<input type="hidden" name="image_id" value="'.$image_id.'">
							</form>
				';
    }
    public function nuke_requests_button(int $image_id): string
    {
        return make_form(make_link("note/nuke_requests")) . '
							<input id="noterequest" type="submit" value="Nuke Requests" onclick="return confirm_action()">
							<input type="hidden" name="image_id" value="'.$image_id.'">
							</form>
						';
    }

    public function search_notes_page(Page $page): void
    { //IN DEVELOPMENT, NOT FULLY WORKING
        $html = '<form method="GET" action="'.make_link("post/list/note=").'">
		<input placeholder="Search Notes" type="text" name="search"/>
		<input type="submit" style="display: none;" value="Find"/>
		</form>';

        $page->set_title(html_escape("Search Note"));
        $page->set_heading(html_escape("Search Note"));
        $page->add_block(new Block("Search Note", $html, "main", 10));
    }

    // check action POST on form
    public function display_note_system(Page $page, int $image_id, array $recovered_notes, bool $adminOptions): void
    {
        $base_href = get_base_href();

        $page->add_html_header("<script defer src='$base_href/ext/notes/lib/jquery.imgnotes-1.0.min.js' type='text/javascript'></script>");
        $page->add_html_header("<script defer src='$base_href/ext/notes/lib/jquery.imgareaselect-1.0.0-rc1.min.js' type='text/javascript'></script>");
        $page->add_html_header("<link rel='stylesheet' type='text/css' href='$base_href/ext/notes/lib/jquery.imgnotes-1.0.min.css' />");

        $to_json = [];
        foreach ($recovered_notes as $note) {
            $parsedNote = $note["note"];
            $parsedNote = str_replace("\n", "\\n", $parsedNote);
            $parsedNote = str_replace("\r", "\\r", $parsedNote);

            $to_json[] = [
                'x1'      => $note["x1"],
                'y1'      => $note["y1"],
                'height'  => $note["height"],
                'width'   => $note["width"],
                'note'    => $parsedNote,
                'note_id' => $note["id"],
            ];
        }

        $html = "<script type='text/javascript'>notes = ".json_encode($to_json)."</script>";

        $html .= "
	<div id='noteform'>
		".make_form(make_link("note/add_note"))."
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
			".make_form(make_link("note/edit_note"))."
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

        if ($adminOptions) {
            $html .= "
				".make_form(make_link("note/delete_note"))."
				<input type='hidden' name='image_id' value='".$image_id."' />
				<input type='hidden' name='note_id' value='' id='DeleteNoteNoteID' />
				<table>
					<tr>
						<td><input type='submit' value='Delete note' /></td>
					</tr>
				</table>
			</form>
";
        }

        $html .= "</div>";

        $page->add_block(new Block(null, $html, "main", 1, 'note_system'));
    }


    public function display_note_list($images, $pageNumber, $totalPages)
    {
        global $page;
        $pool_images = '';
        foreach ($images as $pair) {
            $image = $pair[0];

            $thumb_html = $this->build_thumb_html($image);

            $pool_images .= '<span class="thumb">'.
                            '    <a href="$image_link">'.$thumb_html.'</a>'.
                            '</span>';
        }
        $this->display_paginator($page, "note/list", null, $pageNumber, $totalPages);

        $page->set_title("Notes");
        $page->set_heading("Notes");
        $page->add_block(new Block("Notes", $pool_images, "main", 20));
    }

    public function display_note_requests($images, $pageNumber, $totalPages)
    {
        global $page;

        $pool_images = '';
        foreach ($images as $pair) {
            $image = $pair[0];

            $thumb_html = $this->build_thumb_html($image);

            $pool_images .= '<span class="thumb">'.
                            '    <a href="$image_link">'.$thumb_html.'</a>'.
                            '</span>';
        }
        $this->display_paginator($page, "requests/list", null, $pageNumber, $totalPages);

        $page->set_title("Note Requests");
        $page->set_heading("Note Requests");
        $page->add_block(new Block("Note Requests", $pool_images, "main", 20));
    }

    private function get_history(array $histories): string
    {
        global $user;

        $html = "<table id='poolsList' class='zebra'>".
                "<thead><tr>".
                "<th>Post</th>".
                "<th>Note</th>".
                "<th>Body</th>".
                "<th>Updater</th>".
                "<th>Date</th>";

        if (!$user->is_anonymous()) {
            $html .= "<th>Action</th>";
        }

        $html .= "</tr></thead>".
                 "<tbody>";

        foreach ($histories as $history) {
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

            if (!$user->is_anonymous()) {
                $html .= "<td>".$revert_link."</td>";
            }
        }

        $html .= "</tr></tbody></table>";

        return $html;
    }

    public function display_histories($histories, $pageNumber, $totalPages)
    {
        global $page;

        $html = $this->get_history($histories);

        $page->set_title("Note Updates");
        $page->set_heading("Note Updates");
        $page->add_block(new Block("Note Updates", $html, "main", 10));

        $this->display_paginator($page, "note/updated", null, $pageNumber, $totalPages);
    }

    public function display_history($histories, $pageNumber, $totalPages)
    {
        global $page;

        $html = $this->get_history($histories);

        $page->set_title("Note History");
        $page->set_heading("Note History");
        $page->add_block(new Block("Note History", $html, "main", 10));

        $this->display_paginator($page, "note/updated", null, $pageNumber, $totalPages);
    }

    public function get_help_html(): string
    {
        return '<p>Search for posts with notes.</p>
        <div class="command_example">
        <pre>note=noted</pre>
        <p>Returns posts with a note matching "noted".</p>
        </div>
        <div class="command_example">
        <pre>notes>0</pre>
        <p>Returns posts with 1 or more notes.</p>
        </div>
        <p>Can use &lt;, &lt;=, &gt;, &gt;=, or =.</p>
        <div class="command_example">
        <pre>notes_by=username</pre>
        <p>Returns posts with note(s) by "username".</p>
        </div>
        <div class="command_example">
        <pre>notes_by_user_id=123</pre>
        <p>Returns posts with note(s) by user 123.</p>
        </div>
        ';
    }
}
