<?php

class CustomCommentListTheme extends CommentListTheme {
	/**
	 * @param Comment $comment
	 * @param bool $trim
	 * @return string
	 */
	protected function comment_to_html(Comment $comment, $trim=false) {
		return $this->rr(parent::comment_to_html($comment, $trim));
	}

	/**
	 * @param int $image_id
	 * @return string
	 */
	protected function build_postbox($image_id) {
		return $this->rr(parent::build_postbox($image_id));
	}
}
