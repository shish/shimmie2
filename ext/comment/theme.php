<?php

class CommentListTheme extends Themelet {
	public function display_page_start($page, $page_number, $total_pages) {
		$prev = $page_number - 1;
		$next = $page_number + 1;

		$h_prev = ($page_number <= 1) ? "Prev" :
			"<a href='".make_link("comment/list/$prev")."'>Prev</a>";
		$h_index = "<a href='".make_link("index")."'>Index</a>";
		$h_next = ($page_number >= $total_pages) ? "Next" :
			"<a href='".make_link("comment/list/$next")."'>Next</a>";

		$nav = "$h_prev | $h_index | $h_next";
		
		$page->set_title("Comments");
		$page->set_heading("Comments");
		$page->add_block(new Block("Navigation", $nav, "left"));
		$page->add_block(new Paginator("comment/list", null, $page_number, $total_pages), 90);
	}

	public function display_recent_comments($page, $comments) {
		$html = $this->comments_to_html($comments, true);
		$html .= "<p><a class='more' href='".make_link("comment/list")."'>Full List</a>";
		$page->add_block(new Block("Comments", $html, "left"));
	}

	public function display_comments($page, $comments, $postbox, $image_id) {
		if($postbox) {
			$page->add_block(new Block("Comments",
					$this->comments_to_html($comments).
					$this->build_postbox($image_id), "main", 30));
		}
		else {
			$page->add_block(new Block("Comments",
					$this->comments_to_html($comments), "main", 30));
		}
	}


	private function comments_to_html($comments, $trim=false) {
		$html = "";
		foreach($comments as $comment) {
			$html .= $comment->to_html($trim);
		}
		return $html;
	}

	// FIXME: privatise this
	public function build_postbox($image_id) {
		$i_image_id = int_escape($image_id);
		return "
			<form action='".make_link("comment/add")."' method='POST'>
			<input type='hidden' name='image_id' value='$i_image_id' />
			<textarea name='comment' rows='5' cols='50'></textarea>
			<br><input type='submit' value='Post' />
			</form>
			";
	}


	public function add_comment_list($page, $image, $comments, $position, $with_postbox) {
		$html  = "<div style='text-align: left'>";
		$html .=   "<div style='float: left; margin-right: 16px;'>" . build_thumb_html($image) . "</div>";
		$html .=   $this->comments_to_html($comments);
		$html .= "</div>";
		if($with_postbox) {
			$html .= "<div style='clear:both;'>".($this->build_postbox($image->id))."</div>";
		}
		else {
			$html .= "<div style='clear:both;'><p><small>You need to create an account before you can comment</small></p></div>";
		}

		$page->add_block(new Block("{$image->id}: ".($image->get_tag_list()), $html, "main", $position));
	}
}
?>
