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
        // Private IPv4 ranges
        $private_ranges = [
            '127.0.0.0/8',      // Loopback
            '10.0.0.0/8',       // Private network
            '172.16.0.0/12',    // Private network
            '192.168.0.0/16',   // Private network
            '169.254.0.0/16',   // Link-local
            '224.0.0.0/4',      // Multicast
            '240.0.0.0/4',      // Reserved
            '0.0.0.0/8',        // Current network
            '255.255.255.255',  // Broadcast
        ];

        foreach ($private_ranges as $range) {
            $range_obj = new IPRangeV4($range);
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
