<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

final class BuildCaptchaEvent extends Event
{
    public ?HTMLElement $html = null;

    public function setCaptcha(HTMLElement $html): void
    {
        $this->html = $html;
    }
}
