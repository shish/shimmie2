<?php

declare(strict_types=1);

namespace Shimmie2;

final class CommentPostingEvent extends Event
{
    public int $id;
    public function __construct(
        public int $image_id,
        public User $user,
        public string $comment
    ) {
        parent::__construct();
    }
}

final class CommentEditingEvent extends Event
{
    public function __construct(
        public int $image_id,
        public int $comment_id,
        public User $user,
        public string $comment
    ) {
        parent::__construct();
    }
}

/**
 * A comment is being deleted. Maybe used by spam
 * detectors to get a feel for what should be deleted
 * and what should be kept?
 */
final class CommentDeletionEvent extends Event
{
    public function __construct(
        public int $comment_id
    ) {
        parent::__construct();
    }
}

final class CommentPostingException extends InvalidInput
{
}

/**
 * Comment lock status is being changed on an image
 */
final class CommentLockSetEvent extends Event
{
    public function __construct(
        public int $image_id,
        public bool $locked
    ) {
        parent::__construct();
    }
}
