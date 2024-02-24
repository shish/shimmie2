<?php

declare(strict_types=1);

namespace Shimmie2;

use GQLA\Type;
use GQLA\Field;
use GQLA\Query;

#[Type(name: "TagUsage")]
class TagUsage
{
    #[Field]
    public string $tag;
    #[Field]
    public int $uses;

    public function __construct(string $tag, int $uses)
    {
        $this->tag = $tag;
        $this->uses = $uses;
    }

    /**
     * @return TagUsage[]
     */
    #[Query(name: "tags", type: '[TagUsage!]!')]
    public static function tags(string $search, int $limit = 10): array
    {
        global $cache, $database;

        $search = strtolower($search);
        if (
            $search == '' ||
            $search[0] == '_' ||
            $search[0] == '%' ||
            strlen($search) > 32
        ) {
            return [];
        }

        $cache_key = "tagusage-$search";
        $limitSQL = "";
        $search = str_replace('_', '\_', $search);
        $search = str_replace('%', '\%', $search);
        $SQLarr = ["search" => "$search%"]; #, "cat_search"=>"%:$search%"];
        if ($limit !== 0) {
            $limitSQL = "LIMIT :limit";
            $SQLarr['limit'] = $limit;
            $cache_key .= "-" . $limit;
        }

        $res = cache_get_or_set(
            $cache_key,
            fn () => $database->get_pairs(
                "
                SELECT tag, count
                FROM tags
                WHERE LOWER(tag) LIKE LOWER(:search)
                -- OR LOWER(tag) LIKE LOWER(:cat_search)
                AND count > 0
                ORDER BY count DESC
                $limitSQL
                ",
                $SQLarr
            ),
            600
        );

        $counts = [];
        foreach ($res as $k => $v) {
            $counts[] = new TagUsage($k, $v);
        }
        return $counts;
    }
}

/**
 * Class Tag
 *
 * A class for organising the tag related functions.
 *
 * All the methods are static, one should never actually use a tag object.
 *
 */
class Tag
{
    /** @var array<string, int> */
    private static array $tag_id_cache = [];

    public static function get_or_create_id(string $tag): int
    {
        global $database;

        // don't cache in unit tests, because the test suite doesn't
        // reset static variables but it does reset the database
        if (!defined("UNITTEST") && array_key_exists($tag, self::$tag_id_cache)) {
            return self::$tag_id_cache[$tag];
        }

        $id = $database->get_one(
            "SELECT id FROM tags WHERE LOWER(tag) = LOWER(:tag)",
            ["tag" => $tag]
        );
        if (empty($id)) {
            // a new tag
            $database->execute(
                "INSERT INTO tags(tag) VALUES (:tag)",
                ["tag" => $tag]
            );
            $id = $database->get_one(
                "SELECT id FROM tags WHERE LOWER(tag) = LOWER(:tag)",
                ["tag" => $tag]
            );
        }

        self::$tag_id_cache[$tag] = $id;
        return $id;
    }

    /** @param string[] $tags */
    public static function implode(array $tags): string
    {
        sort($tags, SORT_FLAG_CASE | SORT_STRING);
        return implode(' ', $tags);
    }

    /**
     * Turn a human-supplied string into a valid tag array.
     *
     * @return string[]
     */
    public static function explode(string $tags, bool $tagme = true): array
    {
        global $database;

        $tags = explode(' ', trim($tags));

        /* sanitise by removing invisible / dodgy characters */
        $tag_array = self::sanitize_array($tags);

        /* if user supplied a blank string, add "tagme" */
        if (count($tag_array) === 0 && $tagme) {
            $tag_array = ["tagme"];
        }

        /* resolve aliases */
        $new = [];
        $i = 0;
        $tag_count = count($tag_array);
        while ($i < $tag_count) {
            $tag = $tag_array[$i];
            $negative = '';
            if (!empty($tag) && ($tag[0] == '-')) {
                $negative = '-';
                $tag = substr($tag, 1);
            }

            $newtags = $database->get_one(
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
                if (!in_array($alias, $new)) {
                    if ($tag == $alias) {
                        $new[] = $negative.$alias;
                    } elseif (!in_array($alias, $tag_array)) {
                        $tag_array[] = $negative.$alias;
                        $tag_count++;
                    }
                }
            }
            $i++;
        }

        /* remove any duplicate tags */
        $tag_array = array_iunique($new);

        /* tidy up */
        sort($tag_array);

        return $tag_array;
    }

    public static function sanitize(string $tag): string
    {
        $tag = preg_replace("/\s/", "", $tag);                # whitespace
        assert($tag !== null);
        $tag = preg_replace('/\x20[\x0e\x0f]/', '', $tag);    # unicode RTL
        assert($tag !== null);
        $tag = preg_replace("/\.+/", ".", $tag);              # strings of dots?
        assert($tag !== null);
        $tag = preg_replace("/^(\.+[\/\\\\])+/", "", $tag);   # trailing slashes?
        assert($tag !== null);
        $tag = trim($tag, ", \t\n\r\0\x0B");

        if ($tag == ".") {
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

        $tags1 = array_map("strtolower", $tags1);
        $tags2 = array_map("strtolower", $tags2);
        sort($tags1);
        sort($tags2);

        return $tags1 == $tags2;
    }

    /**
     * @param string[] $source
     * @param string[] $remove
     * @return string[]
     */
    public static function get_diff_tags(array $source, array $remove): array
    {
        $before = array_map('strtolower', $source);
        $remove = array_map('strtolower', $remove);
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
     * @return string[]
     */
    public static function sanitize_array(array $tags): array
    {
        global $page;
        $tag_array = [];
        foreach ($tags as $tag) {
            try {
                $tag = Tag::sanitize($tag);
            } catch (UserError $e) {
                $page->flash($e->getMessage());
                continue;
            }

            if (!empty($tag)) {
                $tag_array[] = $tag;
            }
        }
        return $tag_array;
    }

    public static function sqlify(string $term): string
    {
        global $database;
        if ($database->get_driver_id() === DatabaseDriverID::SQLITE) {
            $term = str_replace('\\', '\\\\', $term);
        }
        $term = str_replace('_', '\_', $term);
        $term = str_replace('%', '\%', $term);
        $term = str_replace('*', '%', $term);
        // $term = str_replace("?", "_", $term);
        return $term;
    }
}
