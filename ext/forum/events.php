<?php

declare(strict_types=1);

namespace Shimmie2;

final class ForumThreadPostingEvent extends Event
{
    public int $id;
    public function __construct(
        public User $user,
        public string $title,
        public bool $sticky
    ) {
        parent::__construct();
    }
}

final class ForumThreadDeletionEvent extends Event
{
    public function __construct(
        public int $thread_id
    ) {
        parent::__construct();
    }
}

final class ForumPostPostingEvent extends Event
{
    public function __construct(
        public User $user,
        public int $thread_id,
        public string $message
    ) {
        parent::__construct();
    }
}

final class ForumPostDeletionEvent extends Event
{
    public function __construct(
        public int $thread_id,
        public int $post_id
    ) {
        parent::__construct();
    }
}

final class ForumPostingException extends InvalidInput
{
}
