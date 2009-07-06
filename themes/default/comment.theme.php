<?php

class CustomCommentListTheme extends CommentListTheme {
	public function add_comment_list(Page $page, Image $image, $comments, $position, $with_postbox) {
		$html  = "<div style='text-align: left'>";
		$html .=   "<div style='float: left; margin-right: 16px;'>" . $this->build_thumb_html($image) . "</div>";
		$html .=   "<div style='margin-left: 228px;'>" . $this->comments_to_html($comments) . "</div>";
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
		return "
			<div class='rr'>
				<div class='rrtop'><div></div></div>
				<div class='rrcontent'>
				$h_userlink: $h_comment $h_imagelink $h_dellink
				</div>
				<div class='rrbot'><div></div></div>
			</div>";
	}
}
?>
