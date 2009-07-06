<?php

class CommentListTheme extends Themelet {
	/*
	 * Do the basics of the comments page
	 *
	 * $page_number = the current page number
	 * $total_pages = the total number of comment pages
	 */
	public function display_page_start(Page $page, $page_number, $total_pages) {
		$prev = $page_number - 1;
		$next = $page_number + 1;

		$h_prev = ($page_number <= 1) ? "Prev" :
			"<a href='".make_link("comment/list/$prev")."'>Prev</a>";
		$h_index = "<a href='".make_link()."'>Index</a>";
		$h_next = ($page_number >= $total_pages) ? "Next" :
			"<a href='".make_link("comment/list/$next")."'>Next</a>";

		$nav = "$h_prev | $h_index | $h_next";

		$page->set_title("Comments");
		$page->set_heading("Comments");
		$page->add_block(new Block("Navigation", $nav, "left"));
		$this->display_paginator($page, "comment/list", null, $page_number, $total_pages);
	}

	/*
	 * Add some comments to the page, probably in a sidebar
	 *
	 * $comments = an array of Comment objects to be shown
	 */
	public function display_recent_comments(Page $page, $comments) {
		$html = $this->comments_to_html($comments, true);
		$html .= "<p><a class='more' href='".make_link("comment/list")."'>Full List</a>";
		$page->add_block(new Block("Comments", $html, "left"));
	}

	/*
	 * Show comments for an image
	 */
	public function display_comments(Page $page, $comments, $postbox, $image_id) {
		if($postbox) {
			$page->add_block(new Block("Comments",
					$this->comments_to_html($comments).
					$this->build_postbox($image_id), "main", 30));
		}
		else {
			if(count($comments) > 0) {
				$page->add_block(new Block("Comments",
						$this->comments_to_html($comments), "main", 30));
			}
		}
	}

	/*
	 * Add a block with thumbnail and comments, as part of the comment
	 * list page
	 */
	public function add_comment_list(Page $page, Image $image, $comments, $position, $with_postbox) {
		$html  = "<div style='text-align: left'>";
		$html .=   "<div style='float: left; margin-right: 16px;'>" . $this->build_thumb_html($image) . "</div>";
		$html .=   "<div style='margin-left: 230px;'>" . $this->comments_to_html($comments) . "</div>";
		$html .= "</div>";
		if($with_postbox) {
			$html .= "<div style='clear:both;'>".($this->build_postbox($image->id))."</div>";
		}
		else {
			// $html .= "<div style='clear:both;'><p><small>You need to create an account before you can comment</small></p></div>";
			$html .= "<div style='clear:both;'><p>&nbsp;</p></div>";
		}

		$page->add_block(new Block("{$image->id}: ".($image->get_tag_list()), $html, "main", $position));
	}


	/*
	 * Various functions which are only used by this theme
	 */


	protected function comments_to_html($comments, $trim=false) {
		$html = "<div class='commentblock'>";
		foreach($comments as $comment) {
			$html .= $this->comment_to_html($comment, $trim);
		}
		$html .= "</div>";
		return $html;
	}

	protected function comment_to_html($comment, $trim=false) {
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
		$stripped_nonl = str_replace("\n", "\\n", $tfe->stripped);
		$stripped_nonl = str_replace("\r", "\\r", $stripped_nonl);
		$h_dellink = $user->is_admin() ?
			"<br>($h_poster_ip, <a ".
			"onclick=\"return confirm('Delete comment by $h_name:\\n$stripped_nonl');\" ".
			"href='".make_link("comment/delete/$i_comment_id/$i_image_id")."'>Del</a>)" : "";
		$h_imagelink = $trim ? "<a href='".make_link("post/view/$i_image_id")."'>&gt;&gt;&gt;</a>\n" : "";
		return "<div class='comment'><p>$h_userlink: $h_comment $h_imagelink $h_dellink</p></div>";
	}

	protected function build_postbox($image_id) {
		$i_image_id = int_escape($image_id);
		$hash = CommentList::get_hash();
		return "
			<form action='".make_link("comment/add")."' method='POST'>
			<input type='hidden' name='image_id' value='$i_image_id' />
			<input type='hidden' name='hash' value='$hash' />
			<textarea name='comment' rows='5' cols='50'></textarea>
			<br><input type='submit' value='Post' />
			</form>
			";
	}
}
?>
