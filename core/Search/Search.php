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
            $cache_key = "image-count:" . md5(Tag::implode($terms));
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
    public static function tag_or_wildcard_to_ids(string $tag): array
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
        if ($params->tag_count === 0) {
            static::$_search_path[] = "no_tags";
            $query = new Querylet("SELECT $columns FROM images WHERE 1=1");
        }

        // one tag sorted by ID - we can fetch this from the image_tags table,
        // and do the offset / limit there, which is 10x faster than fetching
        // all the image_tags and doing the offset / limit on the result.
        elseif (
            $params->tag_count === 1
            // We can only do this if img_conditions is empty, because
            // we're going to apply the offset / limit to the image_tags
            // subquery, and applying extra conditions to the top-level
            // query might reduce the total results below the target limit
            && $params->img_count === 0
            && !$params->tracker->first()->negative
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
            /** @var TagCondition $tc */
            $tc = $params->tracker->first();
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
            $params->tracker->check_all_dis();
            if ($params->tracker->no_child_groups) {
                $sub_query = $params->tracker->everything_sql(); // simpler
            } else {
                $sub_query = $params->tracker->generate_sql();
            }
            if (is_null($sub_query)) {
                throw new PostNotFound("No posts were found to match the search criteria");
            }

            $query = new Querylet("
                    SELECT $columns
                    FROM images
                    INNER JOIN ({$sub_query->sql}) a on a.image_id = images.id
                ", $sub_query->variables);
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
