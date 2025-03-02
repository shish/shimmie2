<?php

declare(strict_types=1);

namespace Shimmie2;

class InstallerException extends \RuntimeException
{
    public function __construct(
        public string $title,
        public string $body,
        public int $exit_code
    ) {
        parent::__construct($body);
    }
}
