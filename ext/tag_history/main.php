<?php declare(strict_types=1);

class TagHistory extends Extension
{
    /** @var TagHistoryTheme */
    protected $theme;

    // in before tags are actually set, so that "get current tags" works
    public function get_priority(): int
    {
        return 40;
    }

    public function onInitExt(InitExtEvent $event)
    {
        global $config;
        $config->set_default_int("history_limit", -1);
    }

    public function onAdminBuilding(AdminBuildingEvent $event)
    {
        $this->theme->display_admin_block();
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $user;

        if ($event->page_matches("tag_history/revert")) {
            // this is a request to revert to a previous version of the tags
            if ($user->can(Permissions::EDIT_IMAGE_TAG)) {
                if (isset($_POST['revert'])) {
                    $this->process_revert_request((int)$_POST['revert']);
                }
            }
        } elseif ($event->page_matches("tag_history/bulk_revert")) {
            if ($user->can(Permissions::BULK_EDIT_IMAGE_TAG) && $user->check_auth_token()) {
                $this->process_bulk_revert_request();
            }
        } elseif ($event->page_matches("tag_history/all")) {
            $page_id = int_escape($event->get_arg(0));
            $this->theme->display_global_page($page, $this->get_global_tag_history($page_id), $page_id);
        } elseif ($event->page_matches("tag_history") && $event->count_args() == 1) {
            // must be an attempt to view a tag history
            $image_id = int_escape($event->get_arg(0));
            $this->theme->display_history_page($page, $image_id, $this->get_tag_history_from_id($image_id));
        }
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event)
    {
        $event->add_part("
			<form action='".make_link("tag_history/{$event->image->id}")."' method='GET'>
				<input type='submit' value='View Tag History'>
			</form>
		", 20);
    }

    /*
    // disk space is cheaper than manually rebuilding history,
    // so let's default to -1 and the user can go advanced if
    // they /really/ want to
    public function onSetupBuilding(SetupBuildingEvent $event) {
        $sb = $event->panel->create_new_block("Tag History");
        $sb->add_label("Limit to ");
        $sb->add_int_option("history_limit");
        $sb->add_label(" entires per image");
        $sb->add_label("<br>(-1 for unlimited)");
    }
    */

    public function onTagSet(TagSetEvent $event)
    {
        $this->add_tag_history($event->image, $event->tags);
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event)
    {
        global $user;
        if ($event->parent==="system") {
            if ($user->can(Permissions::BULK_EDIT_IMAGE_TAG)) {
                $event->add_nav_link("tag_history", new Link('tag_history/all/1'), "Tag Changes", NavLink::is_active(["tag_history"]));
            }
        }
    }


    public function onUserBlockBuilding(UserBlockBuildingEvent $event)
    {
        global $user;
        if ($user->can(Permissions::BULK_EDIT_IMAGE_TAG)) {
            $event->add_link("Tag Changes", make_link("tag_history/all/1"));
        }
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event)
    {
        global $database;

        if ($this->get_version("ext_tag_history_version") < 1) {
            $database->create_table("tag_histories", "
	    		id SCORE_AIPK,
	    		image_id INTEGER NOT NULL,
				user_id INTEGER NOT NULL,
				user_ip SCORE_INET NOT NULL,
	    		tags TEXT NOT NULL,
				date_set TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
				FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
			");
            $database->execute("CREATE INDEX tag_histories_image_id_idx ON tag_histories(image_id)", []);
            $this->set_version("ext_tag_history_version", 3);
        }

        if ($this->get_version("ext_tag_history_version") == 1) {
            $database->execute("ALTER TABLE tag_histories ADD COLUMN user_id INTEGER NOT NULL");
            $database->execute("ALTER TABLE tag_histories ADD COLUMN date_set TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
            $this->set_version("ext_tag_history_version", 2);
        }

        if ($this->get_version("ext_tag_history_version") == 2) {
            $database->execute("ALTER TABLE tag_histories ADD COLUMN user_ip CHAR(15) NOT NULL");
            $this->set_version("ext_tag_history_version", 3);
        }
    }

    /**
     * This function is called when a revert request is received.
     */
    private function process_revert_request(int $revert_id)
    {
        global $page;

        // check for the nothing case
        if ($revert_id < 1) {
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link());
            return;
        }

        // lets get this revert id assuming it exists
        $result = $this->get_tag_history_from_revert($revert_id);

        if (empty($result)) {
            // there is no history entry with that id so either the image was deleted
            // while the user was viewing the history, someone is playing with form
            // variables or we have messed up in code somewhere.
            /* FIXME: calling die() is probably not a good idea, we should throw an Exception */
            die("Error: No tag history with specified id was found.");
        }

        // lets get the values out of the result
        $stored_image_id = (int)$result['image_id'];
        $stored_tags = $result['tags'];

        $image = Image::by_id($stored_image_id);
        if (! $image instanceof Image) {
            throw new ImageDoesNotExist("Error: cannot find any image with the ID = ". $stored_image_id);
        }

        log_debug("tag_history", 'Reverting tags of >>'.$stored_image_id.' to ['.$stored_tags.']');
        // all should be ok so we can revert by firing the SetUserTags event.
        send_event(new TagSetEvent($image, Tag::explode($stored_tags)));

        // all should be done now so redirect the user back to the image
        $page->set_mode(PageMode::REDIRECT);
        $page->set_redirect(make_link('post/view/'.$stored_image_id));
    }

    protected function process_bulk_revert_request()
    {
        if (isset($_POST['revert_name']) && !empty($_POST['revert_name'])) {
            $revert_name = $_POST['revert_name'];
        } else {
            $revert_name = null;
        }

        if (isset($_POST['revert_ip']) && !empty($_POST['revert_ip'])) {
            $revert_ip = filter_var($_POST['revert_ip'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE);

            if ($revert_ip === false) {
                // invalid ip given.
                $this->theme->display_admin_block('Invalid IP');
                return;
            }
        } else {
            $revert_ip = null;
        }

        if (isset($_POST['revert_date']) && !empty($_POST['revert_date'])) {
            if (isValidDate($_POST['revert_date'])) {
                $revert_date = addslashes($_POST['revert_date']); // addslashes is really unnecessary since we just checked if valid, but better safe.
            } else {
                $this->theme->display_admin_block('Invalid Date');
                return;
            }
        } else {
            $revert_date = null;
        }

        set_time_limit(0); // reverting changes can take a long time, disable php's timelimit if possible.

        // Call the revert function.
        $this->process_revert_all_changes($revert_name, $revert_ip, $revert_date);
        // output results
        $this->theme->display_revert_ip_results();
    }

    public function get_tag_history_from_revert(int $revert_id): ?array
    {
        global $database;
        $row = $database->get_row("
				SELECT tag_histories.*, users.name
				FROM tag_histories
				JOIN users ON tag_histories.user_id = users.id
				WHERE tag_histories.id = :id", ["id"=>$revert_id]);
        return ($row ? $row : null);
    }

    public function get_tag_history_from_id(int $image_id): array
    {
        global $database;
        return $database->get_all(
            "
				SELECT tag_histories.*, users.name
				FROM tag_histories
				JOIN users ON tag_histories.user_id = users.id
				WHERE image_id = :id
				ORDER BY tag_histories.id DESC",
            ["id"=>$image_id]
        );
    }

    public function get_global_tag_history(int $page_id): array
    {
        global $database;
        return $database->get_all("
				SELECT tag_histories.*, users.name
				FROM tag_histories
				JOIN users ON tag_histories.user_id = users.id
				ORDER BY tag_histories.id DESC
				LIMIT 100 OFFSET :offset
		", ["offset" => ($page_id-1)*100]);
    }

    /**
     * This function attempts to revert all changes by a given IP within an (optional) timeframe.
     */
    public function process_revert_all_changes(?string $name, ?string $ip, ?string $date)
    {
        global $database;

        $select_code = [];
        $select_args = [];

        if (!is_null($name)) {
            $duser = User::by_name($name);
            if (is_null($duser)) {
                $this->theme->add_status($name, "user not found");
                return;
            } else {
                $select_code[] = 'user_id = :user_id';
                $select_args['user_id'] = $duser->id;
            }
        }

        if (!is_null($ip)) {
            $select_code[] = 'user_ip = :user_ip';
            $select_args['user_ip'] = $ip;
        }

        if (!is_null($date)) {
            $select_code[] = 'date_set >= :date_set';
            $select_args['date_set'] = $date;
        }

        if (count($select_code) == 0) {
            log_error("tag_history", "Tried to mass revert without any conditions");
            return;
        }

        log_info("tag_history", 'Attempting to revert edits where '.implode(" and ", $select_code)." (".implode(" / ", $select_args).")");

        // Get all the images that the given IP has changed tags on (within the timeframe) that were last edited by the given IP
        $result = $database->get_col('
				SELECT t1.image_id
				FROM tag_histories t1
				LEFT JOIN tag_histories t2 ON (t1.image_id = t2.image_id AND t1.date_set < t2.date_set)
				WHERE t2.image_id IS NULL
				AND t1.image_id IN ( select image_id from tag_histories where '.implode(" AND ", $select_code).')
				ORDER BY t1.image_id
		', $select_args);

        foreach ($result as $image_id) {
            // Get the first tag history that was done before the given IP edit
            $row = $database->get_row('
				SELECT id, tags
				FROM tag_histories
				WHERE image_id='.$image_id.'
				AND NOT ('.implode(" AND ", $select_code).')
				ORDER BY date_set DESC LIMIT 1
			', $select_args);

            if (!empty($row)) {
                $revert_id = (int)$row['id'];
                $result = $this->get_tag_history_from_revert($revert_id);

                if (empty($result)) {
                    // there is no history entry with that id so either the image was deleted
                    // while the user was viewing the history,  or something messed up
                    /* calling die() is probably not a good idea, we should throw an Exception */
                    die('Error: No tag history with specified id ('.$revert_id.') was found in the database.'."\n\n".
                        'Perhaps the image was deleted while processing this request.');
                }

                // lets get the values out of the result
                $stored_result_id = (int)$result['id'];
                $stored_image_id = (int)$result['image_id'];
                $stored_tags = $result['tags'];

                $image = Image::by_id($stored_image_id);
                if (! $image instanceof Image) {
                    continue;
                    //throw new ImageDoesNotExist("Error: cannot find any image with the ID = ". $stored_image_id);
                }

                log_debug("tag_history", 'Reverting tags of >>'.$stored_image_id.' to ['.$stored_tags.']');
                // all should be ok so we can revert by firing the SetTags event.
                send_event(new TagSetEvent($image, Tag::explode($stored_tags)));
                $this->theme->add_status('Reverted Change', 'Reverted >>'.$image_id.' to Tag History #'.$stored_result_id.' ('.$row['tags'].')');
            }
        }

        log_info("tag_history", 'Reverted '.count($result).' edits.');
    }

    /**
     * This function is called just before an images tag are changed.
     *
     * #param string[] $tags
     */
    private function add_tag_history(Image $image, array $tags)
    {
        global $database, $config, $user;

        $new_tags = Tag::implode($tags);
        $old_tags = $image->get_tag_list();

        if ($new_tags == $old_tags) {
            return;
        }

        if (empty($old_tags)) {
            /* no old tags, so we are probably adding the image for the first time */
            log_debug("tag_history", "adding new tag history: [$new_tags]");
        } else {
            log_debug("tag_history", "adding tag history: [$old_tags] -> [$new_tags]");
        }

        $allowed = $config->get_int("history_limit");
        if ($allowed == 0) {
            return;
        }

        // if the image has no history, make one with the old tags
        $entries = $database->get_one("SELECT COUNT(*) FROM tag_histories WHERE image_id = :id", ["id"=>$image->id]);
        if ($entries == 0 && !empty($old_tags)) {
            $database->execute(
                "
				INSERT INTO tag_histories(image_id, tags, user_id, user_ip, date_set)
				VALUES (:image_id, :tags, :user_id, :user_ip, now())",
                ["image_id"=>$image->id, "tags"=>$old_tags, "user_id"=>$config->get_int('anon_id'), "user_ip"=>'127.0.0.1']
            );
            $entries++;
        }

        // add a history entry
        $database->execute(
            "
				INSERT INTO tag_histories(image_id, tags, user_id, user_ip, date_set)
				VALUES (:image_id, :tags, :user_id, :user_ip, now())",
            ["image_id"=>$image->id, "tags"=>$new_tags, "user_id"=>$user->id, "user_ip"=>$_SERVER['REMOTE_ADDR']]
        );
        $entries++;

        // if needed remove oldest one
        if ($allowed == -1) {
            return;
        }
        if ($entries > $allowed) {
            // TODO: Make these queries better
            /*
                MySQL does NOT allow you to modify the same table which you use in the SELECT part.
                Which means that these will probably have to stay as TWO separate queries...

                https://dev.mysql.com/doc/refman/5.1/en/subquery-restrictions.html
                https://stackoverflow.com/questions/45494/mysql-error-1093-cant-specify-target-table-for-update-in-from-clause
            */
            $min_id = $database->get_one("SELECT MIN(id) FROM tag_histories WHERE image_id = :image_id", ["image_id"=>$image->id]);
            $database->execute("DELETE FROM tag_histories WHERE id = :id", ["id"=>$min_id]);
        }
    }
}
