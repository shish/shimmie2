<?php

declare(strict_types=1);

namespace Shimmie2;

class LinkScan extends Extension
{
    /** @var LinkScanTheme */
    protected Themelet $theme;

    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $this->theme->display_form();
    }

    public function onAdminAction(AdminActionEvent $event): void
    {
        global $page;
        if($event->action == "link_scan") {
            $text = $_POST['text'];
            $ids = [];

            $matches = [];
            preg_match_all("/post\/view\/(\d+)/", $text, $matches);
            foreach($matches[1] as $match) {
                $ids[] = $match;
            }
            preg_match_all("/\b([0-9a-fA-F]{32})\b/", $text, $matches);
            foreach($matches[1] as $match) {
                $ids[] = Image::by_hash($match)->id;
            }

            $event->redirect = false;
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(search_link(["id=".implode(",", $ids)]));
        }
    }
}
