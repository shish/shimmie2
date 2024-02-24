<?php

declare(strict_types=1);

namespace Shimmie2;

use GQLA\Query;

/**
 * A small chunk of SQL code + parameters, to be used in a larger query
 *
 * eg
 *
 * $q = new Querylet("SELECT * FROM images");
 * $q->append(new Querylet(" WHERE id = :id", ["id" => 123]));
 * $q->append(new Querylet(" AND rating = :rating", ["rating" => "safe"]));
 * $q->append(new Querylet(" ORDER BY id DESC"));
 *
 * becomes
 *
 * SELECT * FROM images WHERE id = :id AND rating = :rating ORDER BY id DESC
 * ["id" => 123, "rating" => "safe"]
 */
class Querylet
{
    /**
     * @param string $sql
     * @param array<string, mixed> $variables
     */
    public function __construct(
        public string $sql,
        public array $variables = [],
    ) {
    }

    public function append(Querylet $querylet): void
    {
        $this->sql .= $querylet->sql;
        $this->variables = array_merge($this->variables, $querylet->variables);
    }
}

/**
 * When somebody has searched for a tag, "cat", "cute", "-angry", etc
 */
class TagCondition
{
    public function __construct(
        public string $tag,
        public bool $positive = true,
    ) {
    }
}

/**
 * When somebody has searched for a specific image property, like "rating:safe",
 * "id:123", "width:100", etc
 */
class ImgCondition
{
    public function __construct(
        public Querylet $qlet,
        public bool $positive = true,
    ) {
    }
}

class Search
{
    /**
     * The search code is dark and full of horrors, and it's not always clear
     * what's going on. This is a list of the steps that the search code took
     * to find the images that it returned.
     *
     * @var list<string>
     */
    public static array $_search_path = [];

    /**
     * Build a search query for a given set of tags and return
     * the results as a PDOStatement (raw SQL rows)
     *
     * @param list<string> $tags
     */
    private static function find_images_internal(int $start = 0, ?int $limit = null, array $tags = []): \FFSPHP\PDOStatement
    {
        global $database, $user;

        if ($start < 0) {
            $start = 0;
        }
        if ($limit !== null && $limit < 1) {
            $limit = 1;
        }

        if (SPEED_HAX) {
            if (!$user->can(Permissions::BIG_SEARCH) and count($tags) > 3) {
                throw new PermissionDenied("Anonymous users may only search for up to 3 tags at a time");
            }
        }

        [$tag_conditions, $img_conditions, $order] = self::terms_to_conditions($tags);
        $querylet = self::build_search_querylet($tag_conditions, $img_conditions, $order, $limit, $start);
        return $database->get_all_iterable($querylet->sql, $querylet->variables);
    }

    /**
     * Search for an array of images
     *
     * @param list<string> $tags
     * @return Image[]
     */
    #[Query(name: "posts", type: "[Post!]!", args: ["tags" => "[string!]"])]
    public static function find_images(int $offset = 0, ?int $limit = null, array $tags = []): array
    {
        $result = self::find_images_internal($offset, $limit, $tags);

        $images = [];
        foreach ($result as $row) {
            $images[] = new Image($row);
        }
        return $images;
    }

    /**
     * Search for an array of images, returning a iterable object of Image
     *
     * @param list<string> $tags
     * @return \Generator<Image>
     */
    public static function find_images_iterable(int $start = 0, ?int $limit = null, array $tags = []): \Generator
    {
        $result = self::find_images_internal($start, $limit, $tags);
        foreach ($result as $row) {
            yield new Image($row);
        }
    }

