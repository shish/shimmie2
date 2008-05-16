<?php

class CustomCommentListTheme extends CommentListTheme {
	/*
	 * Do the basics of the comments page
	 *
	 * $page_number = the current page number
	 * $total_pages = the total number of comment pages
	 */
	public function display_page_start($page, $page_number, $total_pages) {
		$prev = $page_number - 1;
		$next = $page_number + 1;

		global $config;
		$page_title = $config->get_string('title');
		$page->set_title($page_title);
		$page->set_heading($page_title);
		$page->disable_left();
		$page->add_block(new Block(null, $this->build_upload_box(), "main", 0));
		$page->add_block(new Block(null, "<hr>", "main", 2));
		$this->display_paginator($page, "comment/list", null, $page_number, $total_pages, 5);
		$page->add_block(new Block(null, "<hr>", "main", 80));
		$this->display_paginator($page, "comment/list", null, $page_number, $total_pages, 90);
	}

	private function build_upload_box() {
		return "[[ insert upload-and-comment extension here ]]";
	}

	/*
	 * Add a block with thumbnail and comments, as part of the comment
	 * list page
	 */
	public function add_comment_list($page, $image, $comments, $position, $with_postbox) {
		$h_filename = html_escape($image->filename);
		$h_filesize = to_shorthand_int($image->filesize);
		$w = $image->width;
		$h = $image->height;

		$html  = "<hr height='1'>";
		$html .= "File: <a href=\"".make_link("post/view/{$image->id}")."\">$h_filename</a> - ($h_filesize, {$w}x{$h}) - ";
		$html .= html_escape($image->get_tag_list());
		$html .= "<div style='text-align: left'>";
		$html .=   "<div style='float: left; margin-left: 16px; margin-right: 16px;'>" . $this->build_thumb_html($image) . "</div>";
		$html .=   "<div class='commentset'>" . $this->comments_to_html($comments) . "</div>";
		$html .= "</div>";

		$page->add_block(new Block(null, $html, "main", $position));
	}

	protected function comments_to_html($comments, $trim=false) {
		$html = "";
		$inner_id = 0;
		foreach($comments as $comment) {
			$html .= $this->comment_to_html($comment, $trim, $inner_id++);
		}
		return $html;
	}

	protected function comment_to_html($comment, $trim=false, $inner_id=0) {
		global $user;

		$tfe = new TextFormattingEvent($comment->comment);
		send_event($tfe);

		$i_uid = int_escape($comment->owner_id);
		$h_name = html_escape($comment->owner_name);
		$h_poster_ip = html_escape($comment->poster_ip);
		$h_comment = ($trim ? substr($tfe->stripped, 0, 50)."..." : $tfe->formatted);
		$i_comment_id = int_escape($comment->comment_id);
		$i_image_id = int_escape($comment->image_id);

		$h_userlink = "<a href='".make_link("user/$h_name")."'>$h_name</a>";
		$h_date = $comment->posted;
		$h_dellink = $user->is_admin() ? 
			" ($h_poster_ip, <a ".
			"onclick=\"return confirm('Delete comment by $h_name:\\n".$tfe->stripped."');\" ".
			"href='".make_link("comment/delete/$i_comment_id/$i_image_id")."'>Del</a>)" : "";
		$h_imagelink = $trim ? "<a href='".make_link("post/view/$i_image_id")."'>&gt;&gt;&gt;</a>\n" : "";

		if($inner_id == 0) {
			return "<div class='comment'>$h_userlink$h_dellink $h_date No.$i_comment_id [Reply]<p>$h_comment</p></div>";
		}
		else {
			return "<table><tr><td nowrap class='doubledash'>&gt;&gt;</td><td>".
				"<div class='reply'>$h_userlink$h_dellink $h_date No.$i_comment_id [Reply]<p>$h_comment</p></div>" .
				"</td></tr></table>";
		}
	}

	protected function build_postbox($image_id) {
		$i_image_id = int_escape($image_id);
		return "
			<form action='".make_link("comment/add")."' method='POST'>
			<input type='hidden' name='image_id' value='$i_image_id' />
			<textarea name='comment' rows='5' cols='50'></textarea>
			<br><input type='submit' value='Post' />
			</form>
			";
	}
}
?>
