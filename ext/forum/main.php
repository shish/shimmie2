<?php
/**
 * Name: [Beta] Forum
 * Author: Sein Kraft <mail@seinkraft.info>
 *         Alpha <alpha@furries.com.ar>
 * License: GPLv2
 * Description: Rough forum extension
 * Documentation:
 */
/*
Todo:
*Quote buttons on posts
*Move delete and quote buttons away from each other
*Bring us on par with comment extension(post linking, image linking, thumb links, URL autolink)
*Smiley filter, word filter, etc should work with our extension

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
					date SCORE_DATETIME NOT NULL,
					uptodate SCORE_DATETIME NOT NULL,
					FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
					");
			$database->execute("CREATE INDEX forum_threads_date_idx ON forum_threads(date)", array());
			
			$database->create_table("forum_posts", "
					id SCORE_AIPK,
					thread_id INTEGER NOT NULL,
					user_id INTEGER NOT NULL,
					date SCORE_DATETIME NOT NULL,
					message TEXT,
					FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
					FOREIGN KEY (thread_id) REFERENCES forum_threads (id) ON UPDATE CASCADE ON DELETE CASCADE
					");
			$database->execute("CREATE INDEX forum_posts_date_idx ON forum_posts(date)", array());

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
		global $database;
		
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
						$threadID = int_escape($event->get_arg(1));
						$pageNumber = int_escape($event->get_arg(2));
						list($errors) = $this->sanity_check_viewed_thread($threadID);
						
						if($errors!=null)
                        {
                            $this->theme->display_error(500, "Error", $errors);
                            break;
                        }
						
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
                            list($errors) = $this->sanity_check_new_thread();

                            if($errors!=null)
                            {
                                $this->theme->display_error(500, "Error", $errors);
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
						$threadID = int_escape($event->get_arg(1));
						$postID = int_escape($event->get_arg(2));

                        if ($user->is_admin()) {$this->delete_post($postID);}

                        $page->set_mode("redirect");
                        $page->set_redirect(make_link("forum/view/".$threadID));
                        break;
                    case "nuke":
                        $threadID = int_escape($event->get_arg(1));

                        if ($user->is_admin())
                            $this->delete_thread($threadID);

                        $page->set_mode("redirect");
                        $page->set_redirect(make_link("forum/index"));
                        break;
                    case "answer":
						$threadID = int_escape($_POST["threadID"]);
						$total_pages = $this->get_total_pages_for_thread($threadID);
                        if (!$user->is_anonymous())
                        {
                            list($errors) = $this->sanity_check_new_post();

                            if ($errors!=null)
                            {
                                $this->theme->display_error(500, "Error", $errors);
                                break;
                            }
                            $this->save_new_post($threadID, $user);
                        }
                        $page->set_mode("redirect");
                        $page->set_redirect(make_link("forum/view/".$threadID."/".$total_pages));
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

        private function sanity_check_new_thread()
        {
            $errors = null;
            if (!array_key_exists("title", $_POST))
            {
                $errors .= "<div id='error'>No title supplied.</div>";
            }
            else if (strlen($_POST["title"]) == 0)
            {
                $errors .= "<div id='error'>You cannot have an empty title.</div>";
            }
            else if (strlen(html_escape($_POST["title"])) > 255)
            {
                $errors .= "<div id='error'>Your title is too long.</div>";
            }

            if (!array_key_exists("message", $_POST))
            {
                $errors .= "<div id='error'>No message supplied.</div>";
            }
            else if (strlen($_POST["message"]) == 0)
            {
                $errors .= "<div id='error'>You cannot have an empty message.</div>";
            }

            return array($errors);
        }
        private function sanity_check_new_post()
        {
            $errors = null;
            if (!array_key_exists("threadID", $_POST))
            {
                $errors = "<div id='error'>No thread ID supplied.</div>";
            }
            else if (strlen($_POST["threadID"]) == 0)
            {
                $errors = "<div id='error'>No thread ID supplied.</div>";
            }
            else if (is_numeric($_POST["threadID"]))

            if (!array_key_exists("message", $_POST))
            {
                $errors .= "<div id='error'>No message supplied.</div>";
            }
            else if (strlen($_POST["message"]) == 0)
            {
                $errors .= "<div id='error'>You cannot have an empty message.</div>";
            }
            
            return array($errors);
        }
		private function sanity_check_viewed_thread($threadID)
        {
            $errors = null;
            if (!$this->threadExists($threadID))
            {
                $errors = "<div id='error'>Inexistent thread.</div>";
            }            
            return array($errors);
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
			$threadsPerPage = $config->get_int('forumThreadsPerPage', 15);
			$totalPages = ceil($database->get_one("SELECT COUNT(*) FROM forum_threads") / $threadsPerPage);
			
            if(is_null($pageNumber) || !is_numeric($pageNumber))
                $pageNumber = 0;
            else if ($pageNumber <= 0)
                $pageNumber = 0;
			else if ($pageNumber >= $totalPages)
                $pageNumber = $totalPages - 1;
            else
                $pageNumber--;

            $threads = $database->get_all(
                "SELECT f.id, f.sticky, f.title, f.date, f.uptodate, u.name AS user_name, u.email AS user_email, u.class AS user_class, sum(1) - 1 AS response_count ".
                "FROM forum_threads AS f ".
                "INNER JOIN users AS u ".
                "ON f.user_id = u.id ".
                "INNER JOIN forum_posts AS p ".
                "ON p.thread_id = f.id ".
                "GROUP BY f.id, f.sticky, f.title, f.date, u.name, u.email, u.class ".
                "ORDER BY f.sticky ASC, f.uptodate DESC LIMIT :limit OFFSET :offset"
                , array("limit"=>$threadsPerPage, "offset"=>$pageNumber * $threadsPerPage)
            );
			
            $this->theme->display_thread_list($page, $threads, $showAdminOptions, $pageNumber + 1, $totalPages);
        }
		
		private function show_posts($event, $showAdminOptions = false)
        {
			global $config, $database;
			$threadID = $event->get_arg(1);
            $pageNumber = $event->get_arg(2);
			$postsPerPage = $config->get_int('forumPostsPerPage', 15);
			$totalPages = ceil($database->get_one("SELECT COUNT(*) FROM forum_posts WHERE thread_id = ?", array($threadID)) / $postsPerPage);
			$threadTitle = $this->get_thread_title($threadID);
			
            if(is_null($pageNumber) || !is_numeric($pageNumber))
                $pageNumber = 0;
            else if ($pageNumber <= 0)
                $pageNumber = 0;
			else if ($pageNumber >= $totalPages)
				$pageNumber = $totalPages - 1;
            else
                $pageNumber--;

            $posts = $database->get_all(
                "SELECT p.id, p.date, p.message, u.name as user_name, u.email AS user_email, u.class AS user_class ".
                "FROM forum_posts AS p ".
                "INNER JOIN users AS u ".
                "ON p.user_id = u.id ".
                "WHERE thread_id = :thread_id ".
				"ORDER BY p.date ASC ".
                "LIMIT :limit OFFSET :offset"
                , array("thread_id"=>$threadID, "offset"=>$pageNumber * $postsPerPage, "limit"=>$postsPerPage)
            );
			$this->theme->display_thread($posts, $showAdminOptions, $threadTitle, $threadID, $pageNumber + 1, $totalPages);
        }

        private function save_new_thread($user)
        {
            $title = html_escape($_POST["title"]);
			$sticky = !empty($_POST["sticky"]) ? html_escape($_POST["sticky"]) : "N";
			
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
				
            $threadID = $database->get_last_insert_id("forum_threads_id_seq");
			
			log_info("forum", "Thread {$threadID} created by {$user->name}");
			
            return $threadID;
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
			
			$postID = $database->get_last_insert_id("forum_posts_id_seq");
			
			log_info("forum", "Post {$postID} created by {$user->name}");
			
			$database->execute("UPDATE forum_threads SET uptodate=now() WHERE id=?", array ($threadID));
        }

        private function retrieve_posts($threadID, $pageNumber)
        {
            global $database, $config;
            $postsPerPage = $config->get_int('forumPostsPerPage', 15);

            return $database->get_all(
                "SELECT p.id, p.date, p.message, u.name as user_name, u.email AS user_email, u.class AS user_class ".
                "FROM forum_posts AS p ".
                "INNER JOIN users AS u ".
                "ON p.user_id = u.id ".
                "WHERE thread_id = :thread_id ".
				"ORDER BY p.date ASC ".
                "LIMIT :limit OFFSET :offset "
                , array("thread_id"=>$threadID, "offset"=>($pageNumber - 1) * $postsPerPage, "limit"=>$postsPerPage));
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
		private function threadExists($threadID){
			global $database;
			$result=$database->get_one("SELECT EXISTS (SELECT * FROM forum_threads WHERE id= ?)", array($threadID));
			if ($result==1){
				return true;
			}else{
				return false;
			}
		}
}

