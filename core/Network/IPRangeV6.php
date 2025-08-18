<?php

declare(strict_types=1);

namespace Shimmie2;

final class IPRangeV6 extends IPRange
{
    public function __construct(private string $cidr)
    {
        if (str_contains($cidr, "/")) {
            $parts = explode("/", $cidr);
            if (count($parts) !== 2) {
                throw new \InvalidArgumentException("Invalid CIDR notation: {$cidr}");
            }
            $this->ip = new IPAddressV6($parts[0]);
            $this->mask = (int)$parts[1];
        } else {
            $this->ip = new IPAddressV6($cidr);
            $this->mask = 128;
        }
        if ($this->mask < 0 || $this->mask > 128) {
            throw new \InvalidArgumentException("Invalid mask length: {$this->mask}");
        }
        if ($this->mask % 4 !== 0) {
            throw new \InvalidArgumentException("Mask length must be a multiple of 4: {$this->mask}");
        }
    }

    public function contains(IPAddress $ip): bool
    {
        if (!is_a($ip, IPAddressV6::class)) {
            return false;
        }

        $ip_ip = \Safe\inet_pton((string)$ip);
        $ip_net = \Safe\inet_pton((string)$this->ip);
        $ip_mask = str_repeat("f", $this->mask / 4) . str_repeat("0", (128 - $this->mask) / 4);

        return ($ip_ip & $ip_mask) === ($ip_net & $ip_mask);
    }

    public function __toString(): string
    {
        return $this->cidr;
    }
}
