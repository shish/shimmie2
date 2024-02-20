<?php

/**
 * @template T
 * @param T|false $x
 * @return T
 */
function false_throws(mixed $x, ?callable $errorgen = null): mixed
{
    if($x === false) {
        $msg = "Unexpected false";
        if($errorgen) {
            $msg = $errorgen();
        }
        throw new \Exception($msg);
    }
    return $x;
}

# https://github.com/thecodingmachine/safe/pull/428
function inet_pton_ex(string $ip_address): string
{
    return false_throws(inet_pton($ip_address));
}

function dir_ex(string $directory): \Directory
{
    return false_throws(dir($directory));
}

function filter_var_ex(mixed $variable, int $filter = FILTER_DEFAULT, mixed $options = null): mixed
{
    return false_throws(filter_var($variable, $filter, $options));
}
