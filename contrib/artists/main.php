<?php
/**
 * Name: [Beta] Artists System
 * Author: Sein Kraft <mail@seinkraft.info>
 *         Alpha <alpha@furries.com.ar>
 * License: GPLv2
 * Description: Simple artists extension
 * Documentation:
 *
 */
class AuthorSetEvent extends Event {
    var $image, $user, $author;

    public function AuthorSetEvent(Image $image, User $user, $author)
    {
        $this->image = $image;
        $this->user = $user;
        $this->author = $author;
    }
}

class Artists implements Extension {
    var $theme;

    public function receive_event(Event $event)
    {
        global $user;

        if(is_null($this->theme)) $this->theme = get_theme_object($this);

        if ($event instanceof ImageInfoSetEvent)
            if (isset($_POST["tag_edit__author"]))
                send_event(new AuthorSetEvent($event->image, $user, $_POST["tag_edit__author"]));

        if ($event instanceof AuthorSetEvent)
            $this->update_author($event);

        if($event instanceof InitExtEvent)
            $this->try_install();

        if ($event instanceof ImageInfoBoxBuildingEvent)
            $this->add_author_field_to_image($event);

        if ($event instanceof PageRequestEvent)
            $this->handle_commands($event);
    }

    public function try_install() {
    	global $config, $database;
                
    	if ($config->get_int("ext_artists_version") < 1)
        {
            $database->create_table("artists",
                "id SCORE_AIPK
                 , user_id INTEGER NOT NULL
                 , name VARCHAR(255) NOT NULL
                 , created DATETIME NOT NULL
                 , updated DATETIME NOT NULL
                 , notes TEXT
                 , INDEX(id)
                ");
            $database->create_table("artist_members",
               "id SCORE_AIPK
                , artist_id INTEGER NOT NULL
                , user_id INTEGER NOT NULL
                , name VARCHAR(255) NOT NULL
                , created DATETIME NOT NULL
                , updated DATETIME NOT NULL
                , INDEX (id)
                , FOREIGN KEY (artist_id) REFERENCES artists (id) ON UPDATE CASCADE ON DELETE CASCADE
                ");
            $database->create_table("artist_alias",
                "id SCORE_AIPK
                 , artist_id INTEGER NOT NULL
                 , user_id INTEGER NOT NULL
                 , created DATETIME
                 , updated DATETIME
                 , alias VARCHAR(255)
                 , INDEX (id)
                 , FOREIGN KEY (artist_id) REFERENCES artists (id) ON UPDATE CASCADE ON DELETE CASCADE
                ");
            $database->create_table("artist_urls",
                "id SCORE_AIPK
                , artist_id INTEGER NOT NULL
                , user_id INTEGER NOT NULL
                , created DATETIME NOT NULL
                , updated DATETIME NOT NULL
                , url VARCHAR(1000) NOT NULL
                , INDEX (id)
                , FOREIGN KEY (artist_id) REFERENCES artists (id) ON UPDATE CASCADE ON DELETE CASCADE
                ");
            $database->execute("ALTER TABLE images ADD COLUMN author VARCHAR(255) NULL", array());

            $config->set_int("artistsPerPage", 20);
            $config->set_int("ext_artists_version", 1);

            log_info("artists", "extension installed");
        }
    }

    public function update_author($event)
    {
        global $database;

        $author = strtolower($event->author);
        if (strlen($author) == 0 || strpos($author, " "))
           return;

        $paddedAuthor = str_replace(" ", "_", $author);

        $artistID = NULL;
        if ($this->artist_exists($author))
            $artistID = $this->get_artist_id($author);

        if (is_null($artistID) && $this->alias_exists_by_name($paddedAuthor))
            $artistID = $this->get_artistID_by_aliasName($paddedAuthor);

        if (is_null($artistID) && $this->member_exists_by_name($paddedAuthor))
            $artistID = $this->get_artistID_by_memberName($paddedAuthor);

        if (is_null($artistID) && $this->url_exists_by_url($author))
            $artistID = $this->get_artistID_by_url($author);

        if (!is_null($artistID))
            $artistName = $this->get_artistName_by_artistID($artistID);
        else
        {
            $this->save_new_artist($author, "");
            $artistName = $author;
        }

        $database->execute("UPDATE images SET author = ? WHERE id = ?"
            , array(
                mysql_real_escape_string($artistName)
                , $event->image->id
            ));
    }
    public function handle_commands($event)
    {
        global $config, $page, $user;

        if($event->page_matches("artist"))
        {
            switch($event->get_arg(0))
            {
                //*************ARTIST SECTION**************
                case "list":
                {
                    $this->get_listing($page, $event);
                    $this->theme->sidebar_options("neutral");
                    break;
                }
                case "new":
                {
                    if(!$user->is_anonymous()){
                    	$this->theme->new_artist_composer();
                    }else{
                        $errMessage = "You must be registered and logged in to create a new artist.";
                        $this->theme->display_error($page, "Error", $errMessage);
                    }
                    break;
                }
                case "new_artist":
                {
                    $page->set_mode("redirect");
                    $page->set_redirect(make_link("artist/new"));
                    break;
                }
                case "create":
                {
                    if(!$user->is_anonymous())
                    {
                        $newArtistID = $this->add_artist();
                        if ($newArtistID == -1)
                        {
                            $errMessage = "Error when entering artist data.";
                            $this->theme->display_error($page, "Error", $errMessage);
                        }
                        else
                        {
                            $page->set_mode("redirect");
                            $page->set_redirect(make_link("artist/view/".$newArtistID));
                        }
                    }
                    else
                    {
                        $errMessage = "You must be registered and logged in to create a new artist.";
                        $this->theme->display_error($page, "Error", $errMessage);
                    }
                    break;
                }

                case "view":
                {
                    $artistID = $event->get_arg(1);
                    $artist = $this->get_artist($artistID);
                    $aliases = $this->get_alias($artist['id']);
                    $members = $this->get_members($artist['id']);
                    $urls = $this->get_urls($artist['id']);

                    $userIsLogged = !$user->is_anonymous();
                    $userIsAdmin = $user->is_admin();
					
                    $images = Image::find_images(0, 4, Tag::explode($artist['name']));

                    $this->theme->show_artist($artist, $aliases, $members, $urls, $images, $userIsLogged, $userIsAdmin);
                    if ($userIsLogged)
                    {
                        //$this->theme->show_new_alias_composer($artistID);
                        //$this->theme->show_new_member_composer($artistID);
                        //$this->theme->show_new_url_composer($artistID);
                    }
					
                    $this->theme->sidebar_options("editor", $artistID, $userIsAdmin);
					
                    break;
                }

                case "edit":
                {
                    $artistID = $event->get_arg(1);
                    $artist = $this->get_artist($artistID);
                    $aliases = $this->get_alias($artistID);
                    $members = $this->get_members($artistID);
                    $urls = $this->get_urls($artistID);
					
                    if(!$user->is_anonymous()){
                    	$this->theme->show_artist_editor($artist, $aliases, $members, $urls);
						
                        $userIsAdmin = $user->is_admin();
                        $this->theme->sidebar_options("editor", $artistID, $userIsAdmin);
                    }else{
                        $errMessage = "You must be registered and logged in to edit an artist.";
                        $this->theme->display_error($page, "Error", $errMessage);
                    }
                    break;
                }
                case "edit_artist":
                {
                    $artistID = $_POST['artist_id'];
                    $page->set_mode("redirect");
                    $page->set_redirect(make_link("artist/edit/".$artistID));
                    break;
                }
                case "edited":
                {
                    $artistID = int_escape($_POST['id']);
                    $this->update_artist();
                    $page->set_mode("redirect");
                    $page->set_redirect(make_link("artist/view/".$artistID));
                    break;
                }
                case "nuke_artist":
                {
                    $artistID = $_POST['artist_id'];
                    $page->set_mode("redirect");
                    $page->set_redirect(make_link("artist/nuke/".$artistID));
                    break;
                }
                case "nuke":
                {
                    $artistID = $event->get_arg(1);
                    $this->delete_artist($artistID); // this will delete the artist, it's alias, it's urls and it's members
                    $page->set_mode("redirect");
                    $page->set_redirect(make_link("artist/list"));
                    break;
                }
                case "add_alias":
                {
                    $artistID = $_POST['artist_id'];
                    $this->theme->show_new_alias_composer($artistID);
                    break;
                }
                case "add_member":
                {
                    $artistID = $_POST['artist_id'];
                    $this->theme->show_new_member_composer($artistID);
                    break;
                }
                case "add_url":
                {
                    $artistID = $_POST['artist_id'];
                    $this->theme->show_new_url_composer($artistID);
                    break;
                }
                //***********ALIAS SECTION ***********************
                case "alias":
                {
                    switch ($event->get_arg(1))
                    {
                        case "add":
                        {
                            $artistID = $_POST['artistID'];
                            $this->add_alias();
                            $page->set_mode("redirect");
                            $page->set_redirect(make_link("artist/view/".$artistID));
                            break;
                        }
                        case "delete":
                        {
                            $aliasID = $event->get_arg(2);
                            $artistID = $this->get_artistID_by_aliasID($aliasID);
                            $this->delete_alias($aliasID);
                            $page->set_mode("redirect");
                            $page->set_redirect(make_link("artist/view/".$artistID));
                            break;
                        }
                        case "edit":
                        {
                            $aliasID = int_escape($event->get_arg(2));
                            $alias = $this->get_alias_by_id($aliasID);
                            $this->theme->show_alias_editor($alias);
                            break;
                        }
                        case "edited":
                        {
                            $this->update_alias();
                            $aliasID = int_escape($_POST['aliasID']);
                            $artistID = $this->get_artistID_by_aliasID($aliasID);
                            $page->set_mode("redirect");
                            $page->set_redirect(make_link("artist/view/".$artistID));
                            break;
                        }
                    }
                    break; // case: alias
                }

                //**************** URLS SECTION **********************
                case "url":
                {
                    switch ($event->get_arg(1))
                    {
                        case "add":
                        {
                            $artistID = $_POST['artistID'];
                            $this->add_urls();
                            $page->set_mode("redirect");
                            $page->set_redirect(make_link("artist/view/".$artistID));
                            break;
                        }
                        case "delete":
                        {
                            $urlID = $event->get_arg(2);
                            $artistID = $this->get_artistID_by_urlID($urlID);
                            $this->delete_url($urlID);
                            $page->set_mode("redirect");
                            $page->set_redirect(make_link("artist/view/".$artistID));
                            break;
                        }
                        case "edit":
                        {
                            $urlID = int_escape($event->get_arg(2));
                            $url = $this->get_url_by_id($urlID);
                            $this->theme->show_url_editor($url);
                            break;
                        }
                        case "edited":
                        {
                            $this->update_url();
                            $urlID = int_escape($_POST['urlID']);
                            $artistID = $this->get_artistID_by_urlID($urlID);
                            $page->set_mode("redirect");
                            $page->set_redirect(make_link("artist/view/".$artistID));
                            break;
                        }
                    }
                    break; // case: urls
                }
                //******************* MEMBERS SECTION *********************
                case "member":
                {
                    switch ($event->get_arg(1))
                    {
                        case "add":
                        {
                            $artistID = $_POST['artistID'];
                            $this->add_members();
                            $page->set_mode("redirect");
                            $page->set_redirect(make_link("artist/view/".$artistID));
                            break;
                        }
                        case "delete":
                        {
                            $memberID = int_escape($event->get_arg(2));
                            $artistID = $this->get_artistID_by_memberID($memberID);
                            $this->delete_member($memberID);
                            $page->set_mode("redirect");
                            $page->set_redirect(make_link("artist/view/".$artistID));
                            break;
                        }
                        case "edit":
                        {
                            $memberID = int_escape($event->get_arg(2));
                            $member = $this->get_member_by_id($memberID);
                            $this->theme->show_member_editor($member);
                            break;
                        }
                        case "edited":
                        {
                            $this->update_member();
                            $memberID = int_escape($_POST['memberID']);
                            $artistID = $this->get_artistID_by_memberID($memberID);
                            $page->set_mode("redirect");
                            $page->set_redirect(make_link("artist/view/".$artistID));
                            break;
                        }
                    }
                    break; //case: members
                }
            }
        }
    }

    public function add_author_field_to_image($event)
    {
		global $user;
        $artistName = $this->get_artistName_by_imageID($event->image->id);
		if(!$user->is_anonymous()) {
	        $event->add_part($this->theme->get_author_editor_html($artistName), 42);
		}
    }

    private function get_artistName_by_imageID($imageID)
    {
        if(!is_numeric($imageID)) return null;

        global $database;

        $result = $database->get_row("SELECT author FROM images WHERE id = ?", array($imageID));
        return stripslashes($result['author']);
    }

    private function url_exists_by_url($url)
    {
        global $database;

        $result = $database->db->GetOne("SELECT COUNT(1) FROM artist_urls WHERE url = ?", array(mysql_real_escape_string($url)));
        return ($result != 0);
    }

    private function member_exists_by_name($member)
    {
        global $database;

        $result = $database->db->GetOne("SELECT COUNT(1) FROM artist_members WHERE name = ?", array(mysql_real_escape_string($member)));
        return ($result != 0);
    }

    private function alias_exists_by_name($alias)
    {
        global $database;

        $result = $database->db->GetOne("SELECT COUNT(1) FROM artist_alias WHERE alias = ?", array(mysql_real_escape_string($alias)));
        return ($result != 0);
    }

    private function alias_exists($artistID, $alias){
        if (!is_numeric($artistID)) return;

        global $database;

        $result = $database->db->GetOne("SELECT COUNT(1) FROM artist_alias WHERE artist_id = ? AND alias = ?", array(
                $artistID
                , mysql_real_escape_string($alias)
            ));
        return ($result != 0);
    }

    private function get_artistID_by_url($url)
    {
        global $database;
        $result = $database->get_row("SELECT artist_id FROM artist_urls WHERE url = ?", array(mysql_real_escape_string($url)));
        return $result['artist_id'];
    }

    private function get_artistID_by_memberName($member)
    {
        global $database;
        $result = $database->get_row("SELECT artist_id FROM artist_members WHERE name = ?", array(mysql_real_escape_string($member)));
        return $result['artist_id'];
    }
    private function get_artistName_by_artistID($artistID)
    {
        if (!is_numeric($artistID)) return;

        global $database;
        $result = $database->get_row("SELECT name FROM artists WHERE id = ?", array($artistID));
        return stripslashes($result['name']);
    }

    private function get_artistID_by_aliasID($aliasID)
    {
        if (!is_numeric($aliasID)) return;

        global $database;
        $result = $database->get_row("SELECT artist_id FROM artist_alias WHERE id = ?", array($aliasID));
        return $result['artist_id'];
    }

    private function get_artistID_by_memberID($memberID)
    {
        if (!is_numeric($memberID)) return;

        global $database;
        $result = $database->get_row("SELECT artist_id FROM artist_members WHERE id = ?", array($memberID));
        return $result['artist_id'];
    }

    private function get_artistID_by_urlID($urlID)
    {
        if (!is_numeric($urlID)) return;

        global $database;
        $result = $database->get_row("SELECT artist_id FROM artist_urls WHERE id = ?", array($urlID));
        return $result['artist_id'];
    }

    private function delete_alias($aliasID)
    {
        if (!is_numeric($aliasID)) return;

        global $database;
        $database->execute("DELETE FROM artist_alias WHERE id = ?", array($aliasID));
    }

    private function delete_url($urlID)
    {
        if (!is_numeric($urlID)) return;

        global $database;
        $database->execute("DELETE FROM artist_urls WHERE id = ?", array($urlID));
    }

    private function delete_member($memberID)
    {
        if (!is_numeric($memberID)) return;

        global $database;
        $database->execute("DELETE FROM artist_members WHERE id = ?", array($memberID));
    }


    private function get_alias_by_id($aliasID)
    {
        if (!is_numeric($aliasID)) return;

        global $database;
        $result = $database->get_row("SELECT * FROM artist_alias WHERE id = ?", array($aliasID));

        $result["alias"] = stripslashes($result["alias"]);
        
        return $result;
    }

    private function get_url_by_id($urlID)
    {
        if (!is_numeric($urlID)) return;

        global $database;
        $result = $database->get_row("SELECT * FROM artist_urls WHERE id = ?", array($urlID));

        $result["url"] = stripslashes($result["url"]);

        return $result;
    }

    private function get_member_by_id($memberID)
    {
        if (!is_numeric($memberID)) return;

        global $database;
        $result = $database->get_row("SELECT * FROM artist_members WHERE id = ?", array($memberID));

        $result["name"] = stripslashes($result["name"]);

        return $result;
    }

    private function update_artist()
    {
        global $user;
        $artistID = $_POST['id'];
        $name = strtolower($_POST['name']);
        $notes = $_POST['notes'];
        $userID = $user->id;

        $aliasesAsString = trim($_POST["aliases"]);
        if (strlen($aliasesAsString) == 0) $aliasesAsString = NULL;
        $aliasesIDsAsString = trim($_POST["aliasesIDs"]);
        if (strlen($aliasesIDsAsString) == 0) $aliasesIDsAsString = NULL;

        $membersAsString = trim($_POST["members"]);
        if (strlen($membersAsString) == 0) $membersAsString = NULL;
        $membersIDsAsString = trim($_POST["membersIDs"]);
        if (strlen($membersIDsAsString) == 0) $membersIDsAsString = NULL;

        $urlsAsString = trim($_POST["urls"]);
        if (strlen($urlsAsString) == 0) $urlsAsString = NULL;
        $urlsIDsAsString = trim($_POST["urlsIDs"]);
        if (strlen($urlsIDsAsString) == 0) $urlsIDsAsString = NULL;

        if (is_null($artistID) || !is_numeric($artistID))
            return;

        if (is_null($userID) || !is_numeric($userID))
            return;

        if (is_null($name) || strlen($name) == 0 || strpos($name, " "))
            return;

        //if (is_null($aliasesAsString) || is_null($aliasesIDsAsString))
        //    return;

        //if (is_null($membersAsString) || is_null($membersIDsAsString))
        //    return;

        //if (is_null($urlsAsString) || is_null($urlsIDsAsString))
        //    return;

        if (strlen($notes) == 0)
            $notes = NULL;

        global $database;
        $database->execute("UPDATE artists SET name = ?, notes = ?, updated = now(), user_id = ? WHERE id = ? "
            , array(
                mysql_real_escape_string($name)
                , mysql_real_escape_string($notes)
                , $userID
                , $artistID
            ));

        // ALIAS MATCHING SECTION
        $i = 0;
        $aliasesAsArray = is_null($aliasesAsString) ? array() : explode(" ", $aliasesAsString);
        $aliasesIDsAsArray = is_null($aliasesIDsAsString) ? array() : explode(" ", $aliasesIDsAsString);
        while ($i < count($aliasesAsArray))
        {
            // if an alias was updated
            if ($i < count($aliasesIDsAsArray))
                // save it
                $this->save_existing_alias($aliasesIDsAsArray[$i], $aliasesAsArray[$i], $userID);
            else
                // if we already updated all, save new ones
                $this->save_new_alias($artistID, $aliasesAsArray[$i], $userID);

            $i++;
        }
        // if we have more ids than alias, then some alias have been deleted -- delete them from db
        while ($i < count($aliasesIDsAsArray))
            $this->delete_alias($aliasesIDsAsArray[$i++]);

        // MEMBERS MATCHING SECTION
        $i = 0;
        $membersAsArray = is_null($membersAsString) ? array() : explode(" ", $membersAsString);
        $membersIDsAsArray = is_null($membersIDsAsString) ? array() : explode(" ", $membersIDsAsString);
        while ($i < count($membersAsArray))
        {
            // if a member was updated
            if ($i < count($membersIDsAsArray))
                //save it
                $this->save_existing_member($membersIDsAsArray[$i], $membersAsArray[$i], $userID);
            else
                // if we already updated all, save new ones
                $this->save_new_member($artistID, $membersAsArray[$i], "", $userID);

            $i++;
        }
        // if we have more ids than members, then some members have been deleted -- delete them from db
        while ($i < count($membersIDsAsArray))
            $this->delete_member($membersIDsAsArray[$i++]);

        // URLS MATCHING SECTION
        $i = 0;
        $urlsAsString = str_replace("\r\n", "\n", $urlsAsString);
        $urlsAsString = str_replace("\n\r", "\n", $urlsAsString);
        $urlsAsArray = is_null($urlsAsString) ? array() : explode("\n", $urlsAsString);
        $urlsIDsAsArray = is_null($urlsIDsAsString) ? array() : explode(" ", $urlsIDsAsString);
        while ($i < count($urlsAsArray))
        {
            // if an URL was updated
            if ($i < count($urlsIDsAsArray))
            {
                // save it
                $this->save_existing_url($urlsIDsAsArray[$i], $urlsAsArray[$i], $userID);
            }
            else
            {
                $this->save_new_url($artistID, $urlsAsArray[$i], $userID);
            }

            $i++;
        }
        
        // if we have more ids than urls, then some urls have been deleted -- delete them from db
        while ($i < count($urlsIDsAsArray))
            $this->delete_url($urlsIDsAsArray[$i++]);
    }

    private function update_alias()
    {
        $aliasID = $_POST['aliasID'];
        $alias = strtolower($_POST['alias']);

        if (is_null($aliasID) || !is_numeric($aliasID))
            return;

        if (is_null($alias) || strlen($alias) == 0)
            return;

        global $user;
        $this->save_existing_alias($aliasID, $alias, $user->id);
    }

    private function save_existing_alias($aliasID, $alias, $userID)
    {
        if (!is_numeric($userID)) return;
        if (!is_numeric($aliasID)) return;

        global $database;
        $database->execute("UPDATE artist_alias SET alias = ?, updated = now(), user_id  = ? WHERE id = ? "
            , array(
                mysql_real_escape_string($alias)
                , $userID
                , $aliasID
            ));
    }

    private function update_url()
    {
        $urlID = $_POST['urlID'];
        $url = $_POST['url'];

        if (is_null($urlID) || !is_numeric($urlID))
            return;

        if (is_null($url) || strlen($url) == 0)
            return;

        global $user;
        $this->save_existing_url($urlID, $url, $user->id);
    }

    private function save_existing_url($urlID, $url, $userID)
    {
        if (!is_numeric($userID)) return;
        if (!is_numeric($urlID)) return;

        global $database;
        $database->execute("UPDATE artist_urls SET url = ?, updated = now(), user_id = ? WHERE id = ?"
            , array(
                mysql_real_escape_string($url)
                , $userID
                , $urlID
            ));
    }

    private function update_member()
    {
        $memberID = $_POST['memberID'];
        $memberName = strtolower($_POST['name']);

        if (is_null($memberID) || !is_numeric($memberID))
            return;

        if (is_null($memberName) || strlen($memberName) == 0)
            return;

         global $user;
         $this->save_existing_member($memberID, $memberName, $user->id);
    }

    private function save_existing_member($memberID, $memberName, $userID)
    {
        if (!is_numeric($memberID)) return;
        if (!is_numeric($userID)) return;

        global $database;
		
        $database->execute("UPDATE artist_members SET name = ?, updated = now(), user_id = ? WHERE id = ?"
            , array(
                mysql_real_escape_string($memberName)
                , $userID
                , $memberID
            ));
    }

    /*
    * HERE WE ADD AN ARTIST
    */
    private function add_artist(){
        global $user;

        $name = html_escape(strtolower($_POST["name"]));
        if (is_null($name) || (strlen($name) == 0) || strpos($name, " "))
            return -1;

        $notes = html_escape(ucfirst($_POST["notes"]));
        if (strlen($notes) == 0)
            $notes = NULL;

        $aliases = strtolower($_POST["aliases"]);
        $members = strtolower($_POST["members"]);
        $urls = $_POST["urls"];
        $userID = $user->id;

        $artistID = "";

        //// WE CHECK IF THE ARTIST ALREADY EXISTS ON DATABASE; IF NOT WE CREATE
        if(!$this->artist_exists($name))
        {
            $artistID = $this->save_new_artist($name, $notes);
            log_info("artists", "Artist {$artistID} created by {$user->name}");
        }
        else
            $artistID = $this->get_artist_id($name);

        if (strlen($aliases) > 0)
        {
            $aliasArray = explode(" ", $aliases);
            foreach($aliasArray as $alias)
                if (!$this->alias_exists($artistID, $alias))
                    $this->save_new_alias($artistID, $alias, $userID);
        }

        if (strlen($members) > 0)
        {
            $membersArray = explode(" ", $members);
            foreach ($membersArray as $member)
                if (!$this->member_exists($artistID, $member))
                    $this->save_new_member($artistID, $member, "", $userID);
        }

        if (strlen($urls))
        {
            //delete double "separators"
            $urls = str_replace("\r\n", "\n", $urls);
            $urls = str_replace("\n\r", "\n", $urls);
            
            $urlsArray = explode("\n", $urls);
            foreach ($urlsArray as $url)
                if (!$this->url_exists($artistID, $url))
                    $this->save_new_url($artistID, $url, $userID);
        }
        return $artistID;
    }

    private function save_new_artist($name, $notes)
    {
        global $database, $user;

        $database->execute("
            INSERT INTO artists
                    (user_id, name, notes, created, updated)
            VALUES
                    (?, ?, ?, now(), now())",
            array(
                $user->id
                , mysql_real_escape_string($name)
                , mysql_real_escape_string($notes)
            ));

        $result = $database->get_row("SELECT LAST_INSERT_ID() AS artistID", array());

        return $result["artistID"];
    }

    /*
    * HERE WE CHECK IF ARTIST EXIST
    */
    private function artist_exists($name){
        global $database;

        $result = $database->db->GetOne("SELECT COUNT(1) FROM artists WHERE name = ?"
            , array(
                mysql_real_escape_string($name)
            ));
        return ($result != 0);
    }

    /*
    * HERE WE GET THE INFO OF THE ARTIST
    */
    private function get_artist($artistID){
        if (!is_numeric($artistID)) return;

        global $database;
        $result =  $database->get_row("SELECT * FROM artists WHERE id = ?",
            array(
                $artistID
            ));

        $result["name"] = stripslashes($result["name"]);
        $result["notes"] = stripslashes($result["notes"]);

        return $result;
    }

    private function get_members($artistID)
    {
        if (!is_numeric($artistID)) return;

        global $database;
        $result = $database->get_all("SELECT * FROM artist_members WHERE artist_id = ?"
            , array(
                $artistID
            ));

        for ($i = 0 ; $i < count($result) ; $i++)
        {
            $result[$i]["name"] = stripslashes($result[$i]["name"]);
        }

        return $result;
    }
    private function get_urls($artistID)
    {
        if (!is_numeric($artistID)) return;

        global $database;
        $result = $database->get_all("SELECT id, url FROM artist_urls WHERE artist_id = ?"
            , array(
                $artistID
            ));

        for ($i = 0 ; $i < count($result) ; $i++)
        {
            $result[$i]["url"] = stripslashes($result[$i]["url"]);
        }
        

        return $result;
    }

	/*
	* HERE WE GET THE ID OF THE ARTIST
	*/
	private function get_artist_id($name){
		global $database;
		$artistID = $database->get_row("SELECT id FROM artists WHERE name = ?"
                    , array(
                        mysql_real_escape_string($name)
                    ));
		return $artistID['id'];
	}

        private function get_artistID_by_aliasName($alias)
        {
            global $database;

            $artistID = $database->get_row("SELECT artist_id FROM artist_alias WHERE alias = ?"
                , array(
                    mysql_real_escape_string($alias)
                ));
            return $artistID["artist_id"];
        }
	
	
	/*
	* HERE WE DELETE THE ARTIST
	*/
	private function delete_artist($artistID)
        {
            if (!is_numeric($artistID)) return;

            global $database;
            $database->execute("DELETE FROM artists WHERE id = ? "
                , array(
                    $artistID
                ));
	}
	
	
	
	/*
	* HERE WE GET THE LIST OF ALL ARTIST WITH PAGINATION
	*/
        private function get_listing(Page $page, $event)
        {
            $pageNumber = $event->get_arg(1);
            if(is_null($pageNumber) || !is_numeric($pageNumber))
                $pageNumber = 0;
            else if ($pageNumber <= 0)
                $pageNumber = 0;
            else
                $pageNumber--;

            global $config, $database;
            $artistsPerPage = $config->get_int("artistsPerPage");

            $listing = $database->get_all(
                "
                        (
                            SELECT a.id, a.user_id, a.name, u.name AS user_name, COALESCE(t.count, 0) AS posts
                                , 'artist' as type, a.id AS artist_id, a.name AS artist_name, a.updated
                            FROM artists AS a
                                INNER JOIN users AS u
                                    ON a.user_id = u.id
                                LEFT OUTER JOIN tags AS t
                                    ON a.name = t.tag
                            GROUP BY a.id, a.user_id, a.name, u.name
                            ORDER BY a.updated DESC
                        )

                        UNION

                        (
                            SELECT aa.id, aa.user_id, aa.alias AS name, u.name AS user_name, COALESCE(t.count, 0) AS posts
                                , 'alias' as type, a.id AS artist_id, a.name AS artist_name, aa.updated
                            FROM artist_alias AS aa
                                INNER JOIN users AS u
                                    ON aa.user_id = u.id
                                INNER JOIN artists AS a
                                    ON aa.artist_id = a.id
                                LEFT OUTER JOIN tags AS t
                                    ON aa.alias = t.tag
                            GROUP BY aa.id, a.user_id, aa.alias, u.name, a.id, a.name
                            ORDER BY aa.updated DESC
                        )

                        UNION

                        (
                            SELECT m.id, m.user_id, m.name AS name, u.name AS user_name, COALESCE(t.count, 0) AS posts
                                , 'member' AS type, a.id AS artist_id, a.name AS artist_name, m.updated
                            FROM artist_members AS m
                                INNER JOIN users AS u
                                    ON m.user_id = u.id
                                INNER JOIN artists AS a
                                    ON m.artist_id = a.id
                                LEFT OUTER JOIN tags AS t
                                    ON m.name = t.tag
                            GROUP BY m.id, m.user_id, m.name, u.name, a.id, a.name
                            ORDER BY m.updated DESC
                        )
                ORDER BY updated DESC
                LIMIT ?, ?
            ", array(
                    $pageNumber * $artistsPerPage
                    , $artistsPerPage
                ));

            for ($i = 0 ; $i < count($listing) ; $i++)
            {
                $listing[$i]["name"] = stripslashes($listing[$i]["name"]);
                $listing[$i]["user_name"] = stripslashes($listing[$i]["user_name"]);
                $listing[$i]["artist_name"] = stripslashes($listing[$i]["artist_name"]);
            }

            $count = $database->db->GetOne(
                "SELECT COUNT(1)
                FROM artists AS a
                    LEFT OUTER JOIN artist_members AS am
                        ON a.id = am.artist_id
                    LEFT OUTER JOIN artist_alias AS aa
                        ON a.id = aa.artist_id
                ");

            $totalPages = ceil ($count / $artistsPerPage);

            $this->theme->list_artists($listing, $pageNumber + 1, $totalPages);
        }
	
	/*
	* HERE WE ADD AN ALIAS
	*/
        private function add_urls()
        {
            global $user;
            $artistID = $_POST["artistID"];
            $urls = $_POST["urls"];
            $userID = $user->id;

            if (is_null($artistID) || !is_numeric($artistID))
                return;

            if (is_null($urls) || strlen($urls) == 0)
                return;
            
            $urlArray = explode("\n", $urls);

            foreach ($urlArray as $url)
                if (!$this->url_exists($artistID, $url))
                    $this->save_new_url($artistID, $url, $userID);
        }

        private function save_new_url($artistID, $url, $userID)
        {
            if (!is_numeric($artistID)) return;
            if (!is_numeric($userID)) return;

            global $database;
            $database->execute("INSERT INTO artist_urls (artist_id, created, updated, url, user_id) VALUES (?, now(), now(), ?, ?)"
                , array(
                    $artistID
                    , mysql_real_escape_string($url)
                    , $userID
                ));
        }

	private function add_alias()
        {
            global $user;
            $artistID = $_POST["artistID"];
            $aliases = strtolower($_POST["aliases"]);
            $userID = $user->id;

            if (is_null($artistID) || !is_numeric($artistID))
                return;

            if (is_null($aliases) || trim($aliases) == "")
                return;

            $aliasArray = explode(" ", $aliases);
            global $database;
            foreach ($aliasArray as $alias)
                if (!$this->alias_exists($artistID, $alias))
                    $this->save_new_alias($artistID, $alias, $userID);
        }

        private function save_new_alias($artistID, $alias, $userID)
        {
            if (!is_numeric($artistID)) return;
            if (!is_numeric($userID)) return;

            global $database;
            $database->execute("INSERT INTO artist_alias (artist_id, created, updated, alias, user_id) VALUES (?, now(), now(), ?, ?)"
                        , array(
                            $artistID
                            , mysql_real_escape_string($alias)
                            , $userID
                        ));
        }

        private function add_members()
        {
            global $user;
            $artistID = $_POST["artistID"];
            $members = strtolower($_POST["members"]);
            $userID = $user->id;

            if (is_null($artistID) || !is_numeric($artistID))
                return;

            if (is_null($members) || trim($members) == "")
                return;

            $memberArray = explode(" ", $members);
            foreach ($memberArray as $member)
                if (!$this->member_exists($artistID, $member))
                    $this->save_new_member($artistID, $member, $userID);
        }

        private function save_new_member($artistID, $member, $userID)
        {
            if (!is_numeric($artistID)) return;
            if (!is_numeric($userID)) return;

            global $database;
            $database->execute("INSERT INTO artist_members (artist_id, name, created, updated, user_id) VALUES (?, ?, now(), now(), ?)"
                , array(
                    $artistID
                    , mysql_real_escape_string($member)
                    , $userID
                ));
        }

        private function member_exists($artistID, $member)
        {
            if (!is_numeric($artistID)) return;

            global $database;

            $result = $database->db->GetOne("SELECT COUNT(1) FROM artist_members WHERE artist_id = ? AND name = ?"
                , array(
                    $artistID
                    , mysql_real_escape_string($member)
                ));
            return ($result != 0);
        }

        private function url_exists($artistID, $url)
        {
            if (!is_numeric($artistID)) return;

            global $database;

            $result = $database->db->GetOne("SELECT COUNT(1) FROM artist_urls WHERE artist_id = ? AND url = ?"
                , array(
                    $artistID
                    , mysql_real_escape_string($url)
                ));
            return ($result != 0);
        }

	/*
	* HERE WE GET THE INFO OF THE ALIAS
	*/
	private function get_alias($artistID){
            if (!is_numeric($artistID)) return;

            global $database;
            
            $result = $database->get_all("SELECT id AS alias_id, alias AS alias_name ".
                                      "FROM artist_alias ".
                                      "WHERE artist_id = ? ".
                                      "ORDER BY alias ASC"
                                      , array($artistID));

            for ($i = 0 ; $i < count($result) ; $i++)
            {
                $result[$i]["alias_name"] = stripslashes($result[$i]["alias_name"]);
            }
            return $result;
	}	
}
add_event_listener(new Artists());
?>
