<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\emptyHTML;

/** @extends Extension<BoneQualityTheme> */
final class BoneQuality extends Extension
{
    public const KEY = "bone_quality";

    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("bone_quality")) {
            $boned = false;
            $results = emptyHTML();

            // count how many of each chore searches is above the chore search threshold
            $chore_threshold = Ctx::$config->get(BoneQualityConfig::CHORE_THRESHOLD);
            // split on any line break (\n, \r, \r\n) because browsers can theoretically send any of them
            $chore_searches = preg_split("/\R/", Ctx::$config->get(BoneQualityConfig::CHORE_SEARCHES));
            if ($chore_searches) {
                foreach ($chore_searches as $search) {
                    $search_boned = false;
                    $search_count = Search::count_images(SearchTerm::explode($search));
                    if ($search_count >= $chore_threshold) {
                        $boned = true;
                        $search_boned = true;
                    }
                    $results->appendChild($this->theme->generate_chore_search_html($search, $search_boned, $search_count));
                }
            }

            $failure_string = Ctx::$config->get(BoneQualityConfig::FAILURE_STRING);
            $this->theme->display_page($failure_string, $boned, $results);
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "stats") {
            $failure_string = Ctx::$config->get(BoneQualityConfig::FAILURE_STRING);
            $event->add_nav_link(make_link('bone_quality'), "how $failure_string are we?", "bone_quality");
        }
    }
}
