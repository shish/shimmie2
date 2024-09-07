<?php

declare(strict_types=1);

namespace Shimmie2;

class Statistics extends Extension
{
    /** @var StatisticsTheme */
    protected Themelet $theme;
    /** @var String[] */
    private array $unlisted = ['anonymous', 'ghost', 'hellbanned'];

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page;
        if ($event->page_matches("stats") || $event->page_matches("stats/100")) {
            $base_href = get_base_href();
            $sitename = $config->get_string(SetupConfig::TITLE);
            $theme_name = $config->get_string(SetupConfig::THEME);
            $unlisted = "'".implode("','", $this->unlisted)."'";

            $limit = 10;
            if ($event->page_matches("stats/100")) {
                $limit = 100;
            }

            if (Extension::is_enabled(TagHistoryInfo::KEY)) {
                $tallies = $this->get_tag_stats($this->unlisted);
                arsort($tallies[0], SORT_NUMERIC);
                $stats = [];
                foreach ($tallies[0] as $name => $tag_diff) {
                    $entries = "";
                    if (isset($tallies[1][$name])) {
                        $entries = " <span class='tag_count' title='Total edits'>" . $tallies[1][$name] . "</span>";
                    }
                    $stats[$name] = "<span title='Tags changed (ignoring aliases)'>$tag_diff</span>$entries";
                }
                $tag_table = $this->theme->build_table($stats, "Taggers", "Top $limit taggers", $limit);
            } else {
                $tag_table = null;
            }

            $upload_tally = [];
            foreach ($this->get_upload_stats($unlisted) as $name) {
                array_key_exists($name, $upload_tally) ? $upload_tally[$name] += 1 : $upload_tally[$name] = 1;

            }
            arsort($upload_tally, SORT_NUMERIC);
            $upload_table = $this->theme->build_table($upload_tally, "Uploaders", "Top $limit uploaders", $limit);

            if (Extension::is_enabled(CommentListInfo::KEY)) {
                $comment_tally = [];
                foreach ($this->get_comment_stats($unlisted) as $name) {
                    array_key_exists($name, $comment_tally) ? $comment_tally[$name] += 1 : $comment_tally[$name] = 1;

                }
                arsort($comment_tally, SORT_NUMERIC);
                $comment_table = $this->theme->build_table($comment_tally, "Commenters", "Top $limit commenters", $limit);
            } else {
                $comment_table = null;
            }

            if (Extension::is_enabled(FavoritesInfo::KEY)) {
                $favorite_tally = [];
                foreach ($this->get_favorite_stats($unlisted) as $name) {
                    array_key_exists($name, $favorite_tally) ? $favorite_tally[$name] += 1 : $favorite_tally[$name] = 1;

                }
                arsort($favorite_tally, SORT_NUMERIC);
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
     * @param String[] $unlisted
     * @return array<array<string, int>>
     */
    private function get_tag_stats(array $unlisted): array
    {
        global $database;
        // Returns the username and tags from each tag history entry. This includes Anonymous tag histories to prevent their tagging being ignored and credited to the next user to edit.
        $tag_stats = $database->get_all("SELECT users.class,users.name,tag_histories.tags,tag_histories.image_id FROM tag_histories INNER JOIN users ON users.id = tag_histories.user_id WHERE 1=1 ORDER BY tag_histories.id;");

        // Group tag history entries by image id
        $tag_histories = [];
        foreach ($tag_stats as $ts) {
            $tag_history = ['class' => $ts['class'], 'name' => $ts['name'], 'tags' => $ts['tags']];
            $id = $ts['image_id'];
            array_key_exists($id, $tag_histories) ? array_push($tag_histories[$id], $tag_history) : $tag_histories[$id] = [$tag_history];
        }

        // Grab alias list so we can ignore those changes
        // While this strategy may discount some change made before those aliases were implemented, it is preferable over crediting the changes made by an alias to whoever edits the tags next.
        $alias_db = $database->get_all(
            "
					SELECT *
					FROM aliases
					WHERE 1=1
				"
        );
        $aliases = [];
        foreach ($alias_db as $alias) {
            $aliases[$alias['oldtag']] = $alias['newtag'];
        }

        // Count changes made in each tag history and tally tags for users
        $tag_tally = [];
        $change_tally = [];
        foreach ($tag_histories as $i => $image) {
            $prev = [];
            foreach ($image as $change) {
                $curr = explode(' ', $change['tags']);
                foreach ($curr as $i => $tag) {
                    if (array_key_exists($tag, $aliases)) {
                        $curr[$i] = $aliases[$tag];
                    }
                }
                if (!in_array($change['class'], $unlisted)) {
                    $name = (string)$change['name'];
                    if (!isset($tag_tally[$name])) {
                        $tag_tally[$name] = 0;
                        $change_tally[$name] = 0;
                    }
                    $tag_tally[$name] += count(array_diff($curr, $prev));
                    $change_tally[$name] += 1;
                }
                $prev = $curr;
            }
        }
        return [$tag_tally, $change_tally];
    }

    /**
     * @return array<string>
     */
    private function get_upload_stats(string $unlisted): array
    {
        global $database;
        // Returns the username of each post, as an array.
        return $database->get_col("SELECT users.name FROM images INNER JOIN users ON users.id = images.owner_id WHERE users.class NOT IN ($unlisted) ORDER BY users.id;");
    }

    /**
     * @return array<string>
     */
    private function get_comment_stats(string $unlisted): array
    {
        global $database;
        // Returns the username of each comment, as an array.
        return $database->get_col("SELECT users.name FROM comments INNER JOIN users ON users.id = comments.owner_id WHERE users.class NOT IN ($unlisted) ORDER BY users.id;");
    }

    /**
     * @return array<string>
     */
    private function get_favorite_stats(string $unlisted): array
    {
        global $database;
        // Returns the username of each favorite, as an array.
        return $database->get_col("SELECT users.name FROM user_favorites INNER JOIN users ON users.id = user_favorites.user_id WHERE users.class NOT IN ($unlisted) ORDER BY users.id;");
    }
}
