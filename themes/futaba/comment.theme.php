<?php

class CustomCommentListTheme extends CommentListTheme {
	public $inner_id = 0;

	public function display_comment_list($images, $page_number, $total_pages, $can_post) {
		global $config, $page;

		//$prev = $page_number - 1;
		//$next = $page_number + 1;

		$page_title = $config->get_string('title');
		$page->set_title($page_title);
		$page->set_heading($page_title);
		$page->disable_left();
		$page->add_block(new Block(null, $this->build_upload_box(), "main", 0));
		$page->add_block(new Block(null, "<hr>", "main", 80));
		$this->display_paginator($page, "comment/list", null, $page_number, $total_pages);

		// parts for each image
		$position = 10;
		foreach($images as $pair) {
			$image = $pair[0];
			$comments = $pair[1];

			$h_filename = html_escape($image->filename);
			$h_filesize = to_shorthand_int($image->filesize);
			$w = $image->width;
			$h = $image->height;

			$comment_html = "";
			$comment_id = 0;
			foreach($comments as $comment) {
				$this->inner_id = $comment_id++;
				$comment_html .= $this->comment_to_html($comment, false);
			}

			$html  = "<p style='clear:both'>&nbsp;</p><hr height='1'>";
			$html .= "File: <a href=\"".make_link("post/view/{$image->id}")."\">$h_filename</a> - ($h_filesize, {$w}x{$h}) - ";
			$html .= html_escape($image->get_tag_list());
			$html .= "<div style='text-align: left'>";
			$html .=   "<div style='float: left;'>" . $this->build_thumb_html($image) . "</div>";
			$html .=   "<div class='commentset'>$comment_html</div>";
			$html .= "</div>";

			$page->add_block(new Block(null, $html, "main", $position++));
		}
	}
	
	public function display_recent_comments($comments) {
		// sidebar fails in this theme
	}

	public function build_upload_box() {
		return "[[ insert upload-and-comment extension here ]]";
	}


	protected function comment_to_html(Comment $comment, $trim=false) {
		$inner_id = $this->inner_id; // because custom themes can't add params, because PHP
		global $user;

		$tfe = new TextFormattingEvent($comment->comment);
		send_event($tfe);

		//$i_uid = int_escape($comment->owner_id);
		$h_name = html_escape($comment->owner_name);
		//$h_poster_ip = html_escape($comment->poster_ip);
		$h_comment = ($trim ? substr($tfe->stripped, 0, 50)."..." : $tfe->formatted);
		$i_comment_id = int_escape($comment->comment_id);
		$i_image_id = int_escape($comment->image_id);

		$stripped_nonl = str_replace("\n", "\\n", substr($tfe->stripped, 0, 50));
		$stripped_nonl = str_replace("\r", "\\r", $stripped_nonl);
		$h_userlink = "<a href='".make_link("user/$h_name")."'>$h_name</a>";
		$h_date = $comment->posted;
		$h_del = $user->can("delete_comment") ?
			' - <a onclick="return confirm(\'Delete comment by '.$h_name.':\\n'.$stripped_nonl.'\');" '.
			'href="'.make_link('comment/delete/'.$i_comment_id.'/'.$i_image_id).'">Del</a>' : '';
		$h_reply = "[<a href='".make_link("post/view/$i_image_id")."'>Reply</a>]";

		if($inner_id == 0) {
			return "<div class='comment' style='margin-top: 8px;'>$h_userlink$h_del $h_date No.$i_comment_id $h_reply<p>$h_comment</p></div>";
		}
		else {
			return "<table><tr><td nowrap class='doubledash'>&gt;&gt;</td><td>".
				"<div class='reply'>$h_userlink$h_del $h_date No.$i_comment_id $h_reply<p>$h_comment</p></div>" .
				"</td></tr></table>";
		}
	}
}

