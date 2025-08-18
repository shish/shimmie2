<?php

declare(strict_types=1);

namespace Shimmie2;

final class IPRangeV4 extends IPRange
{
    public function __construct(private string $cidr)
    {
        if (str_contains($cidr, "/")) {
            $parts = explode("/", $cidr);
            if (count($parts) !== 2) {
                throw new \InvalidArgumentException("Invalid CIDR notation: {$cidr}");
            }
            $this->ip = new IPAddressV4($parts[0]);
            $this->mask = (int)$parts[1];
        } else {
            $this->ip = new IPAddressV4($cidr);
            $this->mask = 32;
        }
    }

    public function contains(IPAddress $ip): bool
    {
        if (!is_a($ip, IPAddressV4::class)) {
            return false;
        }

        $ip_ip = ip2long((string)$ip);
        $ip_net = ip2long((string)$this->ip);
        $ip_mask = ~((1 << (32 - $this->mask)) - 1);

        return ($ip_ip & $ip_mask) === ($ip_net & $ip_mask);
    }

    public function __toString(): string
    {
        return $this->cidr;
    }
}
