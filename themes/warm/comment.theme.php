<?php

class CustomCommentListTheme extends CommentListTheme {
	protected function comment_to_html($comment, $trim=false) {
		return $this->box(parent::comment_to_html($comment, $trim));
	}

	protected function build_postbox($image_id) {
		return $this->box(parent::build_postbox($image_id));
	}
}
?>
