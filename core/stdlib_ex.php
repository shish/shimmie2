<?php

/**
 * @template T
 * @param T|false $x
 * @return T
 */
function false_throws(mixed $x): mixed
{
    if($x === false) {
        throw new \Exception("Unexpected false");
    }
    return $x;
}

/**
 * @template T
 * @param T|null $x
 * @return T
 */
function null_throws(mixed $x): mixed
{
    if($x === null) {
        throw new \Exception("Unexpected null");
    }
    return $x;
}

/**
 * @param int<1,max> $depth
 */
function json_encode_ex(mixed $value, int|null $flags = 0, int $depth = 512): string
{
    return false_throws(json_encode($value, $flags, $depth));
}

function strtotime_ex(string $time, int|null $now = null): int
{
    return false_throws(strtotime($time, $now));
}

function md5_file_ex(string $filename, bool|null $raw_output = false): string
{
    return false_throws(md5_file($filename, $raw_output));
}

/**
 * @return string[]
 */
function glob_ex(string $pattern, int|null $flags = 0): array
{
    return false_throws(glob($pattern, $flags));
}

function file_get_contents_ex(string $filename): string
{
    return false_throws(file_get_contents($filename));
}

function filesize_ex(string $filename): int
{
    return false_throws(filesize($filename));
}

function inet_ntop_ex(string $in_addr): string
{
    return false_throws(inet_ntop($in_addr));
}

function inet_pton_ex(string $ip_address): string
{
    return false_throws(inet_pton($ip_address));
}

function dir_ex(string $directory): \Directory
{
    return false_throws(dir($directory));
}

function exec_ex(string $command): string
{
    return false_throws(exec($command));
}

function filter_var_ex(mixed $variable, int $filter = FILTER_DEFAULT, mixed $options = null): mixed
{
    return false_throws(filter_var($variable, $filter, $options));
}
