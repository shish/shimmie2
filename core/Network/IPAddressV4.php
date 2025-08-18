<?php

declare(strict_types=1);

namespace Shimmie2;

final class IPAddressV4 extends IPAddress
{
    public function __construct(private string $addr)
    {
    }

    public function is_localhost(): bool
    {
        return str_starts_with($this->addr, "127.");
    }

    public function __toString(): string
    {
        return $this->addr;
    }
}