    /**
     * Get a specific set of images, in the order that the set specifies,
     * with all the search stuff (rating filters etc) taken into account
     *
     * @param int[] $ids
     * @return Image[]
     */
    public static function get_images(array $ids): array
    {
        $visible_images = [];
        foreach(Search::find_images(tags: ["id=" . implode(",", $ids)]) as $image) {
            $visible_images[$image->id] = $image;
        }
        $visible_ids = array_keys($visible_images);

        $visible_popular_ids = array_filter($ids, fn ($id) => in_array($id, $visible_ids));
        $images = array_map(fn ($id) => $visible_images[$id], $visible_popular_ids);
        return $images;
    }

    /*
     * Image-related utility functions
     */

    public static function count_tag(string $tag): int
    {
        global $database;
        return (int)$database->get_one(
            "SELECT count FROM tags WHERE LOWER(tag) = LOWER(:tag)",
            ["tag" => $tag]
        );
    }

    private static function count_total_images(): int
    {
        global $database;
        return cache_get_or_set("image-count", fn () => (int)$database->get_one("SELECT COUNT(*) FROM images"), 600);
    }

    /**
     * Count the number of image results for a given search
     *
     * @param list<string> $tags
     */
    public static function count_images(array $tags = []): int
    {
        global $cache, $database;
        $tag_count = count($tags);

        // SPEED_HAX ignores the fact that extensions can add img_conditions
        // even when there are no tags being searched for
        if (SPEED_HAX && $tag_count === 0) {
            // total number of images in the DB
            $total = self::count_total_images();
        } elseif (SPEED_HAX && $tag_count === 1 && !preg_match("/[:=><\*\?]/", $tags[0])) {
            if (!str_starts_with($tags[0], "-")) {
                // one positive tag - we can look that up directly
                $total = self::count_tag($tags[0]);
            } else {
                // one negative tag - subtract from the total
                $total = self::count_total_images() - self::count_tag(substr($tags[0], 1));
            }
        } else {
            // complex query
            // implode(tags) can be too long for memcache, so use the hash of tags as the key
            $cache_key = "image-count:" . md5(Tag::implode($tags));
            $total = $cache->get($cache_key);
            if (is_null($total)) {
                [$tag_conditions, $img_conditions, $order] = self::terms_to_conditions($tags);
                $querylet = self::build_search_querylet($tag_conditions, $img_conditions, null);
                $total = (int)$database->get_one("SELECT COUNT(*) AS cnt FROM ($querylet->sql) AS tbl", $querylet->variables);
                if (SPEED_HAX && $total > 5000) {
                    // when we have a ton of images, the count
                    // won't change dramatically very often
                    $cache->set($cache_key, $total, 3600);
                }
            }
        }
        return $total;
    }


    /**
     * @return list<int>
     */
    private static function tag_or_wildcard_to_ids(string $tag): array
    {
        global $database;
        $sq = "SELECT id FROM tags WHERE LOWER(tag) LIKE LOWER(:tag)";
        if ($database->get_driver_id() === DatabaseDriverID::SQLITE) {
            $sq .= "ESCAPE '\\'";
        }
        return $database->get_col($sq, ["tag" => Tag::sqlify($tag)]);
    }

    /**
     * Turn a human input string into a an abstract search query
     *
     * (This is only public for testing purposes, nobody should be calling this
     * directly from outside this class)
     *
     * @param string[] $terms
     * @return array{0: TagCondition[], 1: ImgCondition[], 2: string}
     */
    public static function terms_to_conditions(array $terms): array
    {
        global $config;

        $tag_conditions = [];
        $img_conditions = [];
        $order = null;

        /*
         * Turn a bunch of strings into a bunch of TagCondition
         * and ImgCondition objects
         */
        $stpen = 0;  // search term parse event number
        foreach (array_merge([null], $terms) as $term) {
            $stpe = send_event(new SearchTermParseEvent($stpen++, $term, $terms));
            $order ??= $stpe->order;
            $img_conditions = array_merge($img_conditions, $stpe->img_conditions);
            $tag_conditions = array_merge($tag_conditions, $stpe->tag_conditions);
        }

        $order = ($order ?: "images.".$config->get_string(IndexConfig::ORDER));

        return [$tag_conditions, $img_conditions, $order];
    }

