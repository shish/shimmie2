<?php

declare(strict_types=1);

namespace Shimmie2;

class CustomCommentListTheme extends CommentListTheme
{
    protected function comment_to_html(Comment $comment, bool $trim=false): string
    {
        return $this->rr(parent::comment_to_html($comment, $trim));
    }

    protected function build_postbox(int $image_id): string
    {
        return $this->rr(parent::build_postbox($image_id));
    }
}
