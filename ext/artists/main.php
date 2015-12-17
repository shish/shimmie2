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
	/** @var \Image  */
	public $image;
	/** @var \User  */
	public $user;
	/** @var string */
	public $author;

	/**
	 * @param Image $image
	 * @param User $user
	 * @param string $author
	 */
	public function __construct(Image $image, User $user, /*string*/ $author) {
        $this->image = $image;
        $this->user = $user;
        $this->author = $author;
    }
}

class Artists extends Extension {
	public function onImageInfoSet(ImageInfoSetEvent $event) {
        global $user;
		if (isset($_POST["tag_edit__author"])) {
			send_event(new AuthorSetEvent($event->image, $user, $_POST["tag_edit__author"]));
		}
	}

	public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event) {
        global $user;
        $artistName = $this->get_artistName_by_imageID($event->image->id);
        if(!$user->is_anonymous()) {
            $event->add_part($this->theme->get_author_editor_html($artistName), 42);
        }
	}

	public function onSearchTermParse(SearchTermParseEvent $event) {
		$matches = array();
		if(preg_match("/^author[=|:](.*)$/i", $event->term, $matches)) {
			$char = $matches[1];
			$event->add_querylet(new Querylet("Author = :author_char", array("author_char"=>$char)));
		}
	}

    public function onInitExt(InitExtEvent $event) {
    	global $config, $database;
                
    	if ($config->get_int("ext_artists_version") < 1) {
            $database->create_table("artists", "
					id SCORE_AIPK,
					user_id INTEGER NOT NULL,
					name VARCHAR(255) NOT NULL,
					created SCORE_DATETIME NOT NULL,
					updated SCORE_DATETIME NOT NULL,
					notes TEXT,
					FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
					");
			
            $database->create_table("artist_members", "
					id SCORE_AIPK,
					artist_id INTEGER NOT NULL,
					user_id INTEGER NOT NULL,
					name VARCHAR(255) NOT NULL,
					created SCORE_DATETIME NOT NULL,
					updated SCORE_DATETIME NOT NULL,
					FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
					FOREIGN KEY (artist_id) REFERENCES artists (id) ON UPDATE CASCADE ON DELETE CASCADE
					");
            $database->create_table("artist_alias", "
					id SCORE_AIPK,
					artist_id INTEGER NOT NULL,
					user_id INTEGER NOT NULL,
					created SCORE_DATETIME,
					updated SCORE_DATETIME,
					alias VARCHAR(255),
					FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
					FOREIGN KEY (artist_id) REFERENCES artists (id) ON UPDATE CASCADE ON DELETE CASCADE
					");
            $database->create_table("artist_urls", "
					id SCORE_AIPK,
					artist_id INTEGER NOT NULL,
					user_id INTEGER NOT NULL,
					created SCORE_DATETIME NOT NULL,
					updated SCORE_DATETIME NOT NULL,
					url VARCHAR(1000) NOT NULL,
					FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
					FOREIGN KEY (artist_id) REFERENCES artists (id) ON UPDATE CASCADE ON DELETE CASCADE
					");
            $database->execute("ALTER TABLE images ADD COLUMN author VARCHAR(255) NULL");

            $config->set_int("artistsPerPage", 20);
            $config->set_int("ext_artists_version", 1);

            log_info("artists", "extension installed");
        }
    }

    public function onAuthorSet(AuthorSetEvent $event) {
        global $database;

        $author = strtolower($event->author);
        if (strlen($author) === 0 || strpos($author, " "))
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

        if (!is_null($artistID)) {
            $artistName = $this->get_artistName_by_artistID($artistID);
        }
        else {
            $this->save_new_artist($author, "");
            $artistName = $author;
        }

        $database->execute(
            "UPDATE images SET author = ? WHERE id = ?",
            array($artistName, $event->image->id)
        );
    }

    public function onPageRequest(PageRequestEvent $event) {
        global $page, $user;

        if($event->page_matches("artist")) {
            switch($event->get_arg(0)) {
                //*************ARTIST SECTION**************
                case "list":
                {
                    $this->get_listing($page, $event);
                    $this->theme->sidebar_options("neutral");
                    break;
                }
                case "new":
                {
                    if(!$user->is_anonymous()) {
                    	$this->theme->new_artist_composer();
                    }
                    else {
                        $this->theme->display_error(401, "Error", "You must be registered and logged in to create a new artist.");
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
                    if(!$user->is_anonymous()) {
                        $newArtistID = $this->add_artist();
                        if ($newArtistID == -1) {
                            $this->theme->display_error(400, "Error", "Error when entering artist data.");
                        }
                        else {
                            $page->set_mode("redirect");
                            $page->set_redirect(make_link("artist/view/".$newArtistID));
                        }
                    }
                    else {
                        $this->theme->display_error(401, "Error", "You must be registered and logged in to create a new artist.");
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
                    if ($userIsLogged) {
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
					
                    if(!$user->is_anonymous()) {
                    	$this->theme->show_artist_editor($artist, $aliases, $members, $urls);
						
                        $userIsAdmin = $user->is_admin();
                        $this->theme->sidebar_options("editor", $artistID, $userIsAdmin);
                    }
                    else {
                        $this->theme->display_error(401, "Error", "You must be registered and logged in to edit an artist.");
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
                    $this->delete_artist($artistID); // this will delete the artist, its alias, its urls and its members
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

    /**
     * @param int $imageID
     * @return string
     */
    private function get_artistName_by_imageID($imageID) {
        assert(is_numeric($imageID));

        global $database;
        $result = $database->get_row("SELECT author FROM images WHERE id = ?", array($imageID));
        return stripslashes($result['author']);
    }

    /**
     * @param string $url
     * @return bool
     */
    private function url_exists_by_url($url) {
        global $database;
        $result = $database->get_one("SELECT COUNT(1) FROM artist_urls WHERE url = ?", array($url));
        return ($result != 0);
    }

    /**
     * @param string $member
     * @return bool
     */
    private function member_exists_by_name($member) {
        global $database;
        $result = $database->get_one("SELECT COUNT(1) FROM artist_members WHERE name = ?", array($member));
        return ($result != 0);
    }

    /**
     * @param string $alias
     * @return bool
     */
    private function alias_exists_by_name($alias) {
        global $database;

        $result = $database->get_one("SELECT COUNT(1) FROM artist_alias WHERE alias = ?", array($alias));
        return ($result != 0);
    }

    /**
     * @param int $artistID
     * @param string $alias
     * @return bool
     */
    private function alias_exists($artistID, $alias) {
        assert(is_numeric($artistID));

        global $database;
        $result = $database->get_one(
            "SELECT COUNT(1) FROM artist_alias WHERE artist_id = ? AND alias = ?",
            array($artistID, $alias)
        );
        return ($result != 0);
    }

    /**
     * @param string $url
     * @return int
     */
    private function get_artistID_by_url($url) {
        global $database;
        return $database->get_one("SELECT artist_id FROM artist_urls WHERE url = ?", array($url));
    }

    /**
     * @param string $member
     * @return int
     */
    private function get_artistID_by_memberName($member) {
        global $database;
        return $database->get_one("SELECT artist_id FROM artist_members WHERE name = ?", array($member));
    }

    /**
     * @param int $artistID
     * @return string
     */
    private function get_artistName_by_artistID($artistID) {
        assert(is_numeric($artistID));

        global $database;
        return $database->get_one("SELECT name FROM artists WHERE id = ?", array($artistID));
    }

    /**
     * @param int $aliasID
     * @return int
     */
    private function get_artistID_by_aliasID($aliasID) {
        assert(is_numeric($aliasID));

        global $database;
        return $database->get_one("SELECT artist_id FROM artist_alias WHERE id = ?", array($aliasID));
    }

    /**
     * @param int $memberID
     * @return int
     */
    private function get_artistID_by_memberID($memberID) {
        assert(is_numeric($memberID));

        global $database;
        return $database->get_one("SELECT artist_id FROM artist_members WHERE id = ?", array($memberID));
    }

    /**
     * @param int $urlID
     * @return int
     */
    private function get_artistID_by_urlID($urlID) {
        assert(is_numeric($urlID));

        global $database;
        return $database->get_one("SELECT artist_id FROM artist_urls WHERE id = ?", array($urlID));
    }

    /**
     * @param int $aliasID
     */
    private function delete_alias($aliasID) {
        assert(is_numeric($aliasID));

        global $database;
        $database->execute("DELETE FROM artist_alias WHERE id = ?", array($aliasID));
    }

    /**
     * @param int $urlID
     */
    private function delete_url($urlID) {
        assert(is_numeric($urlID));

        global $database;
        $database->execute("DELETE FROM artist_urls WHERE id = ?", array($urlID));
    }

    /**
     * @param int $memberID
     */
    private function delete_member($memberID) {
        assert(is_numeric($memberID));

        global $database;
        $database->execute("DELETE FROM artist_members WHERE id = ?", array($memberID));
    }

    /**
     * @param int $aliasID
     * @return array
     */
    private function get_alias_by_id($aliasID) {
        assert(is_numeric($aliasID));

        global $database;
        $result = $database->get_row("SELECT * FROM artist_alias WHERE id = ?", array($aliasID));
        $result["alias"] = stripslashes($result["alias"]);
        return $result;
    }

    /**
     * @param int $urlID
     * @return array
     */
    private function get_url_by_id($urlID) {
        assert(is_numeric($urlID));

        global $database;
        $result = $database->get_row("SELECT * FROM artist_urls WHERE id = ?", array($urlID));
        $result["url"] = stripslashes($result["url"]);
        return $result;
    }

    /**
     * @param int $memberID
     * @return array
     */
    private function get_member_by_id($memberID) {
        assert(is_numeric($memberID));

        global $database;
        $result = $database->get_row("SELECT * FROM artist_members WHERE id = ?", array($memberID));
        $result["name"] = stripslashes($result["name"]);
        return $result;
    }

    private function update_artist() {
        global $user;
        $inputs = validate_input(array(
            'id' => 'int',
            'name' => 'string,lower',
            'notes' => 'string,trim,nullify',
            'aliases' => 'string,trim,nullify',
            'aliasesIDs' => 'string,trim,nullify',
            'members' => 'string,trim,nullify',
        ));
        $artistID = $inputs['id'];
        $name = $inputs['name'];
        $notes = $inputs['notes'];
        $userID = $user->id;

        $aliasesAsString = $inputs["aliases"];
        $aliasesIDsAsString = $inputs["aliasesIDs"];

        $membersAsString = $inputs["members"];
        $membersIDsAsString = $inputs["membersIDs"];

        $urlsAsString = $inputs["urls"];
        $urlsIDsAsString = $inputs["urlsIDs"];

        if(strpos($name, " "))
            return;

        global $database;
        $database->execute(
            "UPDATE artists SET name = ?, notes = ?, updated = now(), user_id = ? WHERE id = ? ",
            array($name, $notes, $userID, $artistID)
        );

        // ALIAS MATCHING SECTION
        $i = 0;
        $aliasesAsArray = is_null($aliasesAsString) ? array() : explode(" ", $aliasesAsString);
        $aliasesIDsAsArray = is_null($aliasesIDsAsString) ? array() : explode(" ", $aliasesIDsAsString);
        while ($i < count($aliasesAsArray))
        {
            // if an alias was updated
            if ($i < count($aliasesIDsAsArray))
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
                $this->save_existing_member($membersIDsAsArray[$i], $membersAsArray[$i], $userID);
            else
                // if we already updated all, save new ones
                $this->save_new_member($artistID, $membersAsArray[$i], $userID);

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
            if ($i < count($urlsIDsAsArray)) {
                $this->save_existing_url($urlsIDsAsArray[$i], $urlsAsArray[$i], $userID);
            }
            else {
                $this->save_new_url($artistID, $urlsAsArray[$i], $userID);
            }

            $i++;
        }
        
        // if we have more ids than urls, then some urls have been deleted -- delete them from db
        while ($i < count($urlsIDsAsArray))
            $this->delete_url($urlsIDsAsArray[$i++]);
    }

    private function update_alias() {
        global $user;
        $inputs = validate_input(array(
            "aliasID" => "int",
            "alias" => "string,lower",
        ));
        $this->save_existing_alias($inputs['aliasID'], $inputs['alias'], $user->id);
    }

    /**
     * @param int $aliasID
     * @param string $alias
     * @param int $userID
     */
    private function save_existing_alias($aliasID, $alias, $userID) {
        assert(is_numeric($userID));
        assert(is_numeric($aliasID));

        global $database;
        $database->execute(
            "UPDATE artist_alias SET alias = ?, updated = now(), user_id  = ? WHERE id = ? ",
            array($alias, $userID, $aliasID)
        );
    }

    private function update_url() {
        global $user;
        $inputs = validate_input(array(
            "urlID" => "int",
            "url" => "string",
        ));
        $this->save_existing_url($inputs['urlID'], $inputs['url'], $user->id);
    }

    /**
     * @param int $urlID
     * @param string $url
     * @param int $userID
     */
    private function save_existing_url($urlID, $url, $userID) {
        assert(is_numeric($userID));
        assert(is_numeric($urlID));

        global $database;
        $database->execute(
            "UPDATE artist_urls SET url = ?, updated = now(), user_id = ? WHERE id = ?",
            array($url, $userID, $urlID)
        );
    }

    private function update_member() {
        global $user;
        $inputs = validate_input(array(
            "memberID" => "int",
            "name" => "string,lower",
        ));
        $this->save_existing_member($inputs['memberID'], $inputs['name'], $user->id);
    }

    /**
     * @param int $memberID
     * @param string $memberName
     * @param int $userID
     */
    private function save_existing_member($memberID, $memberName, $userID) {
        assert(is_numeric($memberID));
        assert(is_numeric($userID));

        global $database;
        $database->execute(
            "UPDATE artist_members SET name = ?, updated = now(), user_id = ? WHERE id = ?",
            array($memberName, $userID, $memberID)
        );
    }

    private function add_artist(){
        global $user;
        $inputs = validate_input(array(
            "name" => "string,lower",
            "notes" => "string,optional",
            "aliases" => "string,lower,optional",
            "members" => "string,lower,optional",
            "urls" => "string,optional"
        ));

        $name = $inputs["name"];
        if(strpos($name, " "))
            return -1;

        $notes = $inputs["notes"];

        $aliases = $inputs["aliases"];
        $members = $inputs["members"];
        $urls = $inputs["urls"];
        $userID = $user->id;

        //$artistID = "";

        //// WE CHECK IF THE ARTIST ALREADY EXISTS ON DATABASE; IF NOT WE CREATE
        if(!$this->artist_exists($name)) {
            $artistID = $this->save_new_artist($name, $notes);
            log_info("artists", "Artist {$artistID} created by {$user->name}");
        }
        else {
            $artistID = $this->get_artist_id($name);
        }

        if (!is_null($aliases)) {
            $aliasArray = explode(" ", $aliases);
            foreach($aliasArray as $alias)
                if (!$this->alias_exists($artistID, $alias))
                    $this->save_new_alias($artistID, $alias, $userID);
        }

        if (!is_null($members)) {
            $membersArray = explode(" ", $members);
            foreach ($membersArray as $member)
                if (!$this->member_exists($artistID, $member))
                    $this->save_new_member($artistID, $member, $userID);
        }

        if (!is_null($urls)) {
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

    /**
     * @param string $name
     * @param string $notes
     * @return int
     */
    private function save_new_artist($name, $notes) {
        global $database, $user;
        $database->execute("
            INSERT INTO artists (user_id, name, notes, created, updated)
            VALUES (?, ?, ?, now(), now())
        ", array($user->id, $name, $notes));
        return $database->get_last_insert_id();
    }

    /**
     * @param string $name
     * @return bool
     */
    private function artist_exists($name) {
        global $database;
        $result = $database->get_one(
            "SELECT COUNT(1) FROM artists WHERE name = ?",
            array($name)
        );
        return ($result != 0);
    }

    /**
     * @param int $artistID
     * @return array
     */
    private function get_artist($artistID){
        assert(is_numeric($artistID));

        global $database;
        $result = $database->get_row(
            "SELECT * FROM artists WHERE id = ?",
            array($artistID)
        );

        $result["name"] = stripslashes($result["name"]);
        $result["notes"] = stripslashes($result["notes"]);

        return $result;
    }

    /**
     * @param int $artistID
     * @return array
     */
    private function get_members($artistID) {
        assert(is_numeric($artistID));

        global $database;
        $result = $database->get_all(
            "SELECT * FROM artist_members WHERE artist_id = ?",
            array($artistID)
        );
		
		$num = count($result);
        for ($i = 0 ; $i < $num ; $i++) {
            $result[$i]["name"] = stripslashes($result[$i]["name"]);
        }

        return $result;
    }

    /**
     * @param int $artistID
     * @return array
     */
    private function get_urls($artistID) {
        assert(is_numeric($artistID));

        global $database;
        $result = $database->get_all(
            "SELECT id, url FROM artist_urls WHERE artist_id = ?",
            array($artistID)
        );
			
		$num = count($result);
        for ($i = 0 ; $i < $num ; $i++) {
            $result[$i]["url"] = stripslashes($result[$i]["url"]);
        }

        return $result;
    }

	/**
	 * @param string $name
	 * @return int
	 */
	private function get_artist_id($name) {
		global $database;
		return (int)$database->get_one(
            "SELECT id FROM artists WHERE name = ?",
            array($name)
        );
	}

    /**
     * @param string $alias
     * @return int
     */
    private function get_artistID_by_aliasName($alias) {
        global $database;

        return (int)$database->get_one(
            "SELECT artist_id FROM artist_alias WHERE alias = ?",
            array($alias)
        );
    }


    /**
     * @param int $artistID
     */
	private function delete_artist($artistID) {
        assert(is_numeric($artistID));

        global $database;
        $database->execute(
            "DELETE FROM artists WHERE id = ? ",
            array($artistID)
        );
	}
	
	/*
	* HERE WE GET THE LIST OF ALL ARTIST WITH PAGINATION
	*/
        private function get_listing(Page $page, PageRequestEvent $event)
        {
            global $config, $database;

            $pageNumber = clamp($event->get_arg(1), 1, null) - 1;
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
			
			$number_of_listings = count($listing);

            for ($i = 0 ; $i < $number_of_listings ; $i++)
            {
                $listing[$i]["name"] = stripslashes($listing[$i]["name"]);
                $listing[$i]["user_name"] = stripslashes($listing[$i]["user_name"]);
                $listing[$i]["artist_name"] = stripslashes($listing[$i]["artist_name"]);
            }

            $count = $database->get_one("
                SELECT COUNT(1)
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
    private function add_urls() {
        global $user;
        $inputs = validate_input(array(
            "artistID" => "int",
            "urls" => "string",
        ));
        $artistID = $inputs["artistID"];
        $urls = explode("\n", $inputs["urls"]);

        foreach ($urls as $url)
            if (!$this->url_exists($artistID, $url))
                $this->save_new_url($artistID, $url, $user->id);
    }

    /**
     * @param int $artistID
     * @param string $url
     * @param int $userID
     */
    private function save_new_url($artistID, $url, $userID) {
        global $database;

        assert(is_numeric($artistID));
        assert(is_numeric($userID));

        $database->execute(
            "INSERT INTO artist_urls (artist_id, created, updated, url, user_id) VALUES (?, now(), now(), ?, ?)",
            array($artistID, $url, $userID)
        );
    }

	private function add_alias() {
        global $user;
        $inputs = validate_input(array(
            "artistID" => "int",
            "aliases" => "string,lower",
        ));
        $artistID = $inputs["artistID"];
        $aliases = explode(" ", $inputs["aliases"]);

        foreach ($aliases as $alias)
            if (!$this->alias_exists($artistID, $alias))
                $this->save_new_alias($artistID, $alias, $user->id);
    }

    /**
     * @param int $artistID
     * @param string $alias
     * @param int $userID
     */
    private function save_new_alias($artistID, $alias, $userID) {
        global $database;

        assert(is_numeric($artistID));
        assert(is_numeric($userID));

        $database->execute(
            "INSERT INTO artist_alias (artist_id, created, updated, alias, user_id) VALUES (?, now(), now(), ?, ?)",
            array($artistID, $alias, $userID)
        );
    }

    private function add_members() {
        global $user;
        $inputs = validate_input(array(
            "artistID" => "int",
            "members" => "string,lower",
        ));
        $artistID = $inputs["artistID"];
        $members = explode(" ", $inputs["members"]);

        foreach ($members as $member)
            if (!$this->member_exists($artistID, $member))
                $this->save_new_member($artistID, $member, $user->id);
    }

    /**
     * @param int $artistID
     * @param string $member
     * @param int $userID
     */
    private function save_new_member($artistID, $member, $userID) {
        global $database;

        assert(is_numeric($artistID));
        assert(is_numeric($userID));

        $database->execute(
            "INSERT INTO artist_members (artist_id, name, created, updated, user_id) VALUES (?, ?, now(), now(), ?)",
            array($artistID, $member, $userID)
        );
    }

    /**
     * @param int $artistID
     * @param string $member
     * @return bool
     */
    private function member_exists($artistID, $member) {
        global $database;

        assert(is_numeric($artistID));

        $result = $database->get_one(
            "SELECT COUNT(1) FROM artist_members WHERE artist_id = ? AND name = ?",
            array($artistID, $member)
        );
        return ($result != 0);
    }

    /**
     * @param int $artistID
     * @param string $url
     * @return bool
     */
    private function url_exists($artistID, $url) {
        global $database;

        assert(is_numeric($artistID));

        $result = $database->get_one(
            "SELECT COUNT(1) FROM artist_urls WHERE artist_id = ? AND url = ?",
            array($artistID, $url)
        );
        return ($result != 0);
    }

	/**
	 * HERE WE GET THE INFO OF THE ALIAS
     *
     * @param int $artistID
     * @return array
	 */
	private function get_alias($artistID) {
        global $database;

        assert(is_numeric($artistID));

        $result = $database->get_all("
            SELECT id AS alias_id, alias AS alias_name
            FROM artist_alias
            WHERE artist_id = ?
            ORDER BY alias ASC
        ", array($artistID));

        for ($i = 0 ; $i < count($result) ; $i++) {
            $result[$i]["alias_name"] = stripslashes($result[$i]["alias_name"]);
        }
        return $result;
	}	
}
