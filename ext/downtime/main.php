<?php

declare(strict_types=1);

namespace Shimmie2;

class Downtime extends Extension
{
    /** @var DowntimeTheme */
    protected Themelet $theme;

    public function get_priority(): int
    {
        return 10;
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $event->panel->add_config_group(new DowntimeConfig());
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page, $user;

        if ($config->get_bool("downtime")) {
            if (!$user->can(Permissions::IGNORE_DOWNTIME) && !$this->is_safe_page($event)) {
                $msg = $config->get_string("downtime_message");
                $this->theme->display_message($msg);
                if (!defined("UNITTEST")) {  // hax D:
                    header("HTTP/1.1 {$page->code} Downtime");
                    print($page->data);
                    exit;
                }
            }
            $this->theme->display_notification($page);
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
