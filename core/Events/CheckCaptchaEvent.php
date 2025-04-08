<?php

declare(strict_types=1);

namespace Shimmie2;

final class CheckCaptchaEvent extends Event
{
    public ?bool $passed = null;
}
