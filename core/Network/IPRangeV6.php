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
    }

    public function contains(IPAddress $ip): bool
    {
        if (!is_a($ip, IPAddressV6::class)) {
            return false;
        }

        $ip_ip = \Safe\inet_pton((string)$ip);
        $ip_net = \Safe\inet_pton((string)$this->ip);

        // Create a binary mask for any number of bits (0-128)
        $mask_binary = "";
        for ($byte = 0; $byte < 16; $byte++) {
            $bits_in_this_byte = min(8, max(0, $this->mask - $byte * 8));
            $mask_binary .= chr((0xFF << (8 - $bits_in_this_byte)) & 0xFF);
        }

        return ($ip_ip & $mask_binary) === ($ip_net & $mask_binary);
    }

    public function __toString(): string
    {
        return $this->cidr;
    }
}
