<?php

declare(strict_types=1);

namespace Shimmie2;

abstract class IPAddress
{
    public static function parse(string $addr): IPAddress
    {
        if (filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return new IPAddressV6($addr);
        } elseif (filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return new IPAddressV4($addr);
        } else {
            throw new \InvalidArgumentException("Invalid IP address: {$addr}");
        }
    }

    abstract public function is_localhost(): bool;

    abstract public function __toString(): string;
}
