<?php
class CommentListTheme extends Themelet {
	var $comments_shown = 0;
	var $anon_id = 1;

	/**
	 * Display a page with a list of images, and for each image,
	 * the image's comments
	 */
	public function display_comment_list($images, $page_number, $total_pages, $can_post) {
		global $config, $page, $user;

		// aaaaaaargh php
		assert(is_array($images));
		assert(is_numeric($page_number));
		assert(is_numeric($total_pages));
		assert(is_bool($can_post));

		// parts for the whole page
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

		// parts for each image
		$position = 10;
		foreach($images as $pair) {
			$image = $pair[0];
			$comments = $pair[1];

			$thumb_html = $this->build_thumb_html($image);

			$comment_html = "";
			$comment_limit = $config->get_int("comment_list_count", 10);
			$comment_count = count($comments);
			if($comment_limit > 0 && $comment_count > $comment_limit) {
				$hidden = $comment_count - $comment_limit;
				$comment_html .= "<p>showing $comment_limit of $comment_count comments</p>";
				$comments = array_slice($comments, -$comment_limit);
			}
			$this->anon_id = 1;
			foreach($comments as $comment) {
				$comment_html .= $this->comment_to_html($comment);
			}
			if(!$user->is_anonymous()) {
				if($can_post) {
					$comment_html .= $this->build_postbox($image->id);
				}
			} else {
				if ($can_post) {
					if(!$config->get_bool('comment_captcha')) {
						$comment_html .= $this->build_postbox($image->id);
					}
					else {
						$comment_html .= "<a href='".make_link("post/view/".$image->id)."'>Add Comment</a>";
					}
				}
			}

			$html  = "
				<table class='comment_list_table'><tr>
					<td>$thumb_html</td>
					<td>$comment_html</td>
				</tr></table>
			";

			$page->add_block(new Block("{$image->id}: ".($image->get_tag_list()), $html, "main", $position++));
		}
	}


	/**
	 * Add some comments to the page, probably in a sidebar
	 *
	 * $comments = an array of Comment objects to be shown
	 */
	public function display_recent_comments($comments) {
		global $page;
		$this->anon_id = -1;
		$html = "";
		foreach($comments as $comment) {
			$html .= $this->comment_to_html($comment, true);
		}
		$html .= "<p><a class='more' href='".make_link("comment/list")."'>Full List</a>";
		$page->add_block(new Block("Comments", $html, "left"));
	}


	/**
	 * Show comments for an image
	 */
	public function display_image_comments(Image $image, $comments, $postbox) {
		global $page;
		$this->anon_id = 1;
		$html = "";
		foreach($comments as $comment) {
			$html .= $this->comment_to_html($comment);
		}
		if($postbox) {
			$html .= $this->build_postbox($image->id);
		}
		$page->add_block(new Block("Comments", $html, "main", 30));
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

		$anoncode = "";
		if($h_name == "Anonymous" && $this->anon_id >= 0) {
			$anoncode = "<sup>{$this->anon_id}</sup>";
			$this->anon_id++;
		}
		$h_userlink = "<a href='".make_link("user/$h_name")."'>$h_name</a>$anoncode";
		$stripped_nonl = str_replace("\n", "\\n", substr($tfe->stripped, 0, 50));
		$stripped_nonl = str_replace("\r", "\\r", $stripped_nonl);
		$h_dellink = $user->is_admin() ?
			"<br>($h_poster_ip, <a ".
			"onclick=\"return confirm('Delete comment by $h_name:\\n$stripped_nonl');\" ".
			"href='".make_link("comment/delete/$i_comment_id/$i_image_id")."'>Del</a>)" : "";

		if($trim) {
			return "
				$h_userlink: $h_comment
				<a href='".make_link("post/view/$i_image_id")."'>&gt;&gt;&gt;</a>
				$h_dellink
			";
		}
		else {
			//$avatar = "";
			//if(!empty($comment->owner->email)) {
			//	$hash = md5(strtolower($comment->owner->email));
			//	$avatar = "<img src=\"http://www.gravatar.com/avatar/$hash.jpg\"><br>";
			//}
			$oe = ($this->comments_shown++ % 2 == 0) ? "even" : "odd";
			return "
				<a name='$i_comment_id'></a>
				<div class='$oe comment'>
				$h_userlink: $h_comment
				$h_dellink
				</div>
			";
		}
	}

	protected function build_postbox($image_id) {
		global $config;

		$i_image_id = int_escape($image_id);
		$hash = CommentList::get_hash();
		$captcha = $config->get_bool("comment_captcha") ? captcha_get_html() : "";

		return "
			<form name='comment_form' action='".make_link("comment/add")."' method='POST'>
				<input type='hidden' name='image_id' value='$i_image_id' />
				<input type='hidden' name='hash' value='$hash' />
				<textarea name='comment' rows='5' cols='50'></textarea>
				$captcha
				<br><input type='submit' value='Post Comment' />
			</form>
		";
	}
}
?>
