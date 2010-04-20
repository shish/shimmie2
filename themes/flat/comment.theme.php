<?php

class CustomCommentListTheme extends CommentListTheme {
	protected function comment_to_html($comment, $trim=false) {
		return $this->rr(parent::comment_to_html($comment, $trim));
	}

	protected function build_postbox($image_id) {
		return $this->rr(parent::build_postbox($image_id));
	}
}
?>
