<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\DIV;

class LiteCommentListTheme extends CommentListTheme
{
    protected function comment_to_html(Comment $comment, bool $trim = false): HTMLElement
    {
        return DIV(["class" => "tframe"], parent::comment_to_html($comment, $trim));
    }

    protected function build_postbox(int $image_id): HTMLElement
    {
        return DIV(["class" => "tframe"], parent::build_postbox($image_id));
    }
}
