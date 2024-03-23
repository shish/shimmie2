<?php

declare(strict_types=1);

namespace Shimmie2;

class Statistics extends Extension
{
    /** @var StatisticsTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page;
        if ($event->page_matches("stats") || $event->page_matches("stats/100")) {
            $base_href = get_base_href();
            $sitename = $config->get_string(SetupConfig::TITLE);
            $theme_name = $config->get_string(SetupConfig::THEME);
            $anon_id = $config->get_int("anon_id");

            $limit = 10;
            if ($event->page_matches("stats/100")) {
                $limit = 100;
            }

            if (Extension::is_enabled(TagHistoryInfo::KEY)) {
                $tag_tally = $this->get_tag_stats($anon_id);
                arsort($tag_tally, $flags = SORT_NUMERIC);
                $tag_table = $this->theme->build_table($tag_tally, "Taggers", "Top $limit taggers", $limit);
            } else {
                $tag_table = null;
            }

            $upload_tally = [];
            foreach ($this->get_upload_stats($anon_id) as $name) {
                array_key_exists($name, $upload_tally) ? $upload_tally[$name] += 1 : $upload_tally[$name] = 1;

            }
            arsort($upload_tally, $flags = SORT_NUMERIC);
            $upload_table = $this->theme->build_table($upload_tally, "Uploaders", "Top $limit uploaders", $limit);

            if (Extension::is_enabled(CommentListInfo::KEY)) {
                $comment_tally = [];
                foreach ($this->get_comment_stats($anon_id) as $name) {
                    array_key_exists($name, $comment_tally) ? $comment_tally[$name] += 1 : $comment_tally[$name] = 1;

                }
                arsort($comment_tally, $flags = SORT_NUMERIC);
                $comment_table = $this->theme->build_table($comment_tally, "Commenters", "Top $limit commenters", $limit);
            } else {
                $comment_table = null;
            }

            if (Extension::is_enabled(FavoritesInfo::KEY)) {
                $favorite_tally = [];
                foreach ($this->get_favorite_stats($anon_id) as $name) {
                    array_key_exists($name, $favorite_tally) ? $favorite_tally[$name] += 1 : $favorite_tally[$name] = 1;

                }
                arsort($favorite_tally, $flags = SORT_NUMERIC);
                $favorite_table = $this->theme->build_table($favorite_tally, "Favoriters", "Top $limit favoriters", $limit);
            } else {
                $favorite_table = null;
            }

            $this->theme->display_page($page, $limit, $tag_table, $upload_table, $comment_table, $favorite_table);
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Stats Page");
        $sb->add_longtext_option("stats_text", "<br>Page Text:<br>");
    }

    public function onPageNavBuilding(PageNavBuildingEvent $event): void
    {
        $event->add_nav_link("stats", new Link('stats'), "Stats");
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent == "stats") {
            $event->add_nav_link("stats_100", new Link('stats/100'), "Top 100");
        }
    }

    /**
     * @return array<string, int>
     */
    private function get_tag_stats(int $anon_id): array
    {
        global $database;
        // Returns the username and tags from each tag history entry. Excludes Anonymous
        $tag_stats = $database->get_all("SELECT users.name,tag_histories.tags,tag_histories.image_id FROM tag_histories INNER JOIN users ON users.id = tag_histories.user_id WHERE tag_histories.user_id <> $anon_id;");

        // Group tag history entries by image id
        $tag_histories = [];
        foreach ($tag_stats as $ts) {
            $tag_history = ['name' => $ts['name'], 'tags' => $ts['tags']];
            $id = $ts['image_id'];
            array_key_exists($id, $tag_histories) ? array_push($tag_histories[$id], $tag_history) : $tag_histories[$id] = [$tag_history];
        }
        // Count changes made in each tag history and tally tags for users
        $tag_tally = [];
        foreach ($tag_histories as $i => $image) {
            $first = true;
            $prev = [];
            foreach ($image as $change) {
                $curr = explode(' ', $change['tags']);
                $name = (string)$change['name'];
                $tag_tally[$name] += count(array_diff($curr, $prev));
                $prev = $curr;
            }
        }
        return $tag_tally;
    }

    /**
     * @return array<string>
     */
    private function get_upload_stats(int $anon_id): array
    {
        global $database;
        // Returns the username of each post, as an array. Excludes Anonymous
        return $database->get_col("SELECT users.name FROM images INNER JOIN users ON users.id = images.owner_id WHERE images.owner_id <> $anon_id;");
    }

    /**
     * @return array<string>
     */
    private function get_comment_stats(int $anon_id): array
    {
        global $database;
        // Returns the username of each comment, as an array. Excludes Anonymous
        return $database->get_col("SELECT users.name FROM comments INNER JOIN users ON users.id = comments.owner_id WHERE comments.owner_id <> $anon_id;");
    }

    /**
     * @return array<string>
     */
    private function get_favorite_stats(int $anon_id): array
    {
        global $database;
        // Returns the username of each favorite, as an array. Excludes Anonymous
        return $database->get_col("SELECT users.name FROM user_favorites INNER JOIN users ON users.id = user_favorites.user_id WHERE user_favorites.user_id <> $anon_id;");
    }
}
