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
        $loopback_range = new IPRangeV4('127.0.0.0/8');
        return $loopback_range->contains($this);
    }

    public function is_private(): bool
    {
        return !filter_var(
            $this->addr,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    public function __toString(): string
    {
        return $this->addr;
    }
}
