<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

final class BuildAvatarEvent extends Event
{
    public User $user;
    public ?HTMLElement $html = null;

    public function __construct(User $user)
    {
        parent::__construct();
        $this->user = $user;
    }

    public function setAvatar(HTMLElement $html): void
    {
        $this->html = $html;
    }
}
