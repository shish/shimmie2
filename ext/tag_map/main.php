<?php

declare(strict_types=1);

namespace Shimmie2;

class TagMap extends Extension
{
    /** @var TagMapTheme */
    protected Themelet $theme;

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_int(TagMapConfig::TAGS_MIN, 3);
        $config->set_default_bool(TagMapConfig::PAGES, false);
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page;

        if ($event->page_matches("tags/{sub}", method: "GET")) {
            $sub = $event->get_arg('sub');

            if ($event->get_GET('starts_with')) {
                $starts_with = $event->get_GET('starts_with') . "%";
            } else {
                if ($config->get_bool(TagMapConfig::PAGES)) {
                    $starts_with = "a%";
                } else {
                    $starts_with = "%";
                }
            }

            if ($event->get_GET('mincount')) {
                $tags_min = int_escape($event->get_GET('mincount'));
            } else {
                global $config;
                $tags_min = $config->get_int(TagMapConfig::TAGS_MIN);	// get the default.
            }

            match ($sub) {
                'map' => $this->theme->display_map($tags_min, $this->get_map_data($starts_with, $tags_min)),
                'alphabetic' => $this->theme->display_alphabetic($starts_with, $tags_min, $this->get_alphabetic_data($starts_with, $tags_min)),
                'popularity' => $this->theme->display_popularity($this->get_popularity_data($tags_min)),
                default => null,
            };
        } elseif ($event->page_matches("tags")) {
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("tags/map"));
        }
    }

    public function onPageNavBuilding(PageNavBuildingEvent $event): void
    {
        $event->add_nav_link("tags", new Link('tags/map'), "Tags");
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent == "tags") {
            $event->add_nav_link("tags_map", new Link('tags/map'), "Map");
            $event->add_nav_link("tags_alphabetic", new Link('tags/alphabetic'), "Alphabetic");
            $event->add_nav_link("tags_popularity", new Link('tags/popularity'), "Popularity");
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Tag Map Options");
        $sb->add_int_option(TagMapConfig::TAGS_MIN, "Only show tags used at least ");
        $sb->add_label(" times");
        $sb->add_bool_option(TagMapConfig::PAGES, "<br>Paged tag lists: ");
    }

    /**
     * @return array<array{tag:string,scaled:float}>
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
            AND LOWER(tag) LIKE LOWER(:starts_with)
            ORDER BY LOWER(tag)
        ", ["tags_min" => $tags_min, "starts_with" => $starts_with]);
    }

    /**
     * @return array<array{tag:string,count:int}>
     */
    private function get_alphabetic_data(string $starts_with, int $tags_min): array
    {
        global $config, $database;

        return $database->get_pairs("
            SELECT tag, count
            FROM tags
            WHERE count >= :tags_min
            AND LOWER(tag) LIKE LOWER(:starts_with)
            ORDER BY LOWER(tag)
        ", ["tags_min" => $tags_min, "starts_with" => $starts_with]);
    }

    /**
     * @return array<array{tag:string,count:int,scaled:float}>
     */
    private function get_popularity_data(int $tags_min): array
    {
        global $config, $database;

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
