<?php

declare(strict_types=1);

namespace Shimmie2;

class Cookie
{
    public function __construct(
        public string $name,
        public string $value,
        public int $time,
        public string $path
    ) {
    }
}
