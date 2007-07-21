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
		$page->disable_left();
	}

	public function display_recent_comments($page, $comments) {
		// no recent comments in this theme
		//$html = $this->comments_to_html($comments, true);
		//$html .= "<p><a class='more' href='".make_link("comment/list")."'>Full List</a>";
		//$page->add_block(new Block("Comments", $html, "left"));
	}

	public function display_comments($page, $comments, $postbox, $image_id) {
		$count = count($comments);
		if($postbox) {
			$page->add_block(new Block("$count Comments",
					$this->comments_to_html($comments).
					$this->build_postbox($image_id), "main", 30));
		}
		else {
			$page->add_block(new Block("$count Comments",
					$this->comments_to_html($comments), "main", 30));
		}
	}


	private function comments_to_html($comments, $trim=false) {
		$html = "";
		foreach($comments as $comment) {
			$html .= $this->comment_to_html($comment, $trim);
		}
		return $html;
	}

	private function comment_to_html($comment, $trim=false) {
		global $user;

		$tfe = new TextFormattingEvent($comment->comment);
		send_event($tfe);

		$i_uid = int_escape($comment->owner_id);
		$h_name = html_escape($comment->owner_name);
		$h_poster_ip = html_escape($comment->poster_ip);
		$h_comment = ($trim ? substr($tfe->stripped, 0, 50)."..." : $tfe->formatted);
		$i_comment_id = int_escape($comment->comment_id);
		$i_image_id = int_escape($comment->image_id);
		$h_posted = html_escape($comment->posted);

		$h_userlink = "<a class='username' href='".make_link("user/$h_name")."'>$h_name</a>";
		$h_dellink = $user->is_admin() ? 
			" ($h_poster_ip, <a ".
			"onclick=\"return confirm('Delete comment by $h_name:\\n".$tfe->stripped."');\" ".
			"href='".make_link("comment/delete/$i_comment_id/$i_image_id")."'>Del</a>)" : "";
		$h_imagelink = $trim ? "<a href='".make_link("post/view/$i_image_id")."'>&gt;&gt;&gt;</a>\n" : "";
		return "<p class='comment'>$h_userlink $h_dellink<br/><b>Posted on $h_posted</b><br/>$h_comment</p>";
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
		$count = count($comments);

		$html  = "<div style='text-align: left'>";
		$html .=   "<div style='float: left; margin-right: 16px;'>" . build_thumb_html($image) . "</div>";
		$html .=   "<div style='float: right; margin-left: 16px; width: 200px; margin-bottom: 32px;'>";
		foreach($image->get_tag_array() as $tag) {
			$u_tag = url_escape($tag);
			$html .= "<br><a href='".make_link("post/list/$u_tag/1")."'>".html_escape($tag)."</a>";
		}
		$html .=   "</div>";
		$html .=   "<div style='margin-left: 250px;'>";
		$html .=   "<b>$count Comments</b><br>";
		$html .=   $this->comments_to_html($comments);
		$html .=   "</div>";
		$html .= "</div>";
		$html .= "<div style='clear: both; display: block; height: 64px;'>&nbsp;</div>";

		$page->add_block(new Block(null, $html, "main", $position));
	}
}
?>
