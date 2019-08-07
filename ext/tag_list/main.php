<?php
/**
 * Name: Tag List
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * Description: Show the tags in various ways
 */

class TagList extends Extension
{
    public function onInitExt(InitExtEvent $event)
    {
        global $config;
        $config->set_default_int("tag_list_length", 15);
        $config->set_default_int("popular_tag_list_length", 15);
        $config->set_default_int("tags_min", 3);
        $config->set_default_string("info_link", 'http://en.wikipedia.org/wiki/$tag');
        $config->set_default_string("tag_list_image_type", 'related');
        $config->set_default_string("tag_list_related_sort", 'alphabetical');
        $config->set_default_string("tag_list_popular_sort", 'tagcount');
        $config->set_default_bool("tag_list_pages", false);
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $database;

        if ($event->page_matches("tags")) {
            $this->theme->set_navigation($this->build_navigation());
            switch ($event->get_arg(0)) {
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
                case 'categories':
                    $this->theme->set_heading("Popular Categories");
                    $this->theme->set_tag_list($this->build_tag_list());
                    break;
            }
            $this->theme->display_page($page);
        } elseif ($event->page_matches("api/internal/tag_list/complete")) {
            if (!isset($_GET["s"]) || $_GET["s"] == "" || $_GET["s"] == "_") {
                return;
            }

            //$limit = 0;
            $cache_key = "autocomplete-" . strtolower($_GET["s"]);
            $limitSQL = "";
            $SQLarr = ["search"=>$_GET["s"]."%"];
            if (isset($_GET["limit"]) && $_GET["limit"] !== 0) {
                $limitSQL = "LIMIT :limit";
                $SQLarr['limit'] = $_GET["limit"];
                $cache_key .= "-" . $_GET["limit"];
            }

            $res = null;
            $database->cache->get($cache_key);
            if (!$res) {
                $res = $database->get_col($database->scoreql_to_sql("
					SELECT tag
					FROM tags
					WHERE SCORE_STRNORM(tag) LIKE SCORE_STRNORM(:search)
						AND count > 0
					$limitSQL
				"), $SQLarr);
                $database->cache->set($cache_key, $res, 600);
            }

            $page->set_mode(PageMode::DATA);
            $page->set_type("text/plain");
            $page->set_data(implode("\n", $res));
        }
    }

    public function onPostListBuilding(PostListBuildingEvent $event)
    {
        global $config, $page;
        if ($config->get_int('tag_list_length') > 0) {
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
        if($event->parent=="tags") {
            $event->add_nav_link("tags_map", new Link('tags/map'), "Map");
            $event->add_nav_link("tags_alphabetic", new Link('tags/alphabetic'), "Alphabetic");
            $event->add_nav_link("tags_popularity", new Link('tags/popularity'), "Popularity");
            $event->add_nav_link("tags_categories", new Link('tags/categories'), "Categories");
        }
    }

    public function onDisplayingImage(DisplayingImageEvent $event)
    {
        global $config, $page;
        if ($config->get_int('tag_list_length') > 0) {
            if ($config->get_string('tag_list_image_type') == 'related') {
                $this->add_related_block($page, $event->image);
            } else {
                if (class_exists("TagCategories") and $config->get_bool(TagCategoriesConfig::SPLIT_ON_VIEW)) {
                    $this->add_split_tags_block($page, $event->image);
                } else {
                    $this->add_tags_block($page, $event->image);
                }
            }
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = new SetupBlock("Tag Map Options");
        $sb->add_int_option("tags_min", "Only show tags used at least ");
        $sb->add_label(" times");
        $sb->add_bool_option("tag_list_pages", "<br>Paged tag lists: ");
        $event->panel->add_block($sb);

        $sb = new SetupBlock("Popular / Related Tag List");
        $sb->add_int_option("tag_list_length", "Show top ");
        $sb->add_label(" related tags");
        $sb->add_int_option("popular_tag_list_length", "<br>Show top ");
        $sb->add_label(" popular tags");
        $sb->add_text_option("info_link", "<br>Tag info link: ");
        $sb->add_choice_option("tag_list_image_type", [
            "Image's tags only" => "tags",
            "Show related" => "related"
        ], "<br>Image tag list: ");
        $sb->add_choice_option("tag_list_related_sort", [
            "Tag Count" => "tagcount",
            "Alphabetical" => "alphabetical"
        ], "<br>Sort related list by: ");
        $sb->add_choice_option("tag_list_popular_sort", [
            "Tag Count" => "tagcount",
            "Alphabetical" => "alphabetical"
        ], "<br>Sort popular list by: ");
        $sb->add_bool_option("tag_list_numbers", "<br>Show tag counts: ");
        $event->panel->add_block($sb);
    }
    // }}}
    // misc {{{
    private function tag_link(string $tag): string
    {
        $u_tag = url_escape($tag);
        return make_link("post/list/$u_tag/1");
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
            return $config->get_int('tags_min');	// get the default.
        }
    }

    private function get_starts_with(): string
    {
        global $config;
        if (isset($_GET['starts_with'])) {
            return $_GET['starts_with'] . "%";
        } else {
            if ($config->get_bool("tag_list_pages")) {
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

        $tag_data = $database->get_col($database->scoreql_to_sql("
			SELECT DISTINCT
				SCORE_STRNORM(substr(tag, 1, 1))
			FROM tags
			WHERE count >= :tags_min
			ORDER BY SCORE_STRNORM(substr(tag, 1, 1))
		"), ["tags_min"=>$tags_min]);

        $html = "<span class='atoz'>";
        foreach ($tag_data as $a) {
            $html .= " <a href='".modify_current_url(["starts_with"=>$a])."'>$a</a>";
        }
        $html .= "</span>\n<p><hr>";

        return $html;
    }
    // }}}
    // maps {{{

    private function build_navigation(): string
    {
        $h_index = "<a href='".make_link()."'>Index</a>";
        $h_map = "<a href='".make_link("tags/map")."'>Map</a>";
        $h_alphabetic = "<a href='".make_link("tags/alphabetic")."'>Alphabetic</a>";
        $h_popularity = "<a href='".make_link("tags/popularity")."'>Popularity</a>";
        $h_cats = "<a href='".make_link("tags/categories")."'>Categories</a>";
        $h_all = "<a href='".modify_current_url(["mincount"=>1])."'>Show All</a>";
        return "$h_index<br>&nbsp;<br>$h_map<br>$h_alphabetic<br>$h_popularity<br>$h_cats<br>&nbsp;<br>$h_all";
    }

    private function build_tag_map(): string
    {
        global $config, $database;

        $tags_min = $this->get_tags_min();
        $starts_with = $this->get_starts_with();
        
        // check if we have a cached version
        $cache_key = warehouse_path("cache/tag_cloud", md5("tc" . $tags_min . $starts_with));
        if (file_exists($cache_key)) {
            return file_get_contents($cache_key);
        }

        // SHIT: PDO/pgsql has problems using the same named param twice -_-;;
        $tag_data = $database->get_all($database->scoreql_to_sql("
				SELECT
					tag,
					FLOOR(LOG(2.7, LOG(2.7, count - :tags_min2 + 1)+1)*1.5*100)/100 AS scaled
				FROM tags
				WHERE count >= :tags_min
				AND tag SCORE_ILIKE :starts_with
				ORDER BY SCORE_STRNORM(tag)
			"), ["tags_min"=>$tags_min, "tags_min2"=>$tags_min, "starts_with"=>$starts_with]);

        $html = "";
        if ($config->get_bool("tag_list_pages")) {
            $html .= $this->build_az();
        }
        foreach ($tag_data as $row) {
            $h_tag = html_escape($row['tag']);
            $size = sprintf("%.2f", (float)$row['scaled']);
            $link = $this->tag_link($row['tag']);
            if ($size<0.5) {
                $size = 0.5;
            }
            $h_tag_no_underscores = str_replace("_", " ", $h_tag);
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
        $cache_key = warehouse_path("cache/tag_alpha", md5("ta" . $tags_min . $starts_with));
        if (file_exists($cache_key)) {
            return file_get_contents($cache_key);
        }

        $tag_data = $database->get_pairs($database->scoreql_to_sql("
				SELECT tag, count
				FROM tags
				WHERE count >= :tags_min
				AND tag SCORE_ILIKE :starts_with
				ORDER BY SCORE_STRNORM(tag)
				"), ["tags_min"=>$tags_min, "starts_with"=>$starts_with]);

        $html = "";
        if ($config->get_bool("tag_list_pages")) {
            $html .= $this->build_az();
        }
        
        /*
          strtolower() vs. mb_strtolower()
          ( See http://www.php.net/manual/en/function.mb-strtolower.php for more info )

          PHP5's strtolower function does not support Unicode (UTF-8) properly, so
          you have to use another function, mb_strtolower, to handle UTF-8 strings.

          What's worse is that mb_strtolower is horribly SLOW.

          It would probably be better to have a config option for the Tag List that
          would allow you to specify if there are UTF-8 tags.

        */
        mb_internal_encoding('UTF-8');
        
        $lastLetter = "";
        # postres utf8 string sort ignores punctuation, so we get "aza, a-zb, azc"
        # which breaks down into "az, a-, az" :(
        ksort($tag_data, SORT_STRING | SORT_FLAG_CASE);
        foreach ($tag_data as $tag => $count) {
            if ($lastLetter != mb_strtolower(substr($tag, 0, strlen($starts_with)+1))) {
                $lastLetter = mb_strtolower(substr($tag, 0, strlen($starts_with)+1));
                $h_lastLetter = html_escape($lastLetter);
                $html .= "<p>$h_lastLetter<br>";
            }
            $link = $this->tag_link($tag);
            $h_tag = html_escape($tag);
            $html .= "<a href='$link'>$h_tag&nbsp;($count)</a>\n";
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
        $cache_key = warehouse_path("cache/tag_popul", md5("tp" . $tags_min));
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
            $link = $this->tag_link($row['tag']);
            $html .= "<a href='$link'>$h_tag&nbsp;($count)</a>\n";
        }

        if (SPEED_HAX) {
            file_put_contents($cache_key, $html);
        }

        return $html;
    }

    private function build_tag_list(): string
    {
        global $database;

        //$tags_min = $this->get_tags_min();
        $tag_data = $database->get_all("SELECT tag,count FROM tags ORDER BY count DESC, tag ASC LIMIT 9");

        $html = "<table>";
        $n = 0;
        foreach ($tag_data as $row) {
            if ($n%3==0) {
                $html .= "<tr>";
            }
            $h_tag = html_escape($row['tag']);
            $link = $this->tag_link($row['tag']);
            $image = Image::by_random([$row['tag']]);
            if (is_null($image)) {
                continue;
            } // one of the popular tags has no images
            $thumb = $image->get_thumb_link();
            $tsize = get_thumbnail_size($image->width, $image->height);
            $html .= "<td><a href='$link'><img src='$thumb' style='height: {$tsize[1]}px; width: {$tsize[0]}px;'><br>$h_tag</a></td>\n";
            if ($n%3==2) {
                $html .= "</tr>";
            }
            $n++;
        }
        $html .= "</table>";

        return $html;
    }
    // }}}
    // blocks {{{
    private function add_related_block(Page $page, Image $image)
    {
        global $database, $config;

        $query = "
			SELECT t3.tag AS tag, t3.count AS calc_count, it3.tag_id
            FROM image_tags AS it1 -- Starting image's tags                
                INNER JOIN tags AS t1 ON t1.id = it1.tag_id AND t1.tag NOT LIKE 'tagme%'
                -- Get images with the same tags as the starting image
                INNER JOIN image_tags AS it2 ON it1.tag_id=it2.tag_id
                -- Get the tags from those other images except the same as the starting tags 
                INNER JOIN image_tags AS it3 ON it2.image_id=it3.image_id 
                LEFT JOIN image_tags it4 ON it4.image_id = it1.image_id AND it4.tag_id = it3.tag_id
                INNER JOIN tags AS t3 ON t3.id = it3.tag_id AND t3.tag NOT LIKE 'tagme%' 
			WHERE
				it1.image_id=:image_id
			GROUP BY it3.tag_id, t3.tag, t3.count
			ORDER BY calc_count DESC
			LIMIT :tag_list_length
		";
        $args = ["image_id" => $image->id, "tag_list_length" => $config->get_int('tag_list_length')];

        $tags = $database->get_all($query, $args);
        if (count($tags) > 0) {
            $this->theme->display_related_block($page, $tags);
        }
    }

    private function add_split_tags_block(Page $page, Image $image)
    {
        global $database;

        $query = "
			SELECT tags.tag, tags.count as calc_count
			FROM tags, image_tags
			WHERE tags.id = image_tags.tag_id
			AND image_tags.image_id = :image_id
			ORDER BY calc_count DESC
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
			SELECT tags.tag, tags.count as calc_count
			FROM tags, image_tags
			WHERE tags.id = image_tags.tag_id
			AND image_tags.image_id = :image_id
			ORDER BY calc_count DESC
		";
        $args = ["image_id"=>$image->id];

        $tags = $database->get_all($query, $args);
        if (count($tags) > 0) {
            $this->theme->display_related_block($page, $tags);
        }
    }

    private function add_popular_block(Page $page)
    {
        global $database, $config;

        $tags = $database->cache->get("popular_tags");
        if (empty($tags)) {
            $query = "
				SELECT tag, count as calc_count
				FROM tags
				WHERE count > 0
				ORDER BY count DESC
				LIMIT :popular_tag_list_length
				";
            $args = ["popular_tag_list_length"=>$config->get_int('popular_tag_list_length')];

            $tags = $database->get_all($query, $args);
            $database->cache->set("popular_tags", $tags, 600);
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
        global $database, $config;

        if (count($search) > 5) {
            return;
        }

        $wild_tags = $search;
        $str_search = Tag::implode($search);
        $related_tags = $database->cache->get("related_tags:$str_search");

        if (empty($related_tags)) {
            // $search_tags = array();

            $tag_id_array = [];
            $tags_ok = true;
            foreach ($wild_tags as $tag) {
                if ($tag[0] == "-" || strpos($tag, "tagme")===0) {
                    continue;
                }
                $tag = str_replace("*", "%", $tag);
                $tag = str_replace("?", "_", $tag);
                $tag_ids = $database->get_col("SELECT id FROM tags WHERE tag LIKE :tag AND count < 25000", ["tag" => $tag]);
                // $search_tags = array_merge($search_tags,
                //                  $database->get_col("SELECT tag FROM tags WHERE tag LIKE :tag", array("tag"=>$tag)));
                $tag_id_array = array_merge($tag_id_array, $tag_ids);
                $tags_ok = count($tag_ids) > 0;
                if (!$tags_ok) {
                    break;
                }
            }
            $tag_id_list = join(', ', $tag_id_array);

            if (count($tag_id_array) > 5 || count($tag_id_array) == 0) {
                return;
            }

            if ($tags_ok) {
                $query = "
					SELECT t2.tag AS tag, COUNT(it2.image_id) AS calc_count
					FROM image_tags AS it1 -- Got other images with the same tags
					    -- Get the tags from those images, except those the same as the starting tags
					    INNER JOIN image_tags AS it2 ON it1.image_id=it2.image_id AND it2.tag_id NOT IN($tag_id_list)
					    -- And filter out anything starting with tagme
						INNER JOIN tags AS t2 ON it2.tag_id = t2.id  AND t2.tag NOT LIKE 'tagme%'
					WHERE
						it1.tag_id IN($tag_id_list)
					GROUP BY t2.tag
					ORDER BY calc_count
					DESC LIMIT :limit
				";
                $args = ["limit"=>$config->get_int('tag_list_length')];

                $related_tags = $database->get_all($query, $args);
                $database->cache->set("related_tags:$str_search", $related_tags, 60*60);
            }
        }

        if (!empty($related_tags)) {
            $this->theme->display_refine_block($page, $related_tags, $wild_tags);
        }
    }
    // }}}
}
