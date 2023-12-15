<?php

declare(strict_types=1);

namespace Shimmie2;

class CustomCommentListTheme extends CommentListTheme
{
    protected function comment_to_html(Comment $comment, bool $trim = false): string
    {
        $html = parent::comment_to_html($comment, $trim);
        return "<div class='tframe'>$html</div>";
    }

    protected function build_postbox(int $image_id): string
    {
        $html = parent::build_postbox($image_id);
        return "<div class='tframe'>$html</div>";
    }
}
