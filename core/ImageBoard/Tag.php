<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * Class Tag
 *
 * A class for organising the tag related functions.
 *
 * All the methods are static, one should never actually use a tag object.
 */
final class Tag
{
    /** @var array<tag-string, int> */
    private static array $tag_id_cache = [];

    /**
     * @param tag-string $tag
     */
    public static function get_or_create_id(string $tag): int
    {
        // don't cache in unit tests, because the test suite doesn't
        // reset static variables but it does reset the database
        if (!defined("UNITTEST") && array_key_exists($tag, self::$tag_id_cache)) {
            return self::$tag_id_cache[$tag];
        }

        $id = Ctx::$database->get_one(
            "SELECT id FROM tags WHERE LOWER(tag) = LOWER(:tag)",
            ["tag" => $tag]
        );
        if (empty($id)) {
            // a new tag
            Ctx::$database->execute(
                "INSERT INTO tags(tag) VALUES (:tag)",
                ["tag" => $tag]
            );
            $id = Ctx::$database->get_one(
                "SELECT id FROM tags WHERE LOWER(tag) = LOWER(:tag)",
                ["tag" => $tag]
            );
        }

        self::$tag_id_cache[$tag] = $id;
        return $id;
    }

    /**
     * @param tag-string[] $tags
     */
    public static function implode(array $tags): string
    {
        sort($tags, SORT_FLAG_CASE | SORT_STRING);
        return implode(' ', $tags);
    }

    /**
     * Turn a human-supplied string into a valid tag array.
     *
     * @return tag-array
     */
    public static function explode(string $tags, bool $tagme = true): array
    {
        $tags = explode(' ', trim($tags));

        /* sanitise by removing invisible / dodgy characters */
        $tags_to_process = self::sanitize_array($tags);

        /* if user supplied a blank string, add "tagme" */
        if (count($tags_to_process) === 0 && $tagme) {
            $tags_to_process = ["tagme"];
        }

        /* resolve aliases */
        $processed_tags = [];
        $i = 0;
        $tag_count = count($tags_to_process);
        while ($i < $tag_count) {
            $tag = $tags_to_process[$i];
            $negative = '';
            if (!empty($tag) && ($tag[0] === '-')) {
                $negative = '-';
                $tag = substr($tag, 1);
            }

            $newtags = Ctx::$database->get_one(
                "
					SELECT newtag
					FROM aliases
					WHERE LOWER(oldtag)=LOWER(:tag)
				",
                ["tag" => $tag]
            );
            if (empty($newtags)) {
                //tag has no alias, use old tag
                $aliases = [$tag];
            } else {
                $aliases = explode(" ", $newtags); // Tag::explode($newtags); - recursion can be infinite
            }

            foreach ($aliases as $alias) {
                if (!in_array($alias, $processed_tags)) {
                    if ($tag === $alias) {
                        $processed_tags[] = $negative.$alias;
                    } elseif (!in_array($alias, $tags_to_process)) {
                        $tags_to_process[] = $negative.$alias;
                        $tag_count++;
                    }
                }
            }
            $i++;
        }

        /* remove any duplicate tags */
        $processed_tags = array_iunique($processed_tags);
        sort($processed_tags);
        $processed_tags = array_filter($processed_tags, fn ($t) => !empty($t));
        $processed_tags = array_values($processed_tags);

        return $processed_tags;
    }

    public static function sanitize(string $tag): string
    {
        $tag = \Safe\preg_replace("/\s/", "", $tag);                # whitespace
        $tag = \Safe\preg_replace('/\x20[\x0e\x0f]/', '', $tag);    # unicode RTL
        $tag = \Safe\preg_replace("/\.+/", ".", $tag);              # strings of dots?
        $tag = \Safe\preg_replace("/^(\.+[\/\\\\])+/", "", $tag);   # trailing slashes?
        $tag = trim($tag, ", \t\n\r\0\x0B");

        if ($tag === ".") {
            $tag = "";
        }  // hard-code one bad case...

        return $tag;
    }

    /**
     * @param string[] $tags1
     * @param string[] $tags2
     */
    public static function compare(array $tags1, array $tags2): bool
    {
        if (count($tags1) !== count($tags2)) {
            return false;
        }

        $tags1 = array_map(strtolower(...), $tags1);
        $tags2 = array_map(strtolower(...), $tags2);
        sort($tags1);
        sort($tags2);

        return $tags1 === $tags2;
    }

    /**
     * @param string[] $source
     * @param string[] $remove
     * @return string[]
     */
    public static function get_diff_tags(array $source, array $remove): array
    {
        $before = array_map(strtolower(...), $source);
        $remove = array_map(strtolower(...), $remove);
        $after = [];
        foreach ($before as $tag) {
            if (!in_array($tag, $remove)) {
                $after[] = $tag;
            }
        }
        return $after;
    }

    /**
     * @param string[] $tags
     * @return list<tag-string>
     */
    public static function sanitize_array(array $tags): array
    {
        $tag_array = [];
        foreach ($tags as $tag) {
            try {
                $tag = Tag::sanitize($tag);
            } catch (UserError $e) {
                Ctx::$page->flash($e->getMessage());
                continue;
            }

            if (!empty($tag)) {
                $tag_array[] = $tag;
            }
        }
        return $tag_array;
    }

    /**
     * @param search-term-string $term
     * @return string
     */
    public static function sqlify(string $term): string
    {
        if (Ctx::$database->get_driver_id() === DatabaseDriverID::SQLITE) {
            $term = str_replace('\\', '\\\\', $term);
        }
        $term = str_replace('_', '\_', $term);
        $term = str_replace('%', '\%', $term);
        $term = str_replace('*', '%', $term);
        // $term = str_replace("?", "_", $term);
        return $term;
    }
}
