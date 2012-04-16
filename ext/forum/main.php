<?php
/**
 * Name: [Beta] Forum
 * Author: Sein Kraft <mail@seinkraft.info>
 *         Alpha <alpha@furries.com.ar>
 * License: GPLv2
 * Description: Rough forum extension
 * Documentation:
 */

class Forum extends Extension {
	public function onInitExt(InitExtEvent $event) {
		global $config, $database;

		// shortcut to latest

		if ($config->get_int("forum_version") < 1) {
			$database->create_table("forum_threads", "
					id SCORE_AIPK,
					sticky SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N,
					title VARCHAR(255) NOT NULL,
					user_id INTEGER NOT NULL,
					date DATETIME NOT NULL,
					uptodate DATETIME NOT NULL,
					INDEX (date),
					FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
					");

			$database->create_table("forum_posts", "
					id SCORE_AIPK,
					thread_id INTEGER NOT NULL,
					user_id INTEGER NOT NULL,
					date DATETIME NOT NULL,
					message TEXT,
					INDEX (date),
					FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
					FOREIGN KEY (thread_id) REFERENCES forum_threads (id) ON UPDATE CASCADE ON DELETE CASCADE
					");

			$config->set_int("forum_version", 2);
			$config->set_int("forumTitleSubString", 25);
			$config->set_int("forumThreadsPerPage", 15);
			$config->set_int("forumPostsPerPage", 15);

			$config->set_int("forumMaxCharsPerPost", 512);

			log_info("forum", "extension installed");
		}
		if ($config->get_int("forum_version") < 2) {
			$database->execute("ALTER TABLE forum_threads ADD FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT");
			$database->execute("ALTER TABLE forum_posts ADD FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT");
			$config->set_int("forum_version", 2);
		}
	}
	
	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Forum");
		$sb->add_int_option("forumTitleSubString", "Title max long: ");
		$sb->add_int_option("forumThreadsPerPage", "<br>Threads per page: ");
		$sb->add_int_option("forumPostsPerPage", "<br>Posts per page: ");
		
		$sb->add_int_option("forumMaxCharsPerPost", "<br>Max chars per post: ");
		$event->panel->add_block($sb);
	}
	
	public function onUserPageBuilding(UserPageBuildingEvent $event) {
		global $page, $user, $database;
		
		$threads_count = $database->get_one("SELECT COUNT(*) FROM forum_threads WHERE user_id=?", array($event->display_user->id));
        $posts_count = $database->get_one("SELECT COUNT(*) FROM forum_posts WHERE user_id=?", array($event->display_user->id));
			
        $days_old = ((time() - strtotime($event->display_user->join_date)) / 86400) + 1;
				
        $threads_rate = sprintf("%.1f", ($threads_count / $days_old));
		$posts_rate = sprintf("%.1f", ($posts_count / $days_old));
				
		$event->add_stats("Forum threads: $threads_count, $threads_rate per day");
        $event->add_stats("Forum posts: $posts_count, $posts_rate per day");
	}


	public function onPageRequest(PageRequestEvent $event) {
            global $page, $user;
            
            if($event->page_matches("forum")) {
                switch($event->get_arg(0)) {
                    case "index":
                    {
                        $this->show_last_threads($page, $event, $user->is_admin());
                        if(!$user->is_anonymous()) $this->theme->display_new_thread_composer($page);
                        break;
                    }
                    case "view":
                    {
                        $threadID = (int)($event->get_arg(1));
                        $pageNumber = (int)($event->get_arg(2));

                        $this->show_posts($event, $user->is_admin());
                        if($user->is_admin()) $this->theme->add_actions_block($page, $threadID);
                        if(!$user->is_anonymous()) $this->theme->display_new_post_composer($page, $threadID);
                        break;
                    }
                    case "new":
                    {
						global $page;
                        $this->theme->display_new_thread_composer($page);
                        break;
                    }
                    case "create":
                    {
                        $redirectTo = "forum/index";
                        if (!$user->is_anonymous())
                        {
                            list($hasErrors, $errors) = $this->valid_values_for_new_thread();

                            if($hasErrors)
                            {
                                $this->theme->display_error(500, "Error", $errors);
                                $this->theme->display_new_thread_composer($page, $_POST["message"], $_POST["title"], false);
                                break;
                            }

                            $newThreadID = $this->save_new_thread($user);
                            $this->save_new_post($newThreadID, $user);
                            $redirectTo = "forum/view/".$newThreadID."/1";
                        }

                        $page->set_mode("redirect");
                        $page->set_redirect(make_link($redirectTo));

                        break;
                    }
					case "delete":
						$threadID = (int)($event->get_arg(1));
						$postID = (int)($event->get_arg(2));

                        if ($user->is_admin()) {$this->delete_post($postID);}

                        $page->set_mode("redirect");
                        $page->set_redirect(make_link("forum/view/".$threadID));
                        break;
                    case "nuke":
                        $threadID = (int)($event->get_arg(1));

                        if ($user->is_admin())
                            $this->delete_thread($threadID);

                        $page->set_mode("redirect");
                        $page->set_redirect(make_link("forum/index"));
                        break;
                    case "answer":
                        if (!$user->is_anonymous())
                        {
                            list($hasErrors, $errors) = $this->valid_values_for_new_post();

                            if ($hasErrors)
                            {
                                $this->theme->display_error(500, "Error", $errors);
                                $this->theme->display_new_post_composer($page, $_POST["threadID"], $_POST["message"], $_POST["title"], false);
                                break;
                            }

                            $threadID = (int)($_POST["threadID"]);
                            
                            $this->save_new_post($threadID, $user);
                        }

                        $page->set_mode("redirect");
                        $page->set_redirect(make_link("forum/view/".$threadID."/1"));
                        break;
                    default:
                    {
                        $page->set_mode("redirect");
                        $page->set_redirect(make_link("forum/index"));
                        //$this->theme->display_error(400, "Invalid action", "You should check forum/index.");
                        break;
                    }
                }
            }
	}

        private function get_total_pages_for_thread(/*int*/ $threadID)
        {
            global $database, $config;
            $result = $database->get_row("SELECT COUNT(1) AS count FROM forum_posts WHERE thread_id = ?", array($threadID));

            return ceil($result["count"] / $config->get_int("forumPostsPerPage"));
        }

        private function valid_values_for_new_thread()
        {
            $hasErrors = false;

            $errors = "";
            
            if (!array_key_exists("title", $_POST))
            {
                $hasErrors = true;
                $errors .= "<div id='error'>No title supplied.</div>";
            }
            else if (strlen($_POST["title"]) == 0)
            {
                $hasErrors = true;
                $errors .= "<div id='error'>You cannot have an empty title.</div>";
            }
            else if (strlen(html_escape($_POST["title"])) > 255)
            {
                $hasErrors = true;
                $errors .= "<div id='error'>Your title is too long.</div>";
            }

            if (!array_key_exists("message", $_POST))
            {
                $hasErrors = true;
                $errors .= "<div id='error'>No message supplied.</div>";
            }
            else if (strlen($_POST["message"]) == 0)
            {
                $hasErrors = true;
                $errors .= "<div id='error'>You cannot have an empty message.</div>";
            }

            return array($hasErrors, $errors);
        }
        private function valid_values_for_new_post()
        {
            $hasErrors = false;

            $errors = "";
            if (!array_key_exists("threadID", $_POST))
            {
                $hasErrors = true;
                $errors = "<div id='error'>No thread ID supplied.</div>";
            }
            else if (strlen($_POST["threadID"]) == 0)
            {
                $hasErrors = true;
                $errors = "<div id='error'>No thread ID supplied.</div>";
            }
            else if (is_numeric($_POST["threadID"]))

            if (!array_key_exists("message", $_POST))
            {
                $hasErrors = true;
                $errors .= "<div id='error'>No message supplied.</div>";
            }
            else if (strlen($_POST["message"]) == 0)
            {
                $hasErrors = true;
                $errors .= "<div id='error'>You cannot have an empty message.</div>";
            }
            
            return array($hasErrors, $errors);
        }
        private function get_thread_title($threadID)
        {
            global $database;
            $result = $database->get_row("SELECT t.title FROM forum_threads AS t WHERE t.id = ? ", array($threadID));
            return $result["title"];
        }
		
        private function show_last_threads(Page $page, $event, $showAdminOptions = false)
        {
			global $config, $database;
            $pageNumber = $event->get_arg(1);
            if(is_null($pageNumber) || !is_numeric($pageNumber))
                $pageNumber = 0;
            else if ($pageNumber <= 0)
                $pageNumber = 0;
            else
                $pageNumber--;
            
            $threadsPerPage = $config->get_int('forumThreadsPerPage', 15);

            $threads = $database->get_all(
                "SELECT f.id, f.sticky, f.title, f.date, f.uptodate, u.name AS user_name, u.email AS user_email, u.admin AS user_admin, sum(1) - 1 AS response_count ".
                "FROM forum_threads AS f ".
                "INNER JOIN users AS u ".
                "ON f.user_id = u.id ".
                "INNER JOIN forum_posts AS p ".
                "ON p.thread_id = f.id ".
                "GROUP BY f.id, f.sticky, f.title, f.date, u.name, u.email, u.admin ".
                "ORDER BY f.sticky ASC, f.uptodate DESC LIMIT ?, ?"
                , array($pageNumber * $threadsPerPage, $threadsPerPage)
            );
			
            $totalPages = ceil($database->get_one("SELECT COUNT(*) FROM forum_threads") / $threadsPerPage);
			
            $this->theme->display_thread_list($page, $threads, $showAdminOptions, $pageNumber + 1, $totalPages);
        }
		
		private function show_posts($event, $showAdminOptions = false)
        {
			global $config, $database, $user;
			
			$threadID = $event->get_arg(1);
            $pageNumber = $event->get_arg(2);
            if(is_null($pageNumber) || !is_numeric($pageNumber))
                $pageNumber = 0;
            else if ($pageNumber <= 0)
                $pageNumber = 0;
            else
                $pageNumber--;
				
            $postsPerPage = $config->get_int('forumPostsPerPage', 15);

            $posts = $database->get_all(
                "SELECT p.id, p.date, p.message, u.name as user_name, u.email AS user_email, u.admin AS user_admin ".
                "FROM forum_posts AS p ".
                "INNER JOIN users AS u ".
                "ON p.user_id = u.id ".
                "WHERE thread_id = ? ".
				"ORDER BY p.date ASC ".
                "LIMIT ?, ? "
                , array($threadID, $pageNumber * $postsPerPage, $postsPerPage)
            );
			
            $totalPages = ceil($database->get_one("SELECT COUNT(*) FROM forum_posts WHERE thread_id = ?", array($threadID)) / $postsPerPage);
			
			$threadTitle = $this->get_thread_title($threadID);
			
			$this->theme->display_thread($posts, $showAdminOptions, $threadTitle, $threadID, $pageNumber + 1, $totalPages);
        }

        private function save_new_thread($user)
        {
            $title = html_escape($_POST["title"]);
			$sticky = html_escape($_POST["sticky"]);
			
			if($sticky == ""){
			$sticky = "N";
			}

            global $database;
            $database->execute("
                INSERT INTO forum_threads
                    (title, sticky, user_id, date, uptodate)
                VALUES
                    (?, ?, ?, now(), now())",
                array($title, $sticky, $user->id));
				
            $result = $database->get_row("SELECT LAST_INSERT_ID() AS threadID", array());
			
			log_info("forum", "Thread {$result["threadID"]} created by {$user->name}");
			
            return $result["threadID"];
        }

        private function save_new_post($threadID, $user)
        {
			global $config;
            $userID = $user->id;
            $message = html_escape($_POST["message"]);
			
			$max_characters = $config->get_int('forumMaxCharsPerPost');
			$message = substr($message, 0, $max_characters);

            global $database;
            $database->execute("INSERT INTO forum_posts
                    (thread_id, user_id, date, message)
                VALUES
                    (?, ?, now(), ?)"
                , array($threadID, $userID, $message));
			
			$result = $database->get_row("SELECT LAST_INSERT_ID() AS postID", array());
			
			log_info("forum", "Post {$result["postID"]} created by {$user->name}");
			
			$database->execute("UPDATE forum_threads SET uptodate=now() WHERE id=?", array ($threadID));
        }

        private function retrieve_posts($threadID, $pageNumber)
        {
            global $database, $config;
            $postsPerPage = $config->get_int('forumPostsPerPage', 15);

            return $database->get_all(
                "SELECT p.id, p.date, p.message, u.name as user_name, u.email AS user_email, u.admin AS user_admin ".
                "FROM forum_posts AS p ".
                "INNER JOIN users AS u ".
                "ON p.user_id = u.id ".
                "WHERE thread_id = ? ".
				"ORDER BY p.date ASC ".
                "LIMIT ?, ? "
                , array($threadID, ($pageNumber - 1) * $postsPerPage, $postsPerPage));
        }

        private function delete_thread($threadID)
        {
            global $database;
            $database->execute("DELETE FROM forum_threads WHERE id = ?", array($threadID));
			$database->execute("DELETE FROM forum_posts WHERE thread_id = ?", array($threadID));
        }
		
		private function delete_post($postID)
        {
            global $database;
            $database->execute("DELETE FROM forum_posts WHERE id = ?", array($postID));
        }
}
?>
