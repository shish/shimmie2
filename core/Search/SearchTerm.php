<?php

declare(strict_types=1);

namespace Shimmie2;

final class SearchTerm
{
    /**
     * For now SearchTerm::explode() and Tag::explode() are the same,
     * but keeping them separate because they are conceptually different, eg
     * a tag-array must be unordered and unique, while a search-term-array may
     * be ordered and may contain duplicates.
     *
     * @return search-term-array
     */
    public static function explode(string $str): array
    {
        return Tag::explode($str);
    }

    /**
     * @param search-term-array $terms
     */
    public static function implode(array $terms): string
    {
        return Tag::implode($terms);
    }
}
