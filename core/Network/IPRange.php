<?php

declare(strict_types=1);

namespace Shimmie2;

abstract class IPRange
{
    public IPAddress $ip;
    public int $mask;

    public static function parse(string $cidr): IPRange
    {
        if (str_contains($cidr, ":")) {
            return new IPRangeV6($cidr);
        } elseif (str_contains($cidr, ".")) {
            return new IPRangeV4($cidr);
        } else {
            throw new \InvalidArgumentException("Invalid CIDR notation: {$cidr}");
        }
    }

    abstract public function contains(IPAddress $ip): bool;
    abstract public function __toString(): string;
}
