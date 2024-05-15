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

class TermConditions
{
    /** @var TagCondition[] */
    public array $tag_conditions = [];
    /** @var ImgCondition[] */
    public array $img_conditions = [];
    /** @var mixed[] */
    public array $order = [];
    public ?int $limit;

    public function addOrder(null|string|QueryBuilderOrder $order): void
    {
        if(is_null($order) || (is_string($order) && empty($order))) {
            return;
        }
        $this->order[] = $order;
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

        $queryBuilder = Search::build_search_query_from_terms($tags, $limit, $start);
        $query = $queryBuilder->render();
        return $database->get_all_iterable($query->sql, $query->parameters);

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
                $queryBuilder = Search::build_search_query_from_terms($tags);

                $query = $queryBuilder->renderForCount();

                $total = (int)$database->get_one($query->sql, $query->parameters);

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
     * Turn a human input string into an abstract search query
     *
     * (This is only public for testing purposes, nobody should be calling this
     * directly from outside this class)
     *
     * @param string[] $terms
     */
    public static function terms_to_conditions(array $terms): TermConditions
    {
        global $config;

        $output = new TermConditions();

        /*
         * Turn a bunch of strings into a bunch of TagCondition
         * and ImgCondition objects
         */
        $stpen = 0;  // search term parse event number
        foreach (array_merge([null], $terms) as $term) {
            $stpe = send_event(new SearchTermParseEvent($stpen++, $term, $terms));
            if(!is_null($stpe->order)) {
                $output->addOrder($stpe->order);
            }
            $output->limit ??= $stpe->limit;
            $output->img_conditions = array_merge($output->img_conditions, $stpe->img_conditions);
            $output->tag_conditions = array_merge($output->tag_conditions, $stpe->tag_conditions);
        }

        if(empty($output->order)) {
            $output->addOrder("images.".$config->get_string(IndexConfig::ORDER));
        }

        return $output;
    }

    /**
    * @param string[] $terms
    */
    public static function build_search_query_from_terms(array $terms, ?int $limit = null, ?int $offset = null): QueryBuilder
    {
        $terms = self::terms_to_conditions($terms);
        return self::build_search_query_from_term_conditions($terms, $limit, $offset);
    }

    public static function build_search_query_from_term_conditions(TermConditions $t, ?int $limit = null, ?int $offset = null): QueryBuilder
    {
        $limitToUse = null;
        if(!is_null($t->limit)) {
            if(!is_null($limit)) {
                $limitToUse = min($t->limit, $limit);
            } else {
                $limitToUse = $t->limit;
            }
        } else {
            $limitToUse = $limit;
        }

        return self::build_search_query(
            $t->tag_conditions,
            $t->img_conditions,
            $t->order,
            $limitToUse,
            $offset
        );
    }

    /**
     * Turn an abstract search query into an SQL QueryBuilder
     *
     * (This is only public for testing purposes, nobody should be calling this
     * directly from outside this class)
     *
     * @param TagCondition[] $tag_conditions
     * @param ImgCondition[] $img_conditions
     * @param string|(string|QueryBuilderOrder)[] $orders
     */
    public static function build_search_query(
        array $tag_conditions,
        array $img_conditions,
        string|array $orders,
        ?int $limit = null,
        ?int $offset = null,
    ): QueryBuilder {
        $parsed_orders = [];
        if (is_string($orders)) {
            $parsed_orders = QueryBuilderOrder::parse($orders);
        } else {
            foreach ($orders as $o) {
                if(is_string($o)) {
                    $parsed_orders = array_merge($parsed_orders, QueryBuilderOrder::parse($o));
                } else {
                    $parsed_orders[] = $o;
                }
            }
        }
        $orders = $parsed_orders;

        $query = new QueryBuilder("images");
        $query->addSelectField("images.*");

        // no tags, do a simple search
        if (count($tag_conditions) === 0) {
            static::$_search_path[] = "no_tags";
        }

        $isIdOrdered = false;
        $idOrder = null;
        foreach ($orders as $order) {
            $sourceString = $order->getSourceString();
            if($order->isSourceString() && !empty($sourceString) && !str_contains($sourceString, ".")) {
                // This is checking if the source is just a field name, and if it specifies a table
                // If it doesn't specify a table, it explicitly declares it being for the images table
                $order = new QueryBuilderOrder("images.$sourceString", $order->getAscending());
            }
            if($sourceString == "id" || $sourceString == "images.id") {
                $isIdOrdered = true;
                $idOrder = $order;
            }
            $query->addQueryBuilderOrder($order);
        }

        if (!is_null($limit)) {
            $query->limit = $limit;
            $query->offset = $offset;
        }

        $positive_tag_count = 0;
        $negative_tag_count = 0;
        foreach ($tag_conditions as $tq) {
            if ($tq->positive) {
                $positive_tag_count++;
            } else {
                $negative_tag_count++;
            }
        }

        // no tags, do a simple search
        if ($positive_tag_count === 0 && $negative_tag_count === 0) {
            // Do nothing, use base QueryBuilder by itself

        }

        // one tag sorted by ID - we can fetch this from the image_tags table,
        // and do the offset / limit there, which is 10x faster than fetching
        // all the image_tags and doing the offset / limit on the result.
        elseif (
            (
                ($positive_tag_count === 1 && $negative_tag_count === 0)
                || ($positive_tag_count === 0 && $negative_tag_count === 1)
            )
            && empty($img_conditions)
            && $isIdOrdered
            && !is_null($offset)
            && !is_null($limit)
        ) {
            static::$_search_path[] = "fast";

            $in = $positive_tag_count === 1 ? "IN" : "NOT IN";
            // IN (SELECT id FROM tags) is 100x slower than doing a separate
            // query and then a second query for IN(first_query_results)??
            $tag_array = self::tag_or_wildcard_to_ids($tag_conditions[0]->tag);
            if (count($tag_array) == 0) {
                if ($positive_tag_count == 1) {
                    static::$_search_path[] = "invalid_tag";

                    // An impossible query, short it here
                    $query->addManualCriterion("1=0");
                    return $query;
                }
            } else {
                $set = implode(', ', $tag_array);

                $tagQuery = new QueryBuilder("image_tags", "it");
                $tagQuery->addSelectField("it.image_id");
                $tagQuery->addManualCriterion("it.tag_id $in ($set)");
                $tagQuery->addOrder("it.image_id", $idOrder->getAscending());
                $tagQuery->limit = $limit;
                $tagQuery->offset = $offset;

                $tagJoin = $query->addJoin("INNER", $tagQuery, "a");
                $tagJoin->addManualCriterion("a.image_id = images.id");

                $query->addOrder("images.id", false);
            }
        }
        // more than one positive tag, or more than zero negative tags
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
                        # one of the positive tags had zero results, therefore there
                        # can be no results; "where 1=0" should shortcut things
                        static::$_search_path[] = "invalid_tag";
                        $query->addManualCriterion("1=0");

                        return $query;
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

            assert($positive_tag_id_array || $positive_wildcard_id_array || $negative_tag_id_array || $all_nonexistent_negatives, @$_GET['q']);

            if ($all_nonexistent_negatives) {
                // Not necessary to add a 1=1 with QueryBuilder
                static::$_search_path[] = "all_nonexistent_negatives";
            } elseif (!empty($positive_tag_id_array) || !empty($positive_wildcard_id_array) || !empty($negative_tag_id_array)) {
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
                $i = 0;
                if (!empty($positive_tag_id_array)) {
                    foreach ($positive_tag_id_array as $tag) {
                        $join = $query->addJoin("INNER", "image_tags", "it$i");
                        $join->addManualCriterion("it$i.tag_id = $tag");
                        $join->addManualCriterion("it$i.image_id = images.id");
                        $i++;
                    }
                }

                if (!empty($positive_wildcard_id_array)) {
                    foreach ($positive_wildcard_id_array as $tags) {
                        $source = new QueryBuilder("image_tags");
                        $source->addSelectField("image_id");
                        $source->addInCriterion("tag_id", $tags);
                        $source->addGroup("image_id");

                        $join = $query->addJoin("INNER", $source, "it$i");
                        $join->addManualCriterion("it$i.image_id = images.id");
                        $i++;
                    }
                }

                if (!empty($negative_tag_id_array)) {

                    $join = $query->addJoin(QueryBuilder::LEFT_JOIN, "image_tags", "negative");
                    $join->addManualCriterion("negative.image_id = images.id");
                    $join->addInCriterion("negative.tag_id", $negative_tag_id_array);
                    $query->addManualCriterion("negative.image_id IS NULL");
                }
            }
        }

        /*
         * Merge all the image metadata searches into one generic querylet
         * and append to the base querylet with "AND blah"
         * Also adds special joins
         */
        $aliasCount = 0;
        if (!empty($img_conditions)) {
            $n = 0;
            $img_sql = "";
            $img_vars = [];
            foreach ($img_conditions as $iq) {
                if(strpos($iq->qlet->sql, "JOIN") === 0) {
                    // This is a join criteria
                    $alias = "joinAlias".$aliasCount;

                    $sql = str_replace("{alias}", $alias, substr($iq->qlet->sql, 4));
                    $source = explode(" ON ", $sql)[0];
                    $criteria = explode(" ON ", $sql)[1];
                    $join = $query->addJoin(($iq->positive ? "INNER" : QueryBuilder::LEFT_JOIN), $source);
                    $join->addManualCriterion($criteria);

                    if(!$iq->positive) {
                        $query->addManualCriterion($alias.".image_id IS NULL", $img_vars);
                    }

                    $aliasCount++;
                    continue;
                }

                if ($n++ > 0) {
                    $img_sql .= "\r  AND";
                }
                if (!$iq->positive) {
                    $img_sql .= "\r  NOT";
                }
                $img_sql .= " (" . $iq->qlet->sql . ")";
                $img_vars = array_merge($img_vars, $iq->qlet->variables);
            }
            $query->addManualCriterion($img_sql, $img_vars);
        }
        return $query;
    }

}
