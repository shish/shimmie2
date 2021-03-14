<?php declare(strict_types=1);

require_once "config.php";

class TagList extends Extension
{
    /** @var TagListTheme */
    protected ?Themelet $theme;

    public function onInitExt(InitExtEvent $event)
    {
        global $config;
        $config->set_default_int(TagListConfig::LENGTH, 15);
        $config->set_default_int(TagListConfig::POPULAR_TAG_LIST_LENGTH, 15);
        $config->set_default_int(TagListConfig::TAGS_MIN, 3);
        $config->set_default_string(TagListConfig::INFO_LINK, 'https://en.wikipedia.org/wiki/$tag');
        $config->set_default_string(TagListConfig::OMIT_TAGS, 'tagme*');
        $config->set_default_string(TagListConfig::IMAGE_TYPE, TagListConfig::TYPE_RELATED);
        $config->set_default_string(TagListConfig::RELATED_SORT, TagListConfig::SORT_ALPHABETICAL);
        $config->set_default_string(TagListConfig::POPULAR_SORT, TagListConfig::SORT_TAG_COUNT);
        $config->set_default_bool(TagListConfig::PAGES, false);
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $page;

        if ($event->page_matches("tags")) {
            $this->theme->set_navigation($this->build_navigation());
            if ($event->count_args() == 0) {
                $sub = "map";
            } else {
                $sub = $event->get_arg(0);
            }
            switch ($sub) {
                default:
                case 'map':
                    $this->theme->set_heading("Tag Map");
                    $this->theme->set_tag_list($this->build_tag_map());
                    break;
                case 'alphabetic':
                    $this->theme->set_heading("Alphabetic Tag List");
                    $this->theme->set_tag_list($this->build_tag_alphabetic());
                    break;
                case 'popularity':
                    $this->theme->set_heading("Tag List by Popularity");
                    $this->theme->set_tag_list($this->build_tag_popularity());
                    break;
            }
            $this->theme->display_page($page);
        }
    }

    public function onPostListBuilding(PostListBuildingEvent $event)
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

    public function onPageNavBuilding(PageNavBuildingEvent $event)
    {
        $event->add_nav_link("tags", new Link('tags/map'), "Tags");
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event)
    {
        if ($event->parent=="tags") {
            $event->add_nav_link("tags_map", new Link('tags/map'), "Map");
            $event->add_nav_link("tags_alphabetic", new Link('tags/alphabetic'), "Alphabetic");
            $event->add_nav_link("tags_popularity", new Link('tags/popularity'), "Popularity");
        }
    }

