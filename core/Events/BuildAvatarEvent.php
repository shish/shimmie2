<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

class BuildAvatarEvent extends Event
{
    public User $user;
    public HTMLElement|null $html;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->html = null;
    }

    public function setAvatar(HTMLElement $html): void
    {
        $this->html = $html;
    }
}
