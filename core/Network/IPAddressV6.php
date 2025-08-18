<?php

declare(strict_types=1);

namespace Shimmie2;

final class IPAddressV6 extends IPAddress
{
    public function __construct(private string $addr)
    {
    }

    public function is_localhost(): bool
    {
        return $this->addr === "::1" || $this->addr === "0:0:0:0:0:0:0:1";
    }

    public function __toString(): string
    {
        return $this->addr;
    }
}
