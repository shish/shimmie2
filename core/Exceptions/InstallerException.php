<?php

declare(strict_types=1);

namespace Shimmie2;

class InstallerException extends \RuntimeException
{
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly int $exit_code
    ) {
        parent::__construct($body);
    }
}
