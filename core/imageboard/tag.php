<?php
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
    public static function implode(array $tags): string
    {
        sort($tags);
        $tags = implode(' ', $tags);

        return $tags;
    }

    /**
     * Turn a human-supplied string into a valid tag array.
     *
     * #return string[]
     */
    public static function explode(string $tags, bool $tagme=true): array
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
        while ($i<$tag_count) {
            $tag = $tag_array[$i];
            $negative = '';
            if (!empty($tag) && ($tag[0] == '-')) {
                $negative = '-';
                $tag = substr($tag, 1);
            }

            $newtags = $database->get_one(
                $database->scoreql_to_sql("
					SELECT newtag
					FROM aliases
					WHERE SCORE_STRNORM(oldtag)=SCORE_STRNORM(:tag)
				"),
                ["tag"=>$tag]
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
        $tag = preg_replace('/\x20(\x0e|\x0f)/', '', $tag);   # unicode RTL
        $tag = preg_replace("/\.+/", ".", $tag);              # strings of dots?
        $tag = preg_replace("/^(\.+[\/\\\\])+/", "", $tag);   # trailing slashes?
        $tag = trim($tag, ", \t\n\r\0\x0B");

        if (mb_strlen($tag, 'UTF-8') > 255) {
            throw new Exception("The tag below is longer than 255 characters, please use a shorter tag.\n$tag\n");
        }
        return $tag;
    }

    public static function compare(array $tags1, array $tags2): bool
    {
        if(count($tags1)!==count($tags2)) {
            return false;
        }

        $tags1 = array_map("strtolower",$tags1);
        $tags2 = array_map("strtolower",$tags2);
        natcasesort($tags1);
        natcasesort($tags2);


        for($i = 0; $i < count($tags1); $i++) {
            if($tags1[$i]!==$tags2[$i]) {
                var_dump($tags1);
                var_dump($tags2);
                return false;
            }
        }
        return true;
    }

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

    public static function sanitize_array(array $tags): array
    {
        $tag_array = [];
        foreach ($tags as $tag) {
            try {
                $tag = Tag::sanitize($tag);
            } catch(Exception $e) {
                flash_message($e->getMessage());
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
        if ($database->get_driver_name() === DatabaseDriver::SQLITE) {
            $term = str_replace('\\', '\\\\', $term);
        }
        $term = str_replace('_', '\_', $term);
        $term = str_replace('%', '\%', $term);
        $term = str_replace('*', '%', $term);
        $term = str_replace("?", "_", $term);
        return $term;
    }
}
