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
        $loopback_range = new IPRangeV6('::1/128');
        return $loopback_range->contains($this);
    }

    public function is_private(): bool
    {
        // IPv6 loopback and private ranges
        $private_ranges = [
            '::1/128',           // Loopback
            '::/128',            // Unspecified
            'fe80::/10',         // Link-local
            'fc00::/7',          // Unique local
            'fd00::/8',          // Unique local (alternative notation)
        ];

        foreach ($private_ranges as $range) {
            $range_obj = new IPRangeV6($range);
            if ($range_obj->contains($this)) {
                return true;
            }
        }

        return false;
    }

    public function __toString(): string
    {
        return $this->addr;
    }
}
