<?php

declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<TagListTheme> */
final class TagList extends Extension
{
    public const KEY = "tag_list";

    #[EventListener]
    public function onPostListBuilding(PostListBuildingEvent $event): void
    {
        if (Ctx::$config->get(TagListConfig::LENGTH) > 0) {
            if (!empty($event->search_terms)) {
                $this->add_refine_block($event->search_terms);
            } else {
                $this->add_popular_block();
            }
        }
    }

    #[EventListener]
    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        if (Ctx::$config->get(TagListConfig::LENGTH) > 0) {
            $type = Ctx::$config->get(TagListConfig::IMAGE_TYPE);
            if ($type === TagListConfig::TYPE_TAGS || $type === TagListConfig::TYPE_BOTH) {
                $this->add_tags_block($event->image);
            }
            if ($type === TagListConfig::TYPE_RELATED || $type === TagListConfig::TYPE_BOTH) {
                $this->add_related_block($event->image);
            }
        }
    }

    /**
     * @return int[]
     */
    private static function get_omitted_tags(): array
    {
        $tags_config = Ctx::$config->get(TagListConfig::OMIT_TAGS);

        return cache_get_or_set(
            "tag_list_omitted_tags:" . $tags_config,
            function () use ($tags_config) {
                $tags = Tag::explode($tags_config, false);

                if (count($tags) === 0) {
                    return [];
                }

                $where = [];
                $args = [];
                foreach ($tags as $i => $tag) {
                    $arg = "tag$i";
                    $args[$arg] = Tag::sqlify($tag);
                    $where[] = "SCORE_ILIKE(tag, :$arg)";
                }

                // @phpstan-ignore-next-line
                return Ctx::$database->get_col("SELECT id FROM tags WHERE " . implode(" OR ", $where), $args);
            },
            600
        );
    }

    private function add_related_block(Image $image): void
    {
        $omitted_tags = self::get_omitted_tags();
        $starting_tags = Ctx::$database->get_col("SELECT tag_id FROM image_tags WHERE image_id = :image_id", ["image_id" => $image->id]);

        $starting_tags = array_diff($starting_tags, $omitted_tags);

        if (count($starting_tags) === 0) {
            // No valid starting tags, so can't look anything up
            return;
        }

        $query = "SELECT tags.* FROM tags INNER JOIN (
                SELECT it2.tag_id
                FROM image_tags AS it1
                    INNER JOIN image_tags AS it2 ON it1.image_id=it2.image_id
                        AND it2.tag_id NOT IN (".implode(",", array_merge($omitted_tags, $starting_tags)).")
                WHERE
                    it1.tag_id IN (".implode(",", $starting_tags).")
                GROUP BY it2.tag_id
            ) A ON A.tag_id = tags.id
			ORDER BY count DESC
			LIMIT :tag_list_length
		";

        $args = ["tag_list_length" => Ctx::$config->get(TagListConfig::LENGTH)];

        // @phpstan-ignore-next-line
        $tags = Ctx::$database->get_all($query, $args);
        /** @var array<array{tag: tag-string, count: int}> $tags */
        if (count($tags) > 0) {
            $this->theme->display_related_block($tags, "Related Tags");
        }
    }

    private function add_tags_block(Image $image): void
    {
        /** @var array<array{tag: tag-string, count: int}> $tags */
        $tags = Ctx::$database->get_all("
			SELECT tags.tag, tags.count
			FROM tags, image_tags
			WHERE tags.id = image_tags.tag_id
			AND image_tags.image_id = :image_id
			ORDER BY tags.count DESC
		", ["image_id" => $image->id]);
        if (count($tags) > 0) {
            if (TagCategoriesInfo::is_enabled() and Ctx::$config->get(TagCategoriesConfig::SPLIT_ON_VIEW)) {
                $this->theme->display_split_related_block($tags);
            } else {
                $this->theme->display_related_block($tags, "Tags");
            }
        }
    }

    private function add_popular_block(): void
    {
        $tags = Ctx::$cache->get("popular_tags");
        if (is_null($tags)) {
            $omitted_tags = self::get_omitted_tags();

            if (empty($omitted_tags)) {
                $query = "
                    SELECT tag, count
                    FROM tags
                    WHERE count > 0
                    ORDER BY count DESC
                    LIMIT :popular_tag_list_length
                    ";
            } else {
                $query = "
                    SELECT tag, count
                    FROM tags
                    WHERE count > 0
                        AND id NOT IN (".(implode(",", $omitted_tags)).")
                    ORDER BY count DESC
                    LIMIT :popular_tag_list_length
                    ";
            }

            $args = ["popular_tag_list_length" => Ctx::$config->get(TagListConfig::POPULAR_TAG_LIST_LENGTH)];

            // @phpstan-ignore-next-line
            $tags = Ctx::$database->get_all($query, $args);

            Ctx::$cache->set("popular_tags", $tags, 600);
        }
        if (count($tags) > 0) {
            $this->theme->display_popular_block($tags);
        }
    }

    /**
     * @param search-term-array $search
     */
    private function add_refine_block(array $search): void
    {
        if (count($search) > 5) {
            return;
        }

        $related_tags = self::get_related_tags($search, Ctx::$config->get(TagListConfig::LENGTH));

        if (!empty($related_tags)) {
            $this->theme->display_refine_block($related_tags, $search);
        }
    }

    /**
     * @param search-term-array $search
     * @return array<array{tag: tag-string, count: int}>
     */
    public static function get_related_tags(array $search, int $limit): array
    {
        $cache_key = "related_tags:" . md5(SearchTerm::implode($search));
        $related_tags = Ctx::$cache->get($cache_key);

        if (is_null($related_tags)) {
            // $search_tags = array();

            $starting_tags = [];
            $tags_ok = true;
            foreach ($search as $tag) {
                if ($tag[0] === "-" || str_starts_with($tag, "tagme")) {
                    continue;
                }
                $tag_ids = Ctx::$database->get_col(
                    "SELECT id FROM tags WHERE SCORE_ILIKE(tag, :tag) AND count < 25000",
                    ["tag" => Tag::sqlify($tag)]
                );
                $starting_tags = array_merge($starting_tags, $tag_ids);
                $tags_ok = count($tag_ids) > 0;
                if (!$tags_ok) {
                    break;
                }
            }

            if (count($starting_tags) > 5 || count($starting_tags) === 0) {
                return [];
            }

            $omitted_tags = self::get_omitted_tags();

            $starting_tags = array_diff($starting_tags, $omitted_tags);

            if (count($starting_tags) === 0) {
                // No valid starting tags, so can't look anything up
                return [];
            }

            if ($tags_ok) {
                $query = "SELECT t.tag, A.calc_count AS count FROM tags t INNER JOIN (
					SELECT it2.tag_id, COUNT(it2.image_id) AS calc_count
					FROM image_tags AS it1 -- Got other images with the same tags
					    INNER JOIN image_tags AS it2 ON it1.image_id=it2.image_id
					    -- And filter out unwanted tags
                            AND it2.tag_id NOT IN (".implode(",", array_merge($omitted_tags, $starting_tags)).")
					WHERE
                    it1.tag_id IN (".implode(",", $starting_tags).")
					GROUP BY it2.tag_id) A ON A.tag_id = t.id
					ORDER BY A.calc_count
					DESC LIMIT :limit
				";
                $args = ["limit" => $limit];

                // @phpstan-ignore-next-line
                $related_tags = Ctx::$database->get_all($query, $args);
            } else {
                $related_tags = [];
            }
            Ctx::$cache->set($cache_key, $related_tags, 60 * 60);
        }
        return $related_tags;
    }
}
