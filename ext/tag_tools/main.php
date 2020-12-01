<?php /** @noinspection PhpUnusedPrivateMethodInspection */
declare(strict_types=1);

class TagTools extends Extension
{
    /** @var TagToolsTheme */
    protected $theme;

    public function onPageRequest(PageRequestEvent $event)
    {
        global $database, $page, $user;

        if ($event->page_matches("admin")) {
            if (!$user->can(Permissions::MANAGE_ADMINTOOLS)) {
                $this->theme->display_permission_denied();
            } else {
                if ($event->count_args() == 0) {
                    send_event(new AdminBuildingEvent($page));
                } else {
                    $action = $event->get_arg(0);
                    $aae = new AdminActionEvent($action);

                    if ($user->check_auth_token()) {
                        log_info("admin", "Util: $action");
                        set_time_limit(0);
                        $database->set_timeout(300000);
                        send_event($aae);
                    }

                    if ($aae->redirect) {
                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(make_link("admin"));
                    }
                }
            }
        }
    }

    public function onAdminBuilding(AdminBuildingEvent $event)
    {
        $this->theme->display_form();
    }

    public function onAdminAction(AdminActionEvent $event)
    {
        $action = $event->action;
        if (method_exists($this, $action)) {
            $event->redirect = $this->$action();
        }
    }

    private function set_tag_case()
    {
        global $database;
        $database->execute(
            "UPDATE tags SET tag=:tag1 WHERE LOWER(tag) = LOWER(:tag2)",
            ["tag1" => $_POST['tag'], "tag2" => $_POST['tag']]
        );
        log_info("admin", "Fixed the case of {$_POST['tag']}", "Fixed case");
        return true;
    }

    private function lowercase_all_tags()
    {
        global $database;
        $database->execute("UPDATE tags SET tag=lower(tag)");
        log_warning("admin", "Set all tags to lowercase", "Set all tags to lowercase");
        return true;
    }

    private function recount_tag_use()
    {
        global $database;
        $database->execute("
			UPDATE tags
			SET count = COALESCE(
				(SELECT COUNT(image_id) FROM image_tags WHERE tag_id=tags.id GROUP BY tag_id),
				0
			)
		");
        $database->execute("DELETE FROM tags WHERE count=0");
        log_warning("admin", "Re-counted tags", "Re-counted tags");
        return true;
    }
}
