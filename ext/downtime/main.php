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
        $sb = $event->panel->create_new_block("Downtime");
        $sb->add_bool_option("downtime", "Disable non-admin access: ");
        $sb->add_longtext_option("downtime_message", "<br>");
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
