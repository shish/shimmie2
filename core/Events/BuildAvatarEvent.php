<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

final class BuildAvatarEvent extends Event
{
    public ?HTMLElement $html = null;

    public function __construct(
        public readonly User $user,
    ) {
        parent::__construct();
    }

    public function setAvatar(HTMLElement $html): void
    {
        $this->html = $html;
    }
}
