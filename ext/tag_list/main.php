<?php

declare(strict_types=1);

namespace Shimmie2;

class TagList extends Extension
{
    /** @var TagListTheme */
    protected Themelet $theme;

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_int(TagListConfig::LENGTH, 15);
        $config->set_default_int(TagListConfig::POPULAR_TAG_LIST_LENGTH, 15);
        $config->set_default_string(TagListConfig::INFO_LINK, 'https://en.wikipedia.org/wiki/$tag');
        $config->set_default_string(TagListConfig::OMIT_TAGS, 'tagme*');
        $config->set_default_string(TagListConfig::IMAGE_TYPE, TagListConfig::TYPE_RELATED);
        $config->set_default_string(TagListConfig::RELATED_SORT, TagListConfig::SORT_ALPHABETICAL);
        $config->set_default_string(TagListConfig::POPULAR_SORT, TagListConfig::SORT_TAG_COUNT);
    }

    public function onPostListBuilding(PostListBuildingEvent $event): void
    {
        global $config, $page;
        if ($config->get_int(TagListConfig::LENGTH) > 0) {
            if (!empty($event->search_terms)) {
                $this->add_refine_block($page, $event->search_terms);
            } else {
                $this->add_popular_block($page);
            }
        }
    }

    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        global $config, $page;
        if ($config->get_int(TagListConfig::LENGTH) > 0) {
            $type = $config->get_string(TagListConfig::IMAGE_TYPE);
            if ($type == TagListConfig::TYPE_TAGS || $type == TagListConfig::TYPE_BOTH) {
                $this->add_tags_block($page, $event->image);
            }
            if ($type == TagListConfig::TYPE_RELATED || $type == TagListConfig::TYPE_BOTH) {
                $this->add_related_block($page, $event->image);
            }
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Popular / Related Tag List");
        $sb->add_int_option(TagListConfig::LENGTH, "Show top ");
        $sb->add_label(" related tags");
        $sb->add_int_option(TagListConfig::POPULAR_TAG_LIST_LENGTH, "<br>Show top ");
        $sb->add_label(" popular tags");
        $sb->start_table();
        $sb->add_text_option(TagListConfig::INFO_LINK, "Tag info link", true);
        $sb->add_text_option(TagListConfig::OMIT_TAGS, "Omit tags", true);
        $sb->add_choice_option(
            TagListConfig::IMAGE_TYPE,
            TagListConfig::TYPE_CHOICES,
            "Post tag list",
            true
        );
        $sb->add_choice_option(
            TagListConfig::RELATED_SORT,
            TagListConfig::SORT_CHOICES,
            "Sort related list by",
            true
        );
        $sb->add_choice_option(
            TagListConfig::POPULAR_SORT,
            TagListConfig::SORT_CHOICES,
            "Sort popular list by",
            true
        );
        $sb->add_bool_option("tag_list_numbers", "Show tag counts", true);
        $sb->end_table();
    }

    /**
     * @return int[]
     */
    private static function get_omitted_tags(): array
    {
        global $cache, $config, $database;
        $tags_config =  $config->get_string(TagListConfig::OMIT_TAGS);

        $results = $cache->get("tag_list_omitted_tags:".$tags_config);

        if (is_null($results)) {
            $tags = Tag::explode($tags_config, false);

            if (count($tags) == 0) {
                return [];
            }

            $where = [];
            $args = [];
            $i = 0;
            foreach ($tags as $tag) {
                $i++;
                $arg = "tag$i";
                $args[$arg] = Tag::sqlify($tag);
                if (!str_contains($tag, '*')
                    && !str_contains($tag, '?')) {
                    $where[] = " tag = :$arg ";
                } else {
                    $where[] = " tag LIKE :$arg ";
                }
            }

            $results = $database->get_col("SELECT id FROM tags WHERE " . implode(" OR ", $where), $args);

            $cache->set("tag_list_omitted_tags:" . $tags_config, $results, 600);
        }
        return $results;
    }

    private function add_related_block(Page $page, Image $image): void
    {
        global $database, $config;

        $omitted_tags = self::get_omitted_tags();
        $starting_tags = $database->get_col("SELECT tag_id FROM image_tags WHERE image_id = :image_id", ["image_id" => $image->id]);

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

        $args = ["tag_list_length" => $config->get_int(TagListConfig::LENGTH)];

        $tags = $database->get_all($query, $args);
        if (count($tags) > 0) {
            $this->theme->display_related_block($page, $tags, "Related Tags");
        }
    }

    private function add_tags_block(Page $page, Image $image): void
    {
        global $config, $database;

        $tags = $database->get_all("
			SELECT tags.tag, tags.count
			FROM tags, image_tags
			WHERE tags.id = image_tags.tag_id
			AND image_tags.image_id = :image_id
			ORDER BY tags.count DESC
		", ["image_id" => $image->id]);
        if (count($tags) > 0) {
            if (Extension::is_enabled(TagCategoriesInfo::KEY) and $config->get_bool(TagCategoriesConfig::SPLIT_ON_VIEW)) {
                $this->theme->display_split_related_block($page, $tags);
            } else {
                $this->theme->display_related_block($page, $tags, "Tags");
            }
        }
    }

    private function add_popular_block(Page $page): void
    {
        global $cache, $database, $config;

        $tags = $cache->get("popular_tags");
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

            $args = ["popular_tag_list_length" => $config->get_int(TagListConfig::POPULAR_TAG_LIST_LENGTH)];

            $tags = $database->get_all($query, $args);

            $cache->set("popular_tags", $tags, 600);
        }
        if (count($tags) > 0) {
            $this->theme->display_popular_block($page, $tags);
        }
    }

    /**
     * @param string[] $search
     */
    private function add_refine_block(Page $page, array $search): void
    {
        global $cache, $config, $database;

        if (count($search) > 5) {
            return;
        }

        $related_tags = self::get_related_tags($search, $config->get_int(TagListConfig::LENGTH));

        if (!empty($related_tags)) {
            $this->theme->display_refine_block($page, $related_tags, $search);
        }
    }

    /**
     * @param string[] $search
     * @return array<array{tag: string, count: int}>
     */
    public static function get_related_tags(array $search, int $limit): array
    {
        global $cache, $database;

        $cache_key = "related_tags:" . md5(Tag::implode($search));
        $related_tags = $cache->get($cache_key);

        if (is_null($related_tags)) {
            // $search_tags = array();

            $starting_tags = [];
            $tags_ok = true;
            foreach ($search as $tag) {
                if ($tag[0] == "-" || str_starts_with($tag, "tagme")) {
                    continue;
                }
                $tag = Tag::sqlify($tag);
                $tag_ids = $database->get_col("SELECT id FROM tags WHERE tag LIKE :tag AND count < 25000", ["tag" => $tag]);
                // $search_tags = array_merge($search_tags,
                //                  $database->get_col("SELECT tag FROM tags WHERE tag LIKE :tag", array("tag"=>$tag)));
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

                $related_tags = $database->get_all($query, $args);
            } else {
                $related_tags = [];
            }
            $cache->set($cache_key, $related_tags, 60 * 60);
        }
        return $related_tags;
    }
}