    /**
     * Turn an abstract search query into an SQL Querylet
     *
     * (This is only public for testing purposes, nobody should be calling this
     * directly from outside this class)
     *
     * @param TagCondition[] $tag_conditions
     * @param ImgCondition[] $img_conditions
     */
    public static function build_search_querylet(
        array $tag_conditions,
        array $img_conditions,
        ?string $order = null,
        ?int $limit = null,
        ?int $offset = null
    ): Querylet {
        // no tags, do a simple search
        if (count($tag_conditions) === 0) {
            static::$_search_path[] = "no_tags";
            $query = new Querylet("SELECT images.* FROM images WHERE 1=1");
        }

        // one tag sorted by ID - we can fetch this from the image_tags table,
        // and do the offset / limit there, which is 10x faster than fetching
        // all the image_tags and doing the offset / limit on the result.
        elseif (
            count($tag_conditions) === 1
            && $tag_conditions[0]->positive
            // We can only do this if img_conditions is empty, because
            // we're going to apply the offset / limit to the image_tags
            // subquery, and applying extra conditions to the top-level
            // query might reduce the total results below the target limit
            && empty($img_conditions)
            // We can only do this if we're sorting by ID, because
            // we're going to be using the image_tags table, which
            // only has image_id and tag_id, not any other columns
            && ($order == "id DESC" || $order == "images.id DESC")
            // This is only an optimisation if we are applying limit
            // and offset
            && !is_null($limit)
            && !is_null($offset)
        ) {
            static::$_search_path[] = "fast";
            $tc = $tag_conditions[0];
            // IN (SELECT id FROM tags) is 100x slower than doing a separate
            // query and then a second query for IN(first_query_results)??
            $tag_array = self::tag_or_wildcard_to_ids($tc->tag);
            if (count($tag_array) == 0) {
                // if wildcard expanded to nothing, take a shortcut
                static::$_search_path[] = "invalid_tag";
                $query = new Querylet("SELECT images.* FROM images WHERE 1=0");
            } else {
                $set = implode(', ', $tag_array);
                $query = new Querylet("
                    SELECT images.*
                    FROM images INNER JOIN (
                        SELECT DISTINCT it.image_id
                        FROM image_tags it
                        WHERE it.tag_id IN ($set)
                        ORDER BY it.image_id DESC
                        LIMIT :limit OFFSET :offset
                    ) a on a.image_id = images.id
                    WHERE 1=1
                ", ["limit" => $limit, "offset" => $offset]);
                // don't offset at the image level because
                // we already offset at the image_tags level
                $limit = null;
                $offset = null;
            }
        }

        // more than one tag, or more than zero other conditions, or a non-default sort order
        else {
            static::$_search_path[] = "general";
            $positive_tag_id_array = [];
            $positive_wildcard_id_array = [];
            $negative_tag_id_array = [];
            $all_nonexistent_negatives = true;

            foreach ($tag_conditions as $tq) {
                $tag_ids = self::tag_or_wildcard_to_ids($tq->tag);
                $tag_count = count($tag_ids);

                if ($tq->positive) {
                    $all_nonexistent_negatives = false;
                    if ($tag_count == 0) {
                        # one of the positive tags had zero results, therefor there
                        # can be no results; "where 1=0" should shortcut things
                        static::$_search_path[] = "invalid_tag";
                        return new Querylet("SELECT images.* FROM images WHERE 1=0");
                    } elseif ($tag_count == 1) {
                        // All wildcard terms that qualify for a single tag can be treated the same as non-wildcards
                        $positive_tag_id_array[] = $tag_ids[0];
                    } else {
                        // Terms that resolve to multiple tags act as an OR within themselves
                        // and as an AND in relation to all other terms,
                        $positive_wildcard_id_array[] = $tag_ids;
                    }
                } else {
                    if ($tag_count > 0) {
                        $all_nonexistent_negatives = false;
                        // Unlike positive criteria, negative criteria are all handled in an OR fashion,
                        // so we can just compile them all into a single sub-query.
                        $negative_tag_id_array = array_merge($negative_tag_id_array, $tag_ids);
                    }
                }
            }

            assert($positive_tag_id_array || $positive_wildcard_id_array || $negative_tag_id_array || $all_nonexistent_negatives, _get_query());

            if ($all_nonexistent_negatives) {
                static::$_search_path[] = "all_nonexistent_negatives";
                $query = new Querylet("SELECT images.* FROM images WHERE 1=1");
            } elseif (!empty($positive_tag_id_array) || !empty($positive_wildcard_id_array)) {
                static::$_search_path[] = "some_positives";
                $inner_joins = [];
                if (!empty($positive_tag_id_array)) {
                    foreach ($positive_tag_id_array as $tag) {
                        $inner_joins[] = "= $tag";
                    }
                }
                if (!empty($positive_wildcard_id_array)) {
                    foreach ($positive_wildcard_id_array as $tags) {
                        $positive_tag_id_list = join(', ', $tags);
                        $inner_joins[] = "IN ($positive_tag_id_list)";
                    }
                }

                $first = array_shift($inner_joins);
                $sub_query = "SELECT DISTINCT it.image_id FROM image_tags it ";
                $i = 0;
                foreach ($inner_joins as $inner_join) {
                    $i++;
                    $sub_query .= " INNER JOIN image_tags it$i ON it$i.image_id = it.image_id AND it$i.tag_id $inner_join ";
                }
                if (!empty($negative_tag_id_array)) {
                    $negative_tag_id_list = join(', ', $negative_tag_id_array);
                    $sub_query .= " LEFT JOIN image_tags negative ON negative.image_id = it.image_id AND negative.tag_id IN ($negative_tag_id_list) ";
                }
                $sub_query .= "WHERE it.tag_id $first ";
                if (!empty($negative_tag_id_array)) {
                    $sub_query .= " AND negative.image_id IS NULL";
                }
                $sub_query .= " GROUP BY it.image_id ";

                $query = new Querylet("
                    SELECT images.*
                    FROM images
                    INNER JOIN ($sub_query) a on a.image_id = images.id
                ");
            } elseif (!empty($negative_tag_id_array)) {
                static::$_search_path[] = "only_negative_tags";
                $negative_tag_id_list = join(', ', $negative_tag_id_array);
                $query = new Querylet("
                    SELECT images.*
                    FROM images
                    LEFT JOIN image_tags negative ON negative.image_id = images.id AND negative.tag_id in ($negative_tag_id_list)
                    WHERE negative.image_id IS NULL
                ");
            } else {
                throw new InvalidInput("No criteria specified");
            }
        }

        /*
         * Merge all the image metadata searches into one generic querylet
         * and append to the base querylet with "AND blah"
         */
        if (!empty($img_conditions)) {
            $n = 0;
            $img_sql = "";
            $img_vars = [];
            foreach ($img_conditions as $iq) {
                if ($n++ > 0) {
                    $img_sql .= " AND";
                }
                if (!$iq->positive) {
                    $img_sql .= " NOT";
                }
                $img_sql .= " (" . $iq->qlet->sql . ")";
                $img_vars = array_merge($img_vars, $iq->qlet->variables);
            }
            $query->append(new Querylet(" AND "));
            $query->append(new Querylet($img_sql, $img_vars));
        }

        if(!is_null($order)) {
            $query->append(new Querylet(" ORDER BY ".$order));
        }

        if (!is_null($limit)) {
            $query->append(new Querylet(" LIMIT :limit ", ["limit" => $limit]));
            $query->append(new Querylet(" OFFSET :offset ", ["offset" => $offset]));
        }

        return $query;
    }
}
