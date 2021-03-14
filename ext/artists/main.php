<?php declare(strict_types=1);

class AuthorSetEvent extends Event
{
    public Image $image;
    public User $user;
    public string $author;

    public function __construct(Image $image, User $user, string $author)
    {
        parent::__construct();
        $this->image = $image;
        $this->user = $user;
        $this->author = $author;
    }
}

class Artists extends Extension
{
    /** @var ArtistsTheme */
    protected ?Themelet $theme;

    public function onImageInfoSet(ImageInfoSetEvent $event)
    {
        global $user;
        if ($user->can(Permissions::EDIT_IMAGE_ARTIST) && isset($_POST["tag_edit__author"])) {
            send_event(new AuthorSetEvent($event->image, $user, $_POST["tag_edit__author"]));
        }
    }

    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event)
    {
        global $user;
        $artistName = $this->get_artistName_by_imageID($event->image->id);
        if (!$user->is_anonymous()) {
            $event->add_part($this->theme->get_author_editor_html($artistName), 42);
        }
    }

    public function onSearchTermParse(SearchTermParseEvent $event)
    {
        if (is_null($event->term)) {
            return;
        }

        $matches = [];
        if (preg_match("/^(author|artist)[=|:](.*)$/i", $event->term, $matches)) {
            $char = $matches[2];
            $event->add_querylet(new Querylet("author = :author_char", ["author_char"=>$char]));
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event)
    {
        if ($event->key===HelpPages::SEARCH) {
            $block = new Block();
            $block->header = "Artist";
            $block->body = $this->theme->get_help_html();
            $event->add_block($block);
        }
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event)
    {
        global $config, $database;

        if ($this->get_version("ext_artists_version") < 1) {
            $database->create_table("artists", "
					id SCORE_AIPK,
					user_id INTEGER NOT NULL,
					name VARCHAR(255) NOT NULL,
					created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
					updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
					notes TEXT,
					FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
					");

            $database->create_table("artist_members", "
					id SCORE_AIPK,
					artist_id INTEGER NOT NULL,
					user_id INTEGER NOT NULL,
					name VARCHAR(255) NOT NULL,
					created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
					updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
					FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
					FOREIGN KEY (artist_id) REFERENCES artists (id) ON UPDATE CASCADE ON DELETE CASCADE
					");
            $database->create_table("artist_alias", "
					id SCORE_AIPK,
					artist_id INTEGER NOT NULL,
					user_id INTEGER NOT NULL,
					created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
					updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
					alias VARCHAR(255),
					FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
					FOREIGN KEY (artist_id) REFERENCES artists (id) ON UPDATE CASCADE ON DELETE CASCADE
					");
            $database->create_table("artist_urls", "
					id SCORE_AIPK,
					artist_id INTEGER NOT NULL,
					user_id INTEGER NOT NULL,
					created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
					updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
					url VARCHAR(1000) NOT NULL,
					FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
					FOREIGN KEY (artist_id) REFERENCES artists (id) ON UPDATE CASCADE ON DELETE CASCADE
					");
            $database->execute("ALTER TABLE images ADD COLUMN author VARCHAR(255) NULL");

            $config->set_int("artistsPerPage", 20);
            $this->set_version("ext_artists_version", 1);
        }
    }

    public function onAuthorSet(AuthorSetEvent $event)
    {
        global $database;

        $author = strtolower($event->author);
        if (strlen($author) === 0 || strpos($author, " ")) {
            return;
        }

        $paddedAuthor = str_replace(" ", "_", $author);

        $artistID = null;
        if ($this->artist_exists($author)) {
            $artistID = $this->get_artist_id($author);
        }

        if (is_null($artistID) && $this->alias_exists_by_name($paddedAuthor)) {
            $artistID = $this->get_artistID_by_aliasName($paddedAuthor);
        }

        if (is_null($artistID) && $this->member_exists_by_name($paddedAuthor)) {
            $artistID = $this->get_artistID_by_memberName($paddedAuthor);
        }

        if (is_null($artistID) && $this->url_exists_by_url($author)) {
            $artistID = $this->get_artistID_by_url($author);
        }

        if (!is_null($artistID)) {
            $artistName = $this->get_artistName_by_artistID($artistID);
        } else {
            $this->save_new_artist($author, "");
            $artistName = $author;
        }

        $database->execute(
            "UPDATE images SET author = :author WHERE id = :id",
            ['author'=>$artistName, 'id'=>$event->image->id]
        );
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $user;

        if ($event->page_matches("artist")) {
            switch ($event->get_arg(0)) {
                //*************ARTIST SECTION**************
                case "list":
                {
                    $this->get_listing($page, $event);
                    $this->theme->sidebar_options("neutral");
                    break;
                }
                case "new":
                {
                    if (!$user->is_anonymous()) {
                        $this->theme->new_artist_composer();
                    } else {
                        $this->theme->display_error(401, "Error", "You must be registered and logged in to create a new artist.");
                    }
                    break;
                }
                case "new_artist":
                {
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(make_link("artist/new"));
                    break;
                }
                case "create":
                {
                    if (!$user->is_anonymous()) {
                        $newArtistID = $this->add_artist();
                        if ($newArtistID == -1) {
                            $this->theme->display_error(400, "Error", "Error when entering artist data.");
                        } else {
                            $page->set_mode(PageMode::REDIRECT);
                            $page->set_redirect(make_link("artist/view/".$newArtistID));
                        }
                    } else {
                        $this->theme->display_error(401, "Error", "You must be registered and logged in to create a new artist.");
                    }
                    break;
                }

                case "view":
                {
                    $artistID = int_escape($event->get_arg(1));
                    $artist = $this->get_artist($artistID);
                    $aliases = $this->get_alias($artist['id']);
                    $members = $this->get_members($artist['id']);
                    $urls = $this->get_urls($artist['id']);

                    $userIsLogged = !$user->is_anonymous();
                    $userIsAdmin = $user->can(Permissions::ARTISTS_ADMIN);

                    $images = Image::find_images(0, 4, Tag::explode($artist['name']));

                    $this->theme->show_artist($artist, $aliases, $members, $urls, $images, $userIsLogged, $userIsAdmin);
                    /*
                    if ($userIsLogged) {
                        $this->theme->show_new_alias_composer($artistID);
                        $this->theme->show_new_member_composer($artistID);
                        $this->theme->show_new_url_composer($artistID);
                    }
                    */

                    $this->theme->sidebar_options("editor", $artistID, $userIsAdmin);

                    break;
                }

                case "edit":
                {
                    $artistID = int_escape($event->get_arg(1));
                    $artist = $this->get_artist($artistID);
                    $aliases = $this->get_alias($artistID);
                    $members = $this->get_members($artistID);
                    $urls = $this->get_urls($artistID);

                    if (!$user->is_anonymous()) {
                        $this->theme->show_artist_editor($artist, $aliases, $members, $urls);

                        $userIsAdmin = $user->can(Permissions::ARTISTS_ADMIN);
                        $this->theme->sidebar_options("editor", $artistID, $userIsAdmin);
                    } else {
                        $this->theme->display_error(401, "Error", "You must be registered and logged in to edit an artist.");
                    }
                    break;
                }
                case "edit_artist":
                {
                    $artistID = $_POST['artist_id'];
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(make_link("artist/edit/".$artistID));
                    break;
                }
                case "edited":
                {
                    $artistID = int_escape($_POST['id']);
                    $this->update_artist();
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(make_link("artist/view/".$artistID));
                    break;
                }
                case "nuke_artist":
                {
                    $artistID = $_POST['artist_id'];
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(make_link("artist/nuke/".$artistID));
                    break;
                }
                case "nuke":
                {
                    $artistID = int_escape($event->get_arg(1));
                    $this->delete_artist($artistID); // this will delete the artist, its alias, its urls and its members
                    $page->set_mode(PageMode::REDIRECT);
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
                    switch ($event->get_arg(1)) {
                        case "add":
                        {
                            $artistID = $_POST['artistID'];
                            $this->add_alias();
                            $page->set_mode(PageMode::REDIRECT);
                            $page->set_redirect(make_link("artist/view/".$artistID));
                            break;
                        }
                        case "delete":
                        {
                            $aliasID = int_escape($event->get_arg(2));
                            $artistID = $this->get_artistID_by_aliasID($aliasID);
                            $this->delete_alias($aliasID);
                            $page->set_mode(PageMode::REDIRECT);
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
                            $page->set_mode(PageMode::REDIRECT);
                            $page->set_redirect(make_link("artist/view/".$artistID));
                            break;
                        }
                    }
                    break; // case: alias
                }

                //**************** URLS SECTION **********************
                case "url":
                {
                    switch ($event->get_arg(1)) {
                        case "add":
                        {
                            $artistID = $_POST['artistID'];
                            $this->add_urls();
                            $page->set_mode(PageMode::REDIRECT);
                            $page->set_redirect(make_link("artist/view/".$artistID));
                            break;
                        }
                        case "delete":
                        {
                            $urlID = int_escape($event->get_arg(2));
                            $artistID = $this->get_artistID_by_urlID($urlID);
                            $this->delete_url($urlID);
                            $page->set_mode(PageMode::REDIRECT);
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
                            $page->set_mode(PageMode::REDIRECT);
                            $page->set_redirect(make_link("artist/view/".$artistID));
                            break;
                        }
                    }
                    break; // case: urls
                }
                //******************* MEMBERS SECTION *********************
                case "member":
                {
                    switch ($event->get_arg(1)) {
                        case "add":
                        {
                            $artistID = $_POST['artistID'];
                            $this->add_members();
                            $page->set_mode(PageMode::REDIRECT);
                            $page->set_redirect(make_link("artist/view/".$artistID));
                            break;
                        }
                        case "delete":
                        {
                            $memberID = int_escape($event->get_arg(2));
                            $artistID = $this->get_artistID_by_memberID($memberID);
                            $this->delete_member($memberID);
                            $page->set_mode(PageMode::REDIRECT);
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
                            $page->set_mode(PageMode::REDIRECT);
                            $page->set_redirect(make_link("artist/view/".$artistID));
                            break;
                        }
                    }
                    break; //case: members
                }
            }
        }
    }

    private function get_artistName_by_imageID(int $imageID): string
    {
        global $database;
        $result = $database->get_row("SELECT author FROM images WHERE id = :id", ['id'=>$imageID]);
        return $result['author'] ?? "";
    }

    private function url_exists_by_url(string $url): bool
    {
        global $database;
        $result = $database->get_one("SELECT COUNT(1) FROM artist_urls WHERE url = :url", ['url'=>$url]);
        return ($result != 0);
    }

    private function member_exists_by_name(string $member): bool
    {
        global $database;
        $result = $database->get_one("SELECT COUNT(1) FROM artist_members WHERE name = :name", ['name'=>$member]);
        return ($result != 0);
    }

    private function alias_exists_by_name(string $alias): bool
    {
        global $database;
        $result = $database->get_one("SELECT COUNT(1) FROM artist_alias WHERE alias = :alias", ['alias'=>$alias]);
        return ($result != 0);
    }

    private function alias_exists(int $artistID, string $alias): bool
    {
        global $database;
        $result = $database->get_one(
            "SELECT COUNT(1) FROM artist_alias WHERE artist_id = :artist_id AND alias = :alias",
            ['artist_id'=>$artistID, 'alias'=>$alias]
        );
        return ($result != 0);
    }

    private function get_artistID_by_url(string $url): int
    {
        global $database;
        return (int)$database->get_one("SELECT artist_id FROM artist_urls WHERE url = :url", ['url'=>$url]);
    }

    private function get_artistID_by_memberName(string $member): int
    {
        global $database;
        return (int)$database->get_one("SELECT artist_id FROM artist_members WHERE name = :name", ['name'=>$member]);
    }

    private function get_artistName_by_artistID(int $artistID): string
    {
        global $database;
        return (string)$database->get_one("SELECT name FROM artists WHERE id = :id", ['id'=>$artistID]);
    }

    private function get_artistID_by_aliasID(int $aliasID): int
    {
        global $database;
        return (int)$database->get_one("SELECT artist_id FROM artist_alias WHERE id = :id", ['id'=>$aliasID]);
    }

    private function get_artistID_by_memberID(int $memberID): int
    {
        global $database;
        return (int)$database->get_one("SELECT artist_id FROM artist_members WHERE id = :id", ['id'=>$memberID]);
    }

    private function get_artistID_by_urlID(int $urlID): int
    {
        global $database;
        return (int)$database->get_one("SELECT artist_id FROM artist_urls WHERE id = :id", ['id'=>$urlID]);
    }

    private function delete_alias(int $aliasID)
    {
        global $database;
        $database->execute("DELETE FROM artist_alias WHERE id = :id", ['id'=>$aliasID]);
    }

    private function delete_url(int $urlID)
    {
        global $database;
        $database->execute("DELETE FROM artist_urls WHERE id = :id", ['id'=>$urlID]);
    }

    private function delete_member(int $memberID)
    {
        global $database;
        $database->execute("DELETE FROM artist_members WHERE id = :id", ['id'=>$memberID]);
    }

    private function get_alias_by_id(int $aliasID): array
    {
        global $database;
        return $database->get_row("SELECT * FROM artist_alias WHERE id = :id", ['id'=>$aliasID]);
    }

    private function get_url_by_id(int $urlID): array
    {
        global $database;
        return $database->get_row("SELECT * FROM artist_urls WHERE id = :id", ['id'=>$urlID]);
    }

    private function get_member_by_id(int $memberID): array
    {
        global $database;
        return $database->get_row("SELECT * FROM artist_members WHERE id = :id", ['id'=>$memberID]);
    }

    private function update_artist()
    {
        global $user;
        $inputs = validate_input([
            'id' => 'int',
            'name' => 'string,lower',
            'notes' => 'string,trim,nullify',
            'aliases' => 'string,trim,nullify',
            'aliasesIDs' => 'string,trim,nullify',
            'members' => 'string,trim,nullify',
        ]);
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

        if (str_contains($name, " ")) {
            return;
        }

        global $database;
        $database->execute(
            "UPDATE artists SET name = :name, notes = :notes, updated = now(), user_id = :user_id WHERE id = :id",
            ['name'=>$name, 'notes'=>$notes, 'user_id'=>$userID, 'id'=>$artistID]
        );

        // ALIAS MATCHING SECTION
        $i = 0;
        $aliasesAsArray = is_null($aliasesAsString) ? [] : explode(" ", $aliasesAsString);
        $aliasesIDsAsArray = is_null($aliasesIDsAsString) ? [] : explode(" ", $aliasesIDsAsString);
        while ($i < count($aliasesAsArray)) {
            // if an alias was updated
            if ($i < count($aliasesIDsAsArray)) {
                $this->save_existing_alias($aliasesIDsAsArray[$i], $aliasesAsArray[$i], $userID);
            } else {
                // if we already updated all, save new ones
                $this->save_new_alias($artistID, $aliasesAsArray[$i], $userID);
            }

            $i++;
        }
        // if we have more ids than alias, then some alias have been deleted -- delete them from db
        while ($i < count($aliasesIDsAsArray)) {
            $this->delete_alias($aliasesIDsAsArray[$i++]);
        }

        // MEMBERS MATCHING SECTION
        $i = 0;
        $membersAsArray = is_null($membersAsString) ? [] : explode(" ", $membersAsString);
        $membersIDsAsArray = is_null($membersIDsAsString) ? [] : explode(" ", $membersIDsAsString);
        while ($i < count($membersAsArray)) {
            // if a member was updated
            if ($i < count($membersIDsAsArray)) {
                $this->save_existing_member($membersIDsAsArray[$i], $membersAsArray[$i], $userID);
            } else {
                // if we already updated all, save new ones
                $this->save_new_member($artistID, $membersAsArray[$i], $userID);
            }

            $i++;
        }
        // if we have more ids than members, then some members have been deleted -- delete them from db
        while ($i < count($membersIDsAsArray)) {
            $this->delete_member($membersIDsAsArray[$i++]);
        }

        // URLS MATCHING SECTION
        $i = 0;
        $urlsAsString = str_replace("\r\n", "\n", $urlsAsString);
        $urlsAsString = str_replace("\n\r", "\n", $urlsAsString);
        $urlsAsArray = is_null($urlsAsString) ? [] : explode("\n", $urlsAsString);
        $urlsIDsAsArray = is_null($urlsIDsAsString) ? [] : explode(" ", $urlsIDsAsString);
        while ($i < count($urlsAsArray)) {
            // if an URL was updated
            if ($i < count($urlsIDsAsArray)) {
                $this->save_existing_url($urlsIDsAsArray[$i], $urlsAsArray[$i], $userID);
            } else {
                $this->save_new_url($artistID, $urlsAsArray[$i], $userID);
            }

            $i++;
        }

        // if we have more ids than urls, then some urls have been deleted -- delete them from db
        while ($i < count($urlsIDsAsArray)) {
            $this->delete_url($urlsIDsAsArray[$i++]);
        }
    }

    private function update_alias()
    {
        global $user;
        $inputs = validate_input([
            "aliasID" => "int",
            "alias" => "string,lower",
        ]);
        $this->save_existing_alias($inputs['aliasID'], $inputs['alias'], $user->id);
    }

    private function save_existing_alias(int $aliasID, string $alias, int $userID)
    {
        global $database;
        $database->execute(
            "UPDATE artist_alias SET alias = :alias, updated = now(), user_id = :user_id WHERE id = :id",
            ['alias'=>$alias, 'user_id'=>$userID, 'id'=>$aliasID]
        );
    }

    private function update_url()
    {
        global $user;
        $inputs = validate_input([
            "urlID" => "int",
            "url" => "string",
        ]);
        $this->save_existing_url($inputs['urlID'], $inputs['url'], $user->id);
    }

    private function save_existing_url(int $urlID, string $url, int $userID)
    {
        global $database;
        $database->execute(
            "UPDATE artist_urls SET url = :url, updated = now(), user_id = :user_id WHERE id = :id",
            ['url'=>$url, 'user_id'=>$userID, 'id'=>$urlID]
        );
    }

    private function update_member()
    {
        global $user;
        $inputs = validate_input([
            "memberID" => "int",
            "name" => "string,lower",
        ]);
        $this->save_existing_member($inputs['memberID'], $inputs['name'], $user->id);
    }

    private function save_existing_member(int $memberID, string $memberName, int $userID)
    {
        global $database;
        $database->execute(
            "UPDATE artist_members SET name = :name, updated = now(), user_id = :user_id WHERE id = :id",
            ['name'=>$memberName, 'user_id'=>$userID, 'id'=>$memberID]
        );
    }

    private function add_artist(): int
    {
        global $user;
        $inputs = validate_input([
            "name" => "string,lower",
            "notes" => "string,optional",
            "aliases" => "string,lower,optional",
            "members" => "string,lower,optional",
            "urls" => "string,optional"
        ]);

        $name = $inputs["name"];
        if (str_contains($name, " ")) {
            return -1;
        }

        $notes = $inputs["notes"];

        $aliases = $inputs["aliases"];
        $members = $inputs["members"];
        $urls = $inputs["urls"];
        $userID = $user->id;

        //$artistID = "";

        //// WE CHECK IF THE ARTIST ALREADY EXISTS ON DATABASE; IF NOT WE CREATE
        if (!$this->artist_exists($name)) {
            $artistID = $this->save_new_artist($name, $notes);
            log_info("artists", "Artist {$artistID} created by {$user->name}");
        } else {
            $artistID = $this->get_artist_id($name);
        }

        if (!is_null($aliases)) {
            $aliasArray = explode(" ", $aliases);
            foreach ($aliasArray as $alias) {
                if (!$this->alias_exists($artistID, $alias)) {
                    $this->save_new_alias($artistID, $alias, $userID);
                }
            }
        }

        if (!is_null($members)) {
            $membersArray = explode(" ", $members);
            foreach ($membersArray as $member) {
                if (!$this->member_exists($artistID, $member)) {
                    $this->save_new_member($artistID, $member, $userID);
                }
            }
        }

        if (!is_null($urls)) {
            //delete double "separators"
            $urls = str_replace("\r\n", "\n", $urls);
            $urls = str_replace("\n\r", "\n", $urls);

            $urlsArray = explode("\n", $urls);
            foreach ($urlsArray as $url) {
                if (!$this->url_exists($artistID, $url)) {
                    $this->save_new_url($artistID, $url, $userID);
                }
            }
        }
        return $artistID;
    }

    private function save_new_artist(string $name, string $notes): int
    {
        global $database, $user;
        $database->execute("
            INSERT INTO artists (user_id, name, notes, created, updated)
            VALUES (:user_id, :name, :notes, now(), now())
        ", ['user_id'=>$user->id, 'name'=>$name, 'notes'=>$notes]);
        return $database->get_last_insert_id('artists_id_seq');
    }

    private function artist_exists(string $name): bool
    {
        global $database;
        $result = $database->get_one(
            "SELECT COUNT(1) FROM artists WHERE name = :name",
            ['name'=>$name]
        );
        return ($result != 0);
    }

    private function get_artist(int $artistID): array
    {
        global $database;
        $result = $database->get_row(
            "SELECT * FROM artists WHERE id = :id",
            ['id'=>$artistID]
        );

        $result["name"] = stripslashes($result["name"]);
        $result["notes"] = stripslashes($result["notes"]);

        return $result;
    }

    private function get_members(int $artistID): array
    {
        global $database;
        $result = $database->get_all(
            "SELECT * FROM artist_members WHERE artist_id = :artist_id",
            ['artist_id'=>$artistID]
        );

        $num = count($result);
        for ($i = 0 ; $i < $num ; $i++) {
            $result[$i]["name"] = stripslashes($result[$i]["name"]);
        }

        return $result;
    }

    private function get_urls(int $artistID): array
    {
        global $database;
        $result = $database->get_all(
            "SELECT id, url FROM artist_urls WHERE artist_id = :artist_id",
            ['artist_id'=>$artistID]
        );

        $num = count($result);
        for ($i = 0 ; $i < $num ; $i++) {
            $result[$i]["url"] = stripslashes($result[$i]["url"]);
        }

        return $result;
    }

    private function get_artist_id(string $name): int
    {
        global $database;
        return (int)$database->get_one(
            "SELECT id FROM artists WHERE name = :name",
            ['name'=>$name]
        );
    }

    private function get_artistID_by_aliasName(string $alias): int
    {
        global $database;

        return (int)$database->get_one(
            "SELECT artist_id FROM artist_alias WHERE alias = :alias",
            ['alias'=>$alias]
        );
    }

    private function delete_artist(int $artistID)
    {
        global $database;
        $database->execute(
            "DELETE FROM artists WHERE id = :id",
            ['id'=>$artistID]
        );
    }

    /*
    * HERE WE GET THE LIST OF ALL ARTIST WITH PAGINATION
    */
    private function get_listing(Page $page, PageRequestEvent $event)
    {
        global $config, $database;

        $pageNumber = clamp(int_escape($event->get_arg(1)), 1, null) - 1;
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
                LIMIT :offset, :limit
            ",
            [
                "offset"=>$pageNumber * $artistsPerPage,
                "limit"=>$artistsPerPage
            ]
        );

        $number_of_listings = count($listing);

        for ($i = 0 ; $i < $number_of_listings ; $i++) {
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

        $totalPages = ceil($count / $artistsPerPage);

        $this->theme->list_artists($listing, $pageNumber + 1, $totalPages);
    }

    /*
    * HERE WE ADD AN ALIAS
    */
    private function add_urls()
    {
        global $user;
        $inputs = validate_input([
            "artistID" => "int",
            "urls" => "string",
        ]);
        $artistID = $inputs["artistID"];
        $urls = explode("\n", $inputs["urls"]);

        foreach ($urls as $url) {
            if (!$this->url_exists($artistID, $url)) {
                $this->save_new_url($artistID, $url, $user->id);
            }
        }
    }

    private function save_new_url(int $artistID, string $url, int $userID)
    {
        global $database;

        $database->execute(
            "INSERT INTO artist_urls (artist_id, created, updated, url, user_id) VALUES (:artist_id, now(), now(), :url, :user_id)",
            ['artist'=>$artistID, 'url'=>$url, 'user_id'=>$userID]
        );
    }

    private function add_alias()
    {
        global $user;
        $inputs = validate_input([
            "artistID" => "int",
            "aliases" => "string,lower",
        ]);
        $artistID = $inputs["artistID"];
        $aliases = explode(" ", $inputs["aliases"]);

        foreach ($aliases as $alias) {
            if (!$this->alias_exists($artistID, $alias)) {
                $this->save_new_alias($artistID, $alias, $user->id);
            }
        }
    }

    private function save_new_alias(int $artistID, string $alias, int $userID)
    {
        global $database;

        $database->execute(
            "INSERT INTO artist_alias (artist_id, created, updated, alias, user_id) VALUES (:artist_id, now(), now(), :alias, :user_id)",
            ['artist_id'=>$artistID, 'alias'=>$alias, 'user_id'=>$userID]
        );
    }

    private function add_members()
    {
        global $user;
        $inputs = validate_input([
            "artistID" => "int",
            "members" => "string,lower",
        ]);
        $artistID = $inputs["artistID"];
        $members = explode(" ", $inputs["members"]);

        foreach ($members as $member) {
            if (!$this->member_exists($artistID, $member)) {
                $this->save_new_member($artistID, $member, $user->id);
            }
        }
    }

    private function save_new_member(int $artistID, string $member, int $userID)
    {
        global $database;

        $database->execute(
            "INSERT INTO artist_members (artist_id, name, created, updated, user_id) VALUES (:artist_id, :name, now(), now(), :user_id)",
            ['artist'=>$artistID, 'name'=>$member, 'user_id'=>$userID]
        );
    }

    private function member_exists(int $artistID, string $member): bool
    {
        global $database;

        $result = $database->get_one(
            "SELECT COUNT(1) FROM artist_members WHERE artist_id = :artist_id AND name = :name",
            ['artist_id'=>$artistID, 'name'=>$member]
        );
        return ($result != 0);
    }

    private function url_exists(int $artistID, string $url): bool
    {
        global $database;

        $result = $database->get_one(
            "SELECT COUNT(1) FROM artist_urls WHERE artist_id = :artist_id AND url = :url",
            ['artist_id'=>$artistID, 'url'=>$url]
        );
        return ($result != 0);
    }

    /**
     * HERE WE GET THE INFO OF THE ALIAS
     */
    private function get_alias(int $artistID): array
    {
        global $database;

        $result = $database->get_all("
            SELECT id AS alias_id, alias AS alias_name
            FROM artist_alias
            WHERE artist_id = :artist_id
            ORDER BY alias ASC
        ", ['artist_id'=>$artistID]);

        for ($i = 0 ; $i < count($result) ; $i++) {
            $result[$i]["alias_name"] = stripslashes($result[$i]["alias_name"]);
        }
        return $result;
    }
}