    public function onDisplayingImage(DisplayingImageEvent $event)
    {
        global $config, $page;
        if ($config->get_int(TagListConfig::LENGTH) > 0) {
            $type = $config->get_string(TagListConfig::IMAGE_TYPE);
            if ($type == TagListConfig::TYPE_TAGS || $type == TagListConfig::TYPE_BOTH) {
                if (class_exists("TagCategories") and $config->get_bool(TagCategoriesConfig::SPLIT_ON_VIEW)) {
                    $this->add_split_tags_block($page, $event->image);
                } else {
                    $this->add_tags_block($page, $event->image);
                }
            }
            if ($type == TagListConfig::TYPE_RELATED || $type == TagListConfig::TYPE_BOTH) {
                $this->add_related_block($page, $event->image);
            }
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = $event->panel->create_new_block("Tag Map Options");
        $sb->add_int_option(TagListConfig::TAGS_MIN, "Only show tags used at least ");
        $sb->add_label(" times");
        $sb->add_bool_option(TagListConfig::PAGES, "<br>Paged tag lists: ");

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
     * Get the minimum number of times a tag needs to be used
     * in order to be considered in the tag list.
     */
    private function get_tags_min(): int
    {
        if (isset($_GET['mincount'])) {
            return int_escape($_GET['mincount']);
        } else {
            global $config;
            return $config->get_int(TagListConfig::TAGS_MIN);	// get the default.
        }
    }

    private static function get_omitted_tags(): array
    {
        global $cache, $config, $database;
        $tags_config =  $config->get_string(TagListConfig::OMIT_TAGS);

        $results = $cache->get("tag_list_omitted_tags:".$tags_config);

        if ($results==null) {
            $tags = explode(" ", $tags_config);

            if (empty($tags)) {
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

    private function get_starts_with(): string
    {
        global $config;
        if (isset($_GET['starts_with'])) {
            return $_GET['starts_with'] . "%";
        } else {
            if ($config->get_bool(TagListConfig::PAGES)) {
                return "a%";
            } else {
                return "%";
            }
        }
    }

    private function build_az(): string
    {
        global $database;

        $tags_min = $this->get_tags_min();

        $tag_data = $database->get_col("
			SELECT DISTINCT
				LOWER(substr(tag, 1, 1))
			FROM tags
			WHERE count >= :tags_min
			ORDER BY LOWER(substr(tag, 1, 1))
		", ["tags_min"=>$tags_min]);

        $html = "<span class='atoz'>";
        foreach ($tag_data as $a) {
            $html .= " <a href='".modify_current_url(["starts_with"=>$a])."'>$a</a>";
        }
        $html .= "</span>\n<p><hr>";

        return $html;
    }

    private function build_navigation(): string
    {
        $h_index = "<a href='".make_link()."'>Index</a>";
        $h_map = "<a href='".make_link("tags/map")."'>Map</a>";
        $h_alphabetic = "<a href='".make_link("tags/alphabetic")."'>Alphabetic</a>";
        $h_popularity = "<a href='".make_link("tags/popularity")."'>Popularity</a>";
        $h_all = "<a href='".modify_current_url(["mincount"=>1])."'>Show All</a>";
        return "$h_index<br>&nbsp;<br>$h_map<br>$h_alphabetic<br>$h_popularity<br>&nbsp;<br>$h_all";
    }

    private function build_tag_map(): string
    {
        global $config, $database;

        $tags_min = $this->get_tags_min();
        $starts_with = $this->get_starts_with();

        // check if we have a cached version
        $cache_key = warehouse_path(
            "cache/tag_cloud",
            md5("tc" . $tags_min . $starts_with . VERSION)
        );
        if (file_exists($cache_key)) {
            return file_get_contents($cache_key);
        }

        // SHIT: PDO/pgsql has problems using the same named param twice -_-;;
        $tag_data = $database->get_all("
            SELECT
                tag,
                FLOOR(LOG(2.7, LOG(2.7, count - :tags_min2 + 1)+1)*1.5*100)/100 AS scaled
            FROM tags
            WHERE count >= :tags_min
            AND LOWER(tag) LIKE LOWER(:starts_with)
            ORDER BY LOWER(tag)
        ", ["tags_min"=>$tags_min, "tags_min2"=>$tags_min, "starts_with"=>$starts_with]);

        $html = "";
        if ($config->get_bool(TagListConfig::PAGES)) {
            $html .= $this->build_az();
        }
        if (class_exists('TagCategories')) {
            $this->tagcategories = new TagCategories;
            $tag_category_dict = $this->tagcategories->getKeyedDict();
        }
        foreach ($tag_data as $row) {
            $h_tag = html_escape($row['tag']);
            $size = sprintf("%.2f", (float)$row['scaled']);
            $link = $this->theme->tag_link($row['tag']);
            if ($size<0.5) {
                $size = 0.5;
            }
            $h_tag_no_underscores = str_replace("_", " ", $h_tag);
            if (class_exists('TagCategories')) {
                $h_tag_no_underscores = $this->tagcategories->getTagHtml($h_tag, $tag_category_dict);
            }
            $html .= "&nbsp;<a style='font-size: ${size}em' href='$link'>$h_tag_no_underscores</a>&nbsp;\n";
        }

        if (SPEED_HAX) {
            file_put_contents($cache_key, $html);
        }

        return $html;
    }

    private function build_tag_alphabetic(): string
    {
        global $config, $database;

        $tags_min = $this->get_tags_min();
        $starts_with = $this->get_starts_with();

        // check if we have a cached version
        $cache_key = warehouse_path(
            "cache/tag_alpha",
            md5("ta" . $tags_min . $starts_with . VERSION)
        );
        if (file_exists($cache_key)) {
            return file_get_contents($cache_key);
        }

        $tag_data = $database->get_pairs("
            SELECT tag, count
            FROM tags
            WHERE count >= :tags_min
            AND LOWER(tag) LIKE LOWER(:starts_with)
            ORDER BY LOWER(tag)
        ", ["tags_min"=>$tags_min, "starts_with"=>$starts_with]);

        $html = "";
        if ($config->get_bool(TagListConfig::PAGES)) {
            $html .= $this->build_az();
        }

        /*
          strtolower() vs. mb_strtolower()
          ( See https://www.php.net/manual/en/function.mb-strtolower.php for more info )

          PHP5's strtolower function does not support Unicode (UTF-8) properly, so
          you have to use another function, mb_strtolower, to handle UTF-8 strings.

          What's worse is that mb_strtolower is horribly SLOW.

          It would probably be better to have a config option for the Tag List that
          would allow you to specify if there are UTF-8 tags.

        */
        mb_internal_encoding('UTF-8');

        if (class_exists('TagCategories')) {
            $this->tagcategories = new TagCategories;
            $tag_category_dict = $this->tagcategories->getKeyedDict();
        }

        $lastLetter = "";
        # postres utf8 string sort ignores punctuation, so we get "aza, a-zb, azc"
        # which breaks down into "az, a-, az" :(
        ksort($tag_data, SORT_STRING | SORT_FLAG_CASE);
        foreach ($tag_data as $tag => $count) {
            // In PHP, $array["10"] sets the array key as int(10), not string("10")...
            $tag = (string)$tag;
            if ($lastLetter != mb_strtolower(substr($tag, 0, strlen($starts_with)+1))) {
                $lastLetter = mb_strtolower(substr($tag, 0, strlen($starts_with)+1));
                $h_lastLetter = html_escape($lastLetter);
                $html .= "<p>$h_lastLetter<br>";
            }
            $link = $this->theme->tag_link($tag);
            $h_tag = html_escape($tag);
            if (class_exists('TagCategories')) {
                $h_tag = $this->tagcategories->getTagHtml($h_tag, $tag_category_dict, "&nbsp;($count)");
            }
            $html .= "<a href='$link'>$h_tag</a>\n";
        }

        if (SPEED_HAX) {
            file_put_contents($cache_key, $html);
        }

        return $html;
    }

    private function build_tag_popularity(): string
    {
        global $database;

        $tags_min = $this->get_tags_min();

        // Make sure that the value of $tags_min is at least 1.
        // Otherwise the database will complain if you try to do: LOG(0)
        if ($tags_min < 1) {
            $tags_min = 1;
        }

        // check if we have a cached version
        $cache_key = warehouse_path(
            "cache/tag_popul",
            md5("tp" . $tags_min . VERSION)
        );
        if (file_exists($cache_key)) {
            return file_get_contents($cache_key);
        }

        $tag_data = $database->get_all("
            SELECT tag, count, FLOOR(LOG(count)) AS scaled
            FROM tags
            WHERE count >= :tags_min
            ORDER BY count DESC, tag ASC
        ", ["tags_min"=>$tags_min]);

        $html = "Results grouped by log<sub>10</sub>(n)";
        $lastLog = "";
        foreach ($tag_data as $row) {
            $h_tag = html_escape($row['tag']);
            $count = $row['count'];
            $scaled = $row['scaled'];
            if ($lastLog != $scaled) {
                $lastLog = $scaled;
                $html .= "<p>$lastLog<br>";
            }
            $link = $this->theme->tag_link($row['tag']);
            $html .= "<a href='$link'>$h_tag&nbsp;($count)</a>\n";
        }

        if (SPEED_HAX) {
            file_put_contents($cache_key, $html);
        }

        return $html;
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

    private function add_split_tags_block(Page $page, Image $image)
    {
        global $database;

        $query = "
			SELECT tags.tag, tags.count
			FROM tags, image_tags
			WHERE tags.id = image_tags.tag_id
			AND image_tags.image_id = :image_id
			ORDER BY tags.count DESC
		";
        $args = ["image_id"=>$image->id];

        $tags = $database->get_all($query, $args);
        if (count($tags) > 0) {
            $this->theme->display_split_related_block($page, $tags);
        }
    }

    private function add_tags_block(Page $page, Image $image)
    {
        global $database;

        $query = "
			SELECT tags.tag, tags.count
			FROM tags, image_tags
			WHERE tags.id = image_tags.tag_id
			AND image_tags.image_id = :image_id
			ORDER BY tags.count DESC
		";
        $args = ["image_id"=>$image->id];

        $tags = $database->get_all($query, $args);
        if (count($tags) > 0) {
            $this->theme->display_related_block($page, $tags, "Tags");
        }
    }

    private function add_popular_block(Page $page)
    {
        global $cache, $database, $config;

        $tags = $cache->get("popular_tags");
        if (empty($tags)) {
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

            $args = ["popular_tag_list_length"=>$config->get_int(TagListConfig::POPULAR_TAG_LIST_LENGTH)];

            $tags = $database->get_all($query, $args);

            $cache->set("popular_tags", $tags, 600);
        }
        if (count($tags) > 0) {
            $this->theme->display_popular_block($page, $tags);
        }
    }

    /**
     * #param string[] $search
     */
    private function add_refine_block(Page $page, array $search)
    {
        global $config;

        if (count($search) > 5) {
            return;
        }

        $wild_tags = $search;

        $related_tags = self::get_related_tags($search, $config->get_int(TagListConfig::LENGTH));

        if (!empty($related_tags)) {
            $this->theme->display_refine_block($page, $related_tags, $wild_tags);
        }
    }

    public static function get_related_tags(array $search, int $limit): array
    {
        global $cache, $database;


        $wild_tags = $search;
        $str_search = Tag::implode($search);
        $related_tags = $cache->get("related_tags:$str_search");

        if (empty($related_tags)) {
            // $search_tags = array();

            $starting_tags = [];
            $tags_ok = true;
            foreach ($wild_tags as $tag) {
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
                $cache->set("related_tags:$str_search", $related_tags, 60 * 60);
            }
        }
        if ($related_tags === false) {
            return [];
        } else {
            return $related_tags;
        }
    }
}
