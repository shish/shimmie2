<?php

/**
 * @template T
 * @param T|false $x
 * @return T
 */
function false_throws(mixed $x, ?callable $errorgen = null): mixed
{
    if ($x === false) {
        $msg = "Unexpected false";
        if ($errorgen) {
            $msg = $errorgen();
        }
        throw new \Exception($msg);
    }
    return $x;
}

function dir_ex(string $directory): \Directory
{
    return false_throws(dir($directory));
}

function preg_replace_ex(string $pattern, string $replacement, string $subject, int $limit = -1): string
{
    $res = preg_replace($pattern, $replacement, $subject, $limit);
    if (is_null($res)) {
        throw new \Exception("preg_replace failed");
    }
    return $res;
}
