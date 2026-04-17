<?php

declare(strict_types=1);

namespace Shimmie2;

final readonly class Cookie
{
    /**
     * @param string $name Cookie name
     * @param string $value Cookie value
     * @param int $time Expiration time (Unix timestamp)
     * @param string $path Cookie path
     * @param bool $secure Send only over HTTPS
     * @param bool $httponly Prevent JavaScript access
     * @param 'Strict'|'Lax'|'None' $samesite SameSite attribute
     */
    public function __construct(
        public string $name,
        public string $value,
        public int $time,
        public string $path,
        public bool $secure = true,
        public bool $httponly = true,
        public string $samesite = 'Lax',
    ) {
    }
}
