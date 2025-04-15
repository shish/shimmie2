<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * Common functions for Page and Redirect responses
 */
trait WithFlash
{
    /** @var string[] */
    public array $flash = [];

    public function flash(string $message): void
    {
        $this->flash[] = $message;
    }
}
