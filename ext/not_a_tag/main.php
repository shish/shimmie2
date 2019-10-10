<?php

class NotATag extends Extension
{
    public function get_priority(): int
    {
        return 30;
    } // before ImageUploadEvent and tag_history

    public function onInitExt(InitExtEvent $event)
    {
        global $config, $database;
        if ($config->get_int("ext_notatag_version") < 1) {
            $database->create_table("untags", "
				tag VARCHAR(128) NOT NULL PRIMARY KEY,
				redirect VARCHAR(255) NOT NULL
			");
            $config->set_int("ext_notatag_version", 1);
        }
    }

    public function onImageAddition(ImageAdditionEvent $event)
    {
        $this->scan($event->image->get_tag_array());
    }

    public function onTagSet(TagSetEvent $event)
    {
        $this->scan($event->tags);
    }

    /**
     * #param string[] $tags_mixed
     */
    private function scan(array $tags_mixed)
    {
        global $database;

        $tags = [];
        foreach ($tags_mixed as $tag) {
            $tags[] = strtolower($tag);
        }

        $pairs = $database->get_all("SELECT * FROM untags");
        foreach ($pairs as $tag_url) {
            $tag = strtolower($tag_url[0]);
            $url = $tag_url[1];
            if (in_array($tag, $tags)) {
                header("Location: $url");
                exit; # FIXME: need a better way of aborting the tag-set or upload
            }
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event)
    {
        global $user;
        if($event->parent==="tags") {
            if ($user->can(Permissions::BAN_IMAGE)) {
                $event->add_nav_link("untags", new Link('untag/list/1'), "UnTags");
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event)
    {
        global $user;
        if ($user->can(Permissions::BAN_IMAGE)) {
            $event->add_link("UnTags", make_link("untag/list/1"));
        }
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $database, $page, $user;

        if ($event->page_matches("untag")) {
            if ($user->can(Permissions::BAN_IMAGE)) {
                if ($event->get_arg(0) == "add") {
                    $tag = $_POST["tag"];
                    $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : "DNP";

                    $database->Execute(
                        "INSERT INTO untags(tag, redirect) VALUES (?, ?)",
                        [$tag, $redirect]
                    );

                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect($_SERVER['HTTP_REFERER']);
                } elseif ($event->get_arg(0) == "remove") {
                    if (isset($_POST['tag'])) {
                        $database->Execute($database->scoreql_to_sql("DELETE FROM untags WHERE SCORE_STRNORM(tag) = SCORE_STRNORM(?)"), [$_POST['tag']]);

                        flash_message("Image ban removed");
                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect($_SERVER['HTTP_REFERER']);
                    }
                } elseif ($event->get_arg(0) == "list") {
                    $page_num = 0;
                    if ($event->count_args() == 2) {
                        $page_num = int_escape($event->get_arg(1));
                    }
                    $page_size = 100;
                    $page_count = ceil($database->get_one("SELECT COUNT(tag) FROM untags")/$page_size);
                    $this->theme->display_untags($page, $page_num, $page_count, $this->get_untags($page_num, $page_size));
                }
            }
        }
    }

    public function get_untags(int $page, int $size=100): array
    {
        global $database;

        // FIXME: many
        $size_i = int_escape($size);
        $offset_i = int_escape($page-1)*$size_i;
        $where = ["(1=1)"];
        $args = [];
        if (!empty($_GET['tag'])) {
            $where[] = 'tag SCORE_ILIKE ?';
            $args[] = "%".$_GET['tag']."%";
        }
        if (!empty($_GET['redirect'])) {
            $where[] = 'redirect SCORE_ILIKE ?';
            $args[] = "%".$_GET['redirect']."%";
        }
        $where = implode(" AND ", $where);
        $bans = $database->get_all($database->scoreql_to_sql("
			SELECT *
			FROM untags
			WHERE $where
			ORDER BY tag
			LIMIT $size_i
			OFFSET $offset_i
			"), $args);
        if ($bans) {
            return $bans;
        } else {
            return [];
        }
    }
}
