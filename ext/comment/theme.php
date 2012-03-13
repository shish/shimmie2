<?php
class CommentListTheme extends Themelet {
	var $comments_shown = 0;
	var $show_anon_id = false;
	var $anon_id = 1;
	var $anon_cid = 0;
	var $anon_map = array();
	var $ct = null;

	private function get_anon_colour($ip) {
		if(is_null($this->ct)) {
			$this->ct = hsl_rainbow();
		}
		if(!array_key_exists($ip, $this->anon_map)) {
			$this->anon_map[$ip] = $this->ct[$this->anon_cid++ % count($this->ct)];
		}
		return $this->anon_map[$ip];
	}

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
			'<a href="'.make_link('comment/list/'.$prev).'">Prev</a>';
		$h_index = "<a href='".make_link("comment/list")."'>Index</a>";
		$h_next = ($page_number >= $total_pages) ? "Next" :
			'<a href="'.make_link('comment/list/'.$next).'">Next</a>';

		$nav = $h_prev.' | '.$h_index.' | '.$h_next;

		$page->set_title("Comments");
		$page->set_heading("Comments");
		$page->add_block(new Block("Navigation", $nav, "left"));
		$this->display_paginator($page, "comment/list", null, $page_number, $total_pages);

		// parts for each image
		$position = 10;

		$comment_limit = $config->get_int("comment_list_count", 10);
		$comment_captcha = $config->get_bool('comment_captcha');
		
		foreach($images as $pair) {
			$image = $pair[0];
			$comments = $pair[1];

			$thumb_html = $this->build_thumb_html($image);
			$comment_html = "";
			
			$comment_count = count($comments);
			if($comment_limit > 0 && $comment_count > $comment_limit) {
				$hidden = $comment_count - $comment_limit;
				$comment_html .= '<p>showing '.$comment_limit.' of '.$comment_count.' comments</p>';
				$comments = array_slice($comments, -$comment_limit);
				$this->show_anon_id = false;
			}
			else {
				$this->show_anon_id = true;
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
					if(!$comment_captcha) {
						$comment_html .= $this->build_postbox($image->id);
					}
					else {
						$comment_html .= "<a href='".make_link("post/view/".$image->id)."'>Add Comment</a>";
					}
				}
			}

			$html  = '
				<table class="comment_list_table"><tr>
					<td width="220">'.$thumb_html.'</td>
					<td>'.$comment_html.'</td>
				</tr></table>
			';

