<?php

declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<DowntimeTheme> */
final class Downtime extends Extension
{
    public const KEY = "downtime";

    #[EventListener(priority: 10)]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if (Ctx::$config->get(DowntimeConfig::DOWNTIME)) {
            if (!Ctx::$user->can(DowntimePermission::IGNORE_DOWNTIME) && !$this->is_safe_page($event)) {
                $msg = Ctx::$config->get(DowntimeConfig::MESSAGE);
                $this->theme->display_message($msg);
                if (!defined("UNITTEST")) {  // hax D:
                    $page = Ctx::$page;
                    header("HTTP/1.1 {$page->code} Downtime");
                    print($page->data);
                    exit;
                }
            }
            $this->theme->display_notification();
        }
    }

    private function is_safe_page(PageRequestEvent $event): bool
    {
        if ($event->page_matches("user_admin/login")) {
            return true;
        } else {
            return false;
        }
    }
}
