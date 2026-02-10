<?php

declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<StatisticsTheme> */
final class Statistics extends Extension
{
    public const KEY = "statistics";

    /** @var string[] */
    private array $unlisted = ['anonymous', 'ghost'];

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("stats") || $event->page_matches("stats/100")) {
            $unlisted = "'".implode("','", $this->unlisted)."'";

            $limit = 10;
            if ($event->page_matches("stats/100")) {
                $limit = 100;
            }

            if (TagHistoryInfo::is_enabled()) {
                $tallies = $this->get_tag_stats($this->unlisted);
                arsort($tallies[0], SORT_NUMERIC);
                $stats = [];
                foreach ($tallies[0] as $name => $tag_diff) {
                    $stats[$name] = $this->theme->build_tag_field($tallies[1][$name], $tag_diff);
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

            if (CommentListInfo::is_enabled()) {
                $comment_tally = [];
                foreach ($this->get_comment_stats($unlisted) as $name) {
                    array_key_exists($name, $comment_tally) ? $comment_tally[$name] += 1 : $comment_tally[$name] = 1;

                }
                arsort($comment_tally, SORT_NUMERIC);
                $comment_table = $this->theme->build_table($comment_tally, "Commenters", "Top $limit commenters", $limit);
            } else {
                $comment_table = null;
            }

            if (FavoritesInfo::is_enabled()) {
                $favorite_tally = [];
                foreach ($this->get_favorite_stats($unlisted) as $name) {
                    array_key_exists($name, $favorite_tally) ? $favorite_tally[$name] += 1 : $favorite_tally[$name] = 1;

                }
                arsort($favorite_tally, SORT_NUMERIC);
                $favorite_table = $this->theme->build_table($favorite_tally, "Favoriters", "Top $limit favoriters", $limit);
            } else {
                $favorite_table = null;
            }

            $this->theme->display_page($limit, $tag_table, $upload_table, $comment_table, $favorite_table);
        }
    }

    #[EventListener]
    public function onPageNavBuilding(PageNavBuildingEvent $event): void
    {
        $event->add_nav_link(make_link('stats'), "Stats", category: "stats");
    }

    #[EventListener]
    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "stats") {
            $event->add_nav_link(make_link('stats/100'), "Top 100");
        }
    }

    /**
     * @param String[] $unlisted
     * @return array<array<string, int>>
     */
    private function get_tag_stats(array $unlisted): array
    {
        // Grab alias list so we can ignore those changes
        // While this strategy may discount some change made before those aliases were implemented, it is preferable over crediting the changes made by an alias to whoever edits the tags next.
        $alias_db = Ctx::$database->get_all("SELECT * FROM aliases WHERE 1=1");
        $aliases = [];
        foreach ($alias_db as $alias) {
            $aliases[$alias['oldtag']] = $alias['newtag'];
        }

        // Returns the username and tags from each tag history entry. This includes Anonymous tag histories to prevent their tagging being ignored and credited to the next user to edit.
        $tag_stats = Ctx::$database->get_all("
            SELECT users.class,users.name,tag_histories.tags,tag_histories.image_id
            FROM tag_histories
            INNER JOIN users
                ON users.id = tag_histories.user_id
            WHERE 1=1
            ORDER BY tag_histories.image_id, tag_histories.id
        ");


        // Count changes made in each tag history and tally tags for users
        $tag_tally = [];
        $change_tally = [];
        $prev_image_id = 0;
        $prev = [];
        foreach ($tag_stats as $ts) {
            $name = (string)$ts['name'];
            $curr = explode(' ', $ts['tags']);
            foreach ($curr as $i => $tag) {
                if (array_key_exists($tag, $aliases)) {
                    $curr[$i] = $aliases[$tag];
                }
            }
            if ($prev_image_id === $ts['image_id']) {
                if (!in_array($ts['class'], $unlisted)) {
                    if (!isset($tag_tally[$name])) {
                        $tag_tally[$name] = 0;
                        $change_tally[$name] = 0;
                    }
                    $change_tally[$name] += 1;
                    $tag_tally[$name] += count(array_diff($curr, $prev));
                }
            } else {
                if (!in_array($ts['class'], $unlisted)) {
                    if (!isset($tag_tally[$name])) {
                        $tag_tally[$name] = 0;
                        $change_tally[$name] = 0;
                    }
                    $change_tally[$name] += 1;
                    $tag_tally[$name] += count($curr);
                }
                $prev_image_id = $ts['image_id'];
            }
            $prev = $curr;
        }

        return [$tag_tally, $change_tally];
    }

    /**
     * @return array<string>
     */
    private function get_upload_stats(string $unlisted): array
    {
        // Returns the username of each post, as an array.
        // @phpstan-ignore-next-line
        return Ctx::$database->get_col("SELECT users.name FROM images INNER JOIN users ON users.id = images.owner_id WHERE users.class NOT IN ($unlisted) ORDER BY users.id;");
    }

    /**
     * @return array<string>
     */
    private function get_comment_stats(string $unlisted): array
    {
        // Returns the username of each comment, as an array.
        // @phpstan-ignore-next-line
        return Ctx::$database->get_col("SELECT users.name FROM comments INNER JOIN users ON users.id = comments.owner_id WHERE users.class NOT IN ($unlisted) ORDER BY users.id;");
    }

    /**
     * @return array<string>
     */
    private function get_favorite_stats(string $unlisted): array
    {
        // Returns the username of each favorite, as an array.
        // @phpstan-ignore-next-line
        return Ctx::$database->get_col("SELECT users.name FROM user_favorites INNER JOIN users ON users.id = user_favorites.user_id WHERE users.class NOT IN ($unlisted) ORDER BY users.id;");
    }
}