			$page->add_block(new Block( $image->id.': '.$image->get_tag_list(), $html, "main", $position++, "comment-list"));
		}
	}


	/**
	 * Add some comments to the page, probably in a sidebar
	 *
	 * $comments = an array of Comment objects to be shown
	 */
	public function display_recent_comments($comments) {
		global $page;
		$this->show_anon_id = false;
		$html = "";
		foreach($comments as $comment) {
			$html .= $this->comment_to_html($comment, true);
		}
		$html .= "<a class='more' href='".make_link("comment/list")."'>Full List</a>";
		$page->add_block(new Block("Comments", $html, "left", 50, "comment-list"));
	}


	/**
	 * Show comments for an image
	 */
	public function display_image_comments(Image $image, $comments, $postbox) {
		global $page;
		$this->show_anon_id = true;
		$html = "";
		foreach($comments as $comment) {
			$html .= $this->comment_to_html($comment);
		}
		if($postbox) {
			$html .= $this->build_postbox($image->id);
		}
		$page->add_block(new Block("Comments", $html, "main", 30, "comment-list"));
	}


	/**
	 * Show comments made by a user
	 */
	public function display_user_comments($comments) {
		global $page;
		$html = "";
		foreach($comments as $comment) {
			$html .= $this->comment_to_html($comment, true);
		}
		if(empty($html)) {
			$html = '<p>No comments by this user.</p>';
		}
		$page->add_block(new Block("Comments", $html, "left", 70, "comment-list"));
	}


	protected function comment_to_html($comment, $trim=false) {
		global $config, $user;

		$tfe = new TextFormattingEvent($comment->comment);
		send_event($tfe);

		$i_uid = int_escape($comment->owner_id);
		$h_name = html_escape($comment->owner_name);
		$h_poster_ip = html_escape($comment->poster_ip);
		$h_timestamp = autodate($comment->posted);
		$h_comment = ($trim ? truncate($tfe->stripped, 50) : $tfe->formatted);
		$i_comment_id = int_escape($comment->comment_id);
		$i_image_id = int_escape($comment->image_id);

		if($i_uid == $config->get_int("anon_id")) {
			$anoncode = "";
			$anoncode2 = "";
			if($this->show_anon_id) {
				$anoncode = '<sup>'.$this->anon_id.'</sup>';
				if($user->can("view_ip")) {
					#$style = " style='color: ".$this->get_anon_colour($comment->poster_ip).";'";
					if(!array_key_exists($comment->poster_ip, $this->anon_map)) {
						$this->anon_map[$comment->poster_ip] = $this->anon_id;
					}
					if($this->anon_map[$comment->poster_ip] != $this->anon_id) {
						$anoncode2 = '<sup>('.$this->anon_map[$comment->poster_ip].')</sup>';
					}
				}
			}
			$h_userlink = "<span class='username'>" . $h_name . $anoncode . $anoncode2 . "</span>";
			$this->anon_id++;
		}
		else {
			$h_userlink = '<a class="username" href="'.make_link('user/'.$h_name).'">'.$h_name.'</a>';
		}
		$stripped_nonl = str_replace("\n", "\\n", substr($tfe->stripped, 0, 50));
		$stripped_nonl = str_replace("\r", "\\r", $stripped_nonl);

		if($trim) {
			return '
			<div class="comment">
				'.$h_userlink.': '.$h_comment.'
				<a href="'.make_link('post/view/'.$i_image_id.'#c'.$i_comment_id).'">&gt;&gt;&gt;</a>
			</div>
			';
		}
		else {
			$h_avatar = "";
			if(!empty($comment->owner_email)) {
				$hash = md5(strtolower($comment->owner_email));
				$h_avatar = "<img src=\"http://www.gravatar.com/avatar/$hash.jpg\"><br>";
			}
			$h_reply = " - <a href='javascript: replyTo($i_image_id, $i_comment_id)'>Reply</a>";
			$h_ip = $user->can("view_ip") ? "<br>".show_ip($comment->poster_ip, "Comment posted {$comment->posted}") : "";
			$h_del = $user->can("delete_comment") ?
				' - <a onclick="return confirm(\'Delete comment by '.$h_name.':\\n'.$stripped_nonl.'\');" '.
				'href="'.make_link('comment/delete/'.$i_comment_id.'/'.$i_image_id).'">Del</a>' : '';
			return '
				<div class="comment" id="c'.$i_comment_id.'">
					<div class="info">
					'.$h_avatar.'
					'.$h_timestamp.$h_reply.$h_ip.$h_del.'
					</div>
					'.$h_userlink.': '.$h_comment.'
				</div>
			';
		}
		return "";
	}

	protected function build_postbox(/*int*/ $image_id) {
		global $config;

		$i_image_id = int_escape($image_id);
		$hash = CommentList::get_hash();
		$h_captcha = $config->get_bool("comment_captcha") ? captcha_get_html() : "";

		return '
		<div class="comment">
			'.make_form(make_link("comment/add")).'
				<input type="hidden" name="image_id" value="'.$i_image_id.'" />
				<input type="hidden" name="hash" value="'.$hash.'" />
				<textarea id="comment_on_'.$i_image_id.'" name="comment" rows="5" cols="50"></textarea>
				'.$h_captcha.'
				<br><input type="submit" value="Post Comment" />
			</form>
		</div>
		';
	}
}
?>
