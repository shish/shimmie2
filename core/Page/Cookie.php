<?php

declare(strict_types=1);

namespace Shimmie2;

final readonly class Cookie
{
    public function __construct(
        public string $name,
        public string $value,
        public int $time,
        public string $path
    ) {
    }
}
