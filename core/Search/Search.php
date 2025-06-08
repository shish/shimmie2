<?php

declare(strict_types=1);

namespace Shimmie2;

use GQLA\Query;

final class Search
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
     * Build a search query for a given set of terms and return
     * the results as a PDOStatement (raw SQL rows)
     *
     * @param search-term-array $terms
     */
    private static function find_images_internal(int $start = 0, ?int $limit = null, array $terms = []): \FFSPHP\PDOStatement
    {
        if ($start < 0) {
            $start = 0;
        }
        if ($limit !== null && $limit < 1) {
            $limit = 1;
        }

        if (Ctx::$config->get(IndexConfig::BIG_SEARCH) > 0) {
            $anon_limit = Ctx::$config->get(IndexConfig::BIG_SEARCH);
            $counted_tags = $terms;
            // exclude tags which start with "id>", "id<", or "order:id_"
            // because those are added internally for post/next and post/prev
            $counted_tags = array_filter($counted_tags, fn ($tag) => !\Safe\preg_match("/^id[><]|^order:id_/", $tag));
            if (!Ctx::$user->can(IndexPermission::BIG_SEARCH) and count($counted_tags) > $anon_limit) {
                throw new PermissionDenied("Anonymous users may only search for up to $anon_limit tags at a time");
            }
        }

        $params = SearchParameters::from_terms($terms);
        $querylet = self::build_search_querylet($params, $limit, $start);
        // @phpstan-ignore-next-line
        return Ctx::$database->get_all_iterable($querylet->sql, $querylet->variables);
    }

    /**
     * Search for an array of images
     *
     * @param search-term-array $terms
     * @return Image[]
     */
    #[Query(name: "posts", type: "[Post!]!", args: ["terms" => "[string!]"])]
    public static function find_images(int $offset = 0, ?int $limit = null, array $terms = []): array
    {
        $result = self::find_images_internal($offset, $limit, $terms);

        $images = [];
        foreach ($result as $row) {
            $images[] = new Image($row);
        }
        return $images;
    }

    /**
     * Search for an array of images, returning a iterable object of Image
     *
     * @param search-term-array $terms
     * @return \Generator<Image>
     */
    public static function find_images_iterable(int $start = 0, ?int $limit = null, array $terms = []): \Generator
    {
        $result = self::find_images_internal($start, $limit, $terms);
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
        foreach (Search::find_images(terms: ["id=" . implode(",", $ids)]) as $image) {
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

    /**
     * @param tag-string $tag
     */
    public static function count_tag(string $tag): int
    {
        return (int)Ctx::$database->get_one(
            "SELECT count FROM tags WHERE LOWER(tag) = LOWER(:tag)",
            ["tag" => $tag]
        );
    }

    private static function count_total_images(): int
    {
        return cache_get_or_set("image-count", fn () => (int)Ctx::$database->get_one("SELECT COUNT(*) FROM images"), 600);
    }

    /**
     * Count the number of image results for a given search
     *
     * @param search-term-array $terms
     */
    public static function count_images(array $terms = []): int
    {
        $term_count = count($terms);

        // speed_hax ignores the fact that extensions can add img_conditions
        // even when there are no tags being searched for
        $limit_complex = (Ctx::$config->get(IndexConfig::LIMIT_COMPLEX));
        if ($limit_complex && $term_count === 0) {
            // total number of images in the DB
            $total = self::count_total_images();
        } elseif ($limit_complex && $term_count === 1 && !\Safe\preg_match("/[:=><\*\?]/", $terms[0])) {
            if (str_starts_with($terms[0], "-")) {
                // one negative tag - subtract from the total
                $tag = substr($terms[0], 1);
                assert(strlen($tag) > 0);
                $total = self::count_total_images() - self::count_tag($tag);
            } else {
                // one positive tag - we can look that up directly
                $total = self::count_tag($terms[0]);
            }
        } else {
            // complex query
            // implode(tags) can be too long for memcache, so use the hash of tags as the key
            $cache_key = "image-count:" . md5(SearchTerm::implode($terms));
            $total = Ctx::$cache->get($cache_key);
            if (is_null($total)) {
                $params = SearchParameters::from_terms($terms);
                $querylet = self::build_search_querylet($params, count: true);
                // @phpstan-ignore-next-line
                $total = (int)Ctx::$database->get_one($querylet->sql, $querylet->variables);
                if ($limit_complex && $total > 5000) {
                    // when we have a ton of images, the count
                    // won't change dramatically very often
                    Ctx::$cache->set($cache_key, $total, 3600);
                }
            }
        }
        return $total;
    }


    /**
     * @param tag-pattern-string $tag
     * @return list<int>
     */
    private static function tag_or_wildcard_to_ids(string $tag): array
    {
        $sq = "SELECT id FROM tags WHERE SCORE_ILIKE(tag, :tag)";
        return Ctx::$database->get_col($sq, ["tag" => Tag::sqlify($tag)]);
    }

    /**
     * Turn an abstract search query into an SQL Querylet
     *
     * (This is only public for testing purposes, nobody should be calling this
     * directly from outside this class)
     */
    public static function build_search_querylet(
        SearchParameters $params,
        ?int $limit = null,
        ?int $offset = null,
        bool $count = false,
    ): Querylet {
        $columns = $count ? "COUNT(*)" : "images.*";

        // no tags, do a simple search
        if (count($params->tag_conditions) === 0) {
            static::$_search_path[] = "no_tags";
            $query = new Querylet("SELECT $columns FROM images WHERE 1=1");
        }

        // one tag sorted by ID - we can fetch this from the image_tags table,
        // and do the offset / limit there, which is 10x faster than fetching
        // all the image_tags and doing the offset / limit on the result.
        elseif (
            count($params->tag_conditions) === 1
            && $params->tag_conditions[0]->positive
            // We can only do this if img_conditions is empty, because
            // we're going to apply the offset / limit to the image_tags
            // subquery, and applying extra conditions to the top-level
            // query might reduce the total results below the target limit
            && empty($params->img_conditions)
            // We can only do this if we're sorting by ID, because
            // we're going to be using the image_tags table, which
            // only has image_id and tag_id, not any other columns
            && ($params->order === "id DESC" || $params->order === "images.id DESC")
            // This is only an optimisation if we are applying limit
            // and offset
            && !is_null($limit)
            && !is_null($offset)
        ) {
            static::$_search_path[] = "fast";
            $tc = $params->tag_conditions[0];
            // IN (SELECT id FROM tags) is 100x slower than doing a separate
            // query and then a second query for IN(first_query_results)??
            $tag_array = self::tag_or_wildcard_to_ids($tc->tag);
            if (count($tag_array) === 0) {
                // if wildcard expanded to nothing, take a shortcut
                static::$_search_path[] = "invalid_tag";
                $query = new Querylet("SELECT $columns FROM images WHERE 1=0");
            } else {
                $set = implode(', ', $tag_array);
                $query = new Querylet("
                    SELECT $columns
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

            foreach ($params->tag_conditions as $tq) {
                $tag_ids = self::tag_or_wildcard_to_ids($tq->tag);
                $tag_count = count($tag_ids);

                if ($tq->positive) {
                    $all_nonexistent_negatives = false;
                    if ($tag_count === 0) {
                        # one of the positive tags had zero results, therefor there
                        # can be no results; "where 1=0" should shortcut things
                        static::$_search_path[] = "invalid_tag";
                        return new Querylet("SELECT $columns FROM images WHERE 1=0");
                    } elseif ($tag_count === 1) {
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
                $query = new Querylet("SELECT $columns FROM images WHERE 1=1");
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
                    SELECT $columns
                    FROM images
                    INNER JOIN ($sub_query) a on a.image_id = images.id
                ");
            } elseif (!empty($negative_tag_id_array)) {
                static::$_search_path[] = "only_negative_tags";
                $negative_tag_id_list = join(', ', $negative_tag_id_array);
                $query = new Querylet("
                    SELECT $columns
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
        if (!empty($params->img_conditions)) {
            $n = 0;
            $img_sql = "";
            $img_vars = [];
            foreach ($params->img_conditions as $iq) {
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

        if (!is_null($params->order) && $count === false) {
            $query->append(new Querylet(" ORDER BY ".$params->order));
        }

        if (!is_null($limit) && $count === false) {
            $query->append(new Querylet(" LIMIT :limit ", ["limit" => $limit]));
            $query->append(new Querylet(" OFFSET :offset ", ["offset" => $offset]));
        }

        return $query;
    }
}
