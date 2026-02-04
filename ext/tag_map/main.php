<?php

declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<TagMapTheme> */
final class TagMap extends Extension
{
    public const KEY = "tag_map";

    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("tags/{sub}", method: "GET")) {
            $sub = $event->get_arg('sub');

            if ($event->GET->get('starts_with')) {
                $starts_with = $event->GET->get('starts_with') . "%";
            } else {
                if (Ctx::$config->get(TagMapConfig::PAGES)) {
                    $starts_with = "a%";
                } else {
                    $starts_with = "%";
                }
            }

            if ($event->GET->get('mincount')) {
                $tags_min = int_escape($event->GET->get('mincount'));
            } else {
                $tags_min = Ctx::$config->get(TagMapConfig::TAGS_MIN);
            }

            match ($sub) {
                'map' => $this->theme->display_map($tags_min, $this->get_map_data($starts_with, $tags_min)),
                'alphabetic' => $this->theme->display_alphabetic($starts_with, $tags_min, $this->get_alphabetic_data($starts_with, $tags_min)),
                'popularity' => $this->theme->display_popularity($this->get_popularity_data($tags_min)),
                default => null,
            };
        } elseif ($event->page_matches("tags")) {
            Ctx::$page->set_redirect(make_link("tags/map"));
        }
    }

    public function onPageNavBuilding(PageNavBuildingEvent $event): void
    {
        $event->add_nav_link(make_link('tags/map'), "Tags", "tags");
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "tags") {
            $event->add_nav_link(make_link('tags/map'), "Map", "map");
            $event->add_nav_link(make_link('tags/alphabetic'), "Alphabetic", "alphabetic");
            $event->add_nav_link(make_link('tags/popularity'), "Popularity", "popularity");
        }
    }

    /**
     * @return array<array{tag:tag-string,scaled:float}>
     */
    private function get_map_data(string $starts_with, int $tags_min): array
    {
        global $database;

        return $database->get_all("
            SELECT
                tag,
                FLOOR(LN(LN(count - :tags_min + 1)+1)*1.5*100)/100 AS scaled
            FROM tags
            WHERE count >= :tags_min
            AND SCORE_ILIKE(tag, :starts_with)
            ORDER BY LOWER(tag)
        ", ["tags_min" => $tags_min, "starts_with" => $starts_with]);
    }

    /**
     * @return array<array{tag:tag-string,count:int}>
     */
    private function get_alphabetic_data(string $starts_with, int $tags_min): array
    {
        global $database;

        return $database->get_pairs("
            SELECT tag, count
            FROM tags
            WHERE count >= :tags_min
            AND SCORE_ILIKE(tag, :starts_with)
            ORDER BY LOWER(tag)
        ", ["tags_min" => $tags_min, "starts_with" => $starts_with]);
    }

    /**
     * @return array<array{tag:tag-string,count:int,scaled:float}>
     */
    private function get_popularity_data(int $tags_min): array
    {
        global $database;

        // Make sure that the value of $tags_min is at least 1.
        // Otherwise the database will complain if you try to do: LOG(0)
        if ($tags_min < 1) {
            $tags_min = 1;
        }

        return $database->get_all("
            SELECT tag, count, FLOOR(LOG(10, count)) AS scaled
            FROM tags
            WHERE count >= :tags_min
            ORDER BY count DESC, tag ASC
        ", ["tags_min" => $tags_min]);
    }
}
