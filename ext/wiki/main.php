<?php declare(strict_types=1);

class WikiUpdateEvent extends Event
{
    public User $user;
    public WikiPage $wikipage;

    public function __construct(User $user, WikiPage $wikipage)
    {
        parent::__construct();
        $this->user = $user;
        $this->wikipage = $wikipage;
    }
}

class WikiDeleteRevisionEvent extends Event
{
    public string $title;
    public int $revision;

    public function __construct(string $title, int $revision)
    {
        parent::__construct();
        $this->title = $title;
        $this->revision = $revision;
    }
}

class WikiDeletePageEvent extends Event
{
    public string $title;

    public function __construct(string $title)
    {
        parent::__construct();
        $this->title = $title;
    }
}

class WikiUpdateException extends SCoreException
{
}

class WikiPage
{
    public int $id;
    public int $owner_id;
    public string $owner_ip;
    public string $date;
    public string $title;
    public int $revision;
    public bool $locked;
    public string $body;

    public function __construct(array $row=null)
    {
        //assert(!empty($row));

        if (!is_null($row)) {
            $this->id = (int)$row['id'];
            $this->owner_id = (int)$row['owner_id'];
            $this->owner_ip = $row['owner_ip'];
            $this->date = $row['date'];
            $this->title = $row['title'];
            $this->revision = (int)$row['revision'];
            $this->locked = bool_escape($row['locked']);
            $this->body = $row['body'];
        }
    }

    public function get_owner(): User
    {
        return User::by_id($this->owner_id);
    }

    public function is_locked(): bool
    {
        return $this->locked;
    }
}

abstract class WikiConfig
{
    const TAG_PAGE_TEMPLATE = "wiki_tag_page_template";
    const EMPTY_TAGINFO = "wiki_empty_taginfo";
    const TAG_SHORTWIKIS = "shortwikis_on_tags";
    const ENABLE_REVISIONS = "wiki_revisions";
}

class Wiki extends Extension
{
    /** @var WikiTheme */
    protected ?Themelet $theme;

    public function onInitExt(InitExtEvent $event)
    {
        global $config;
        $config->set_default_string(
            WikiConfig::TAG_PAGE_TEMPLATE,
            "{body}

[b]Aliases: [/b][i]{aliases}[/i]
[b]Auto tags: [/b][i]{autotags}[/i]"
        );
        $config->set_default_string(WikiConfig::EMPTY_TAGINFO, "none");
        $config->set_default_bool(WikiConfig::TAG_SHORTWIKIS, false);
        $config->set_default_bool(WikiConfig::ENABLE_REVISIONS, true);
    }

    // Add a block to the Board Config / Setup
    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = $event->panel->create_new_block("Wiki");
        $sb->add_bool_option(WikiConfig::ENABLE_REVISIONS, "Enable wiki revisions: ");
        $sb->add_longtext_option(WikiConfig::TAG_PAGE_TEMPLATE, "Tag page template: ");
        $sb->add_text_option(WikiConfig::EMPTY_TAGINFO, "Empty list text: ");
        $sb->add_bool_option(WikiConfig::TAG_SHORTWIKIS, "Show shortwiki entry when searching for a single tag: ");
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event)
    {
        global $database;

        if ($this->get_version("ext_wiki_version") < 1) {
            $database->create_table("wiki_pages", "
				id SCORE_AIPK,
				owner_id INTEGER NOT NULL,
				owner_ip SCORE_INET NOT NULL,
				date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				title VARCHAR(255) NOT NULL,
				revision INTEGER NOT NULL DEFAULT 1,
				locked BOOLEAN NOT NULL DEFAULT FALSE,
				body TEXT NOT NULL,
				UNIQUE (title, revision),
				FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT
			");
            $this->set_version("ext_wiki_version", 3);
        }
        if ($this->get_version("ext_wiki_version") < 2) {
            $database->execute("ALTER TABLE wiki_pages ADD COLUMN
				locked ENUM('Y', 'N') DEFAULT 'N' NOT NULL AFTER REVISION");
            $this->set_version("ext_wiki_version", 2);
        }
        if ($this->get_version("ext_wiki_version") < 3) {
            $database->standardise_boolean("wiki_pages", "locked", true);
            $this->set_version("ext_wiki_version", 3);
        }
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $user;
        if ($event->page_matches("wiki")) {
            if ($event->count_args() == 0 || strlen(trim($event->get_arg(0))) === 0) {
                $title = "Index";
            } else {
                $title = $event->get_arg(0);
            }

            $content = $this->get_page($title);
            $this->theme->display_page($page, $content, $this->get_page("wiki:sidebar"));
        } elseif ($event->page_matches("wiki_admin/edit")) {
            $content = $this->get_page($_POST['title']);
            $this->theme->display_page_editor($page, $content);
        } elseif ($event->page_matches("wiki_admin/save")) {
            $title = $_POST['title'];
            $rev = int_escape($_POST['revision']);
            $body = $_POST['body'];
            $lock = $user->can(Permissions::WIKI_ADMIN) && isset($_POST['lock']) && ($_POST['lock'] == "on");

            if ($this->can_edit($user, $this->get_page($title))) {
                $wikipage = $this->get_page($title);
                $wikipage->revision = $rev;
                $wikipage->body = $body;
                $wikipage->locked = $lock;
                try {
                    send_event(new WikiUpdateEvent($user, $wikipage));

                    $u_title = url_escape($title);
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(make_link("wiki/$u_title"));
                } catch (WikiUpdateException $e) {
                    $original = $this->get_page($title);
                    // @ because arr_diff is full of warnings
                    $original->body = @$this->arr_diff(
                        explode("\n", $original->body),
                        explode("\n", $wikipage->body)
                    );
                    $this->theme->display_page_editor($page, $original);
                }
            } else {
                $this->theme->display_permission_denied();
            }
        } elseif ($event->page_matches("wiki_admin/delete_revision")) {
            if ($user->can(Permissions::WIKI_ADMIN)) {
                send_event(new WikiDeleteRevisionEvent($_POST["title"], (int)$_POST["revision"]));
                $u_title = url_escape($_POST["title"]);
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link("wiki/$u_title"));
            }
        } elseif ($event->page_matches("wiki_admin/delete_all")) {
            if ($user->can(Permissions::WIKI_ADMIN)) {
                send_event(new WikiDeletePageEvent($_POST["title"]));
                $u_title = url_escape($_POST["title"]);
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link("wiki/$u_title"));
            }
        }
    }


    public function onPageNavBuilding(PageNavBuildingEvent $event)
    {
        $event->add_nav_link("wiki", new Link('wiki'), "Wiki");
    }


    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event)
    {
        if ($event->parent=="wiki") {
            $event->add_nav_link("wiki_rules", new Link('wiki/rules'), "Rules");
            $event->add_nav_link("wiki_help", new Link('ext_doc/wiki'), "Help");
        }
    }

    public function onWikiUpdate(WikiUpdateEvent $event)
    {
        global $database, $config;
        $wpage = $event->wikipage;

        $exists = $database->exists("SELECT id FROM wiki_pages WHERE title = :title", ["title"=>$wpage->title]);

        try {
            if ($config->get_bool(WikiConfig::ENABLE_REVISIONS) || ! $exists) {
                $database->execute(
                    "
                                INSERT INTO wiki_pages(owner_id, owner_ip, date, title, revision, locked, body)
                                VALUES (:owner_id, :owner_ip, now(), :title, :revision, :locked, :body)",
                    ["owner_id"=>$event->user->id, "owner_ip"=>$_SERVER['REMOTE_ADDR'],
                    "title"=>$wpage->title, "revision"=>$wpage->revision, "locked"=>$wpage->locked, "body"=>$wpage->body]
                );
            } else {
                $database->execute(
                    "
                                UPDATE wiki_pages SET owner_id=:owner_id, owner_ip=:owner_ip, date=now(), locked=:locked, body=:body
                                WHERE title = :title ORDER BY revision DESC LIMIT 1",
                    ["owner_id"=>$event->user->id, "owner_ip"=>$_SERVER['REMOTE_ADDR'],
                    "title"=>$wpage->title, "locked"=>$wpage->locked, "body"=>$wpage->body]
                );
            }
        } catch (Exception $e) {
            throw new WikiUpdateException("Somebody else edited that page at the same time :-(");
        }
    }

    public function onWikiDeleteRevision(WikiDeleteRevisionEvent $event)
    {
        global $database;
        $database->execute(
            "DELETE FROM wiki_pages WHERE title=:title AND revision=:rev",
            ["title"=>$event->title, "rev"=>$event->revision]
        );
    }

    public function onWikiDeletePage(WikiDeletePageEvent $event)
    {
        global $database;
        $database->execute(
            "DELETE FROM wiki_pages WHERE title=:title",
            ["title" => $event->title]
        );
    }

    /**
     * See if the given user is allowed to edit the given page.
     */
    public static function can_edit(User $user, WikiPage $page): bool
    {
        // admins can edit everything
        if ($user->can(Permissions::WIKI_ADMIN)) {
            return true;
        }

        // anon / user can't ever edit locked pages
        if ($page->is_locked()) {
            return false;
        }

        // anon / user can edit if allowed by config
        if ($user->can(Permissions::EDIT_WIKI_PAGE)) {
            return true;
        }

        return false;
    }

    public static function get_page(string $title, int $revision=-1): WikiPage
    {
        global $database;
        // first try and get the actual page
        $row = $database->get_row(
            "
				SELECT *
				FROM wiki_pages
				WHERE LOWER(title) LIKE LOWER(:title)
				ORDER BY revision DESC
			",
            ["title"=>$title]
        );

        // fall back to wiki:default
        if (empty($row)) {
            $row = $database->get_row("
                SELECT *
                FROM wiki_pages
                WHERE title LIKE :title
                ORDER BY revision DESC
			", ["title"=>"wiki:default"]);

            // fall further back to manual
            if (empty($row)) {
                $row = [
                    "id" => -1,
                    "owner_ip" => "0.0.0.0",
                    "date" => "",
                    "revision" => 0,
                    "locked" => false,
                    "body" => "This is a default page for when a page is empty, ".
                        "it can be edited by editing [[wiki:default]].",
                ];
            }

            // correct the default
            global $config;
            $row["title"] = $title;
            $row["owner_id"] = $config->get_int("anon_id", 0);
        }

        assert(!empty($row));

        return new WikiPage($row);
    }

    public static function format_tag_wiki_page(WikiPage $page)
    {
        global $database, $config;

        $row = $database->get_row("
                SELECT *
                FROM tags
                WHERE tag = :title
                    ", ["title"=>$page->title]);

        if (!empty($row)) {
            $template = $config->get_string(WikiConfig::TAG_PAGE_TEMPLATE);

            //CATEGORIES
            if (class_exists("TagCategories")) {
                $tagcategories = new TagCategories;
                $tag_category_dict = $tagcategories->getKeyedDict();
            }

            //ALIASES
            $aliases = $database->get_col("
                SELECT oldtag
                FROM aliases
                WHERE newtag = :title
                ORDER BY oldtag ASC
                    ", ["title"=>$row["tag"]]);

            if (!empty($aliases)) {
                $template = str_replace("{aliases}", implode(", ", $aliases), $template);
            } else {
                $template = str_replace("{aliases}", $config->get_string(WikiConfig::EMPTY_TAGINFO), $template);
            }

            //Things before this line will be passed through html_escape.
            $template = format_text($template);
            //Things after this line will NOT be escaped!!! Be careful what you add.

            if (class_exists("AutoTagger")) {
                $auto_tags = $database->get_one("
                    SELECT additional_tags
                    FROM auto_tag
                    WHERE tag = :title
                        ", ["title"=>$row["tag"]]);

                if (!empty($auto_tags)) {
                    $auto_tags = Tag::explode($auto_tags);
                    $f_auto_tags = [];

                    $tag_list_t = new TagListTheme;

                    foreach ($auto_tags as $a_tag) {
                        $a_row = $database->get_row("
                            SELECT *
                            FROM tags
                            WHERE tag = :title
                                ", ["title"=>$a_tag]);

                        $tag_html = $tag_list_t->return_tag($a_row, $tag_category_dict ?? []);
                        array_push($f_auto_tags, $tag_html[1]);
                    }

                    $template = str_replace("{autotags}", implode(", ", $f_auto_tags), $template);
                } else {
                    $template = str_replace("{autotags}", format_text($config->get_string(WikiConfig::EMPTY_TAGINFO)), $template);
                }
            }
        }

        //Insert page body AT LAST to avoid replacing its contents with the actions above.
        return str_replace("{body}", format_text($page->body), $template ?? "{body}");
    }

    /**
        Diff implemented in pure php, written from scratch.
        Copyright (C) 2003  Daniel Unterberger <diff.phpnet@holomind.de>

        This program is free software; you can redistribute it and/or
        modify it under the terms of the GNU General Public License
        as published by the Free Software Foundation; either version 2
        of the License, or (at your option) any later version.

        This program is distributed in the hope that it will be useful,
        but WITHOUT ANY WARRANTY; without even the implied warranty of
        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
        GNU General Public License for more details.

        You should have received a copy of the GNU General Public License
        along with this program; if not, write to the Free Software
        Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

        https://www.gnu.org/licenses/gpl.html

        About:
        I searched a function to compare arrays and the array_diff()
        was not specific enough. It ignores the order of the array-values.
        So I reimplemented the diff-function which is found on unix-systems
        but this you can use directly in your code and adopt for your needs.
        Simply adopt the formatline-function. with the third-parameter of arr_diff()
        you can hide matching lines. Hope someone has use for this.

        Contact: d.u.diff@holomind.de <daniel unterberger>
    **/

    private function arr_diff(array $f1, array $f2, int $show_equal = 0): string
    {
        $c1         = 0 ;                   # current line of left
        $c2         = 0 ;                   # current line of right
        $max1       = count($f1) ;          # maximal lines of left
        $max2       = count($f2) ;          # maximal lines of right
        $outcount   = 0;                    # output counter
        $hit1       = [];                   # hit in left
        $hit2       = [];                   # hit in right
        $stop       = 0;
        $out        = "";

        while (
                $c1 < $max1                 # have next line in left
                and
                $c2 < $max2                 # have next line in right
                and
                ($stop++) < 1000            # don-t have more then 1000 ( loop-stopper )
                and
                $outcount < 20              # output count is less then 20
              ) {
            /**
            *   is the trimmed line of the current left and current right line
            *   the same ? then this is a hit (no difference)
            */
            if (trim($f1[$c1]) == trim($f2[$c2])) {
                /**
                *   add to output-string, if "show_equal" is enabled
                */
                $out    .= ($show_equal==1)
                         ?  $this->formatline(($c1), ($c2), "=", $f1[ $c1 ])
                         : "" ;
                /**
                *   increase the out-putcounter, if "show_equal" is enabled
                *   this ist more for demonstration purpose
                */
                if ($show_equal == 1) {
                    $outcount++ ;
                }

                /**
                *   move the current-pointer in the left and right side
                */
                $c1 ++;
                $c2 ++;
            }

            /**
            *   the current lines are different so we search in parallel
            *   on each side for the next matching pair, we walk on both
            *   sided at the same time comparing with the current-lines
            *   this should be most probable to find the next matching pair
            *   we only search in a distance of 10 lines, because then it
            *   is not the same function most of the time. other algos
            *   would be very complicated, to detect 'real' block movements.
            */
            else {
                $b      = "" ;
                $s1     = 0  ;      # search on left
                $s2     = 0  ;      # search on right
                $found  = 0  ;      # flag, found a matching pair
                $b1     = "" ;
                $b2     = "" ;
                $fstop  = 0  ;      # distance of maximum search

                #fast search in on both sides for next match.
                while (
                        $found == 0             # search until we find a pair
                        and
                        ($c1 + $s1 <= $max1)  # and we are inside of the left lines
                        and
                        ($c2 + $s2 <= $max2)  # and we are inside of the right lines
                        and
                        $fstop++  < 10          # and the distance is lower than 10 lines
                      ) {

                    /**
                    *   test the left side for a hit
                    *
                    *   comparing current line with the searching line on the left
                    *   b1 is a buffer, which collects the line which not match, to
                    *   show the differences later, if one line hits, this buffer will
                    *   be used, else it will be discarded later
                    */
                    #hit
                    if (trim($f1[$c1+$s1]) == trim($f2[$c2])) {
                        $found  = 1   ;     # set flag to stop further search
                        $s2     = 0   ;     # reset right side search-pointer
                        $c2--         ;     # move back the current right, so next loop hits
                        $b      = $b1 ;     # set b=output (b)uffer
                    }
                    #no hit: move on
                    else {
                        /**
                        *   prevent finding a line again, which would show wrong results
                        *
                        *   add the current line to leftbuffer, if this will be the hit
                        */
                        if ($hit1[ ($c1 + $s1) . "_" . ($c2) ] != 1) {
                            /**
                            *   add current search-line to diffence-buffer
                            */
                            $b1  .= $this->formatline(($c1 + $s1), ($c2), "-", $f1[ $c1+$s1 ]);

                            /**
                            *   mark this line as 'searched' to prevent doubles.
                            */
                            $hit1[ ($c1 + $s1) . "_" . $c2 ] = 1 ;
                        }
                    }



                    /**
                    *   test the right side for a hit
                    *
                    *   comparing current line with the searching line on the right
                    */
                    if (trim($f1[$c1]) == trim($f2[$c2+$s2])) {
                        $found  = 1   ;     # flag to stop search
                        $s1     = 0   ;     # reset pointer for search
                        $c1--         ;     # move current line back, so we hit next loop
                        $b      = $b2 ;     # get the buffered difference
                    } else {
                        /**
                        *   prevent to find line again
                        */
                        if ($hit2[ ($c1) . "_" . ($c2 + $s2) ] != 1) {
                            /**
                            *   add current searchline to buffer
                            */
                            $b2   .= $this->formatline(($c1), ($c2 + $s2), "+", $f2[ $c2+$s2 ]);

                            /**
                            *   mark current line to prevent double-hits
                            */
                            $hit2[ ($c1) . "_" . ($c2 + $s2) ] = 1;
                        }
                    }

                    /**
                    *   search in bigger distance
                    *
                    *   increase the search-pointers (satelites) and try again
                    */
                    $s1++ ;     # increase left  search-pointer
                    $s2++ ;     # increase right search-pointer
                }

                /**
                *   add line as different on both arrays (no match found)
                */
                if ($found == 0) {
                    $b  .= $this->formatline(($c1), ($c2), "-", $f1[ $c1 ]);
                    $b  .= $this->formatline(($c1), ($c2), "+", $f2[ $c2 ]);
                }

                /**
                *   add current buffer to outputstring
                */
                $out        .= $b;
                $outcount++ ;       #increase outcounter

                $c1++  ;    #move currentline forward
                $c2++  ;    #move currentline forward

                /**
                *   comment the lines are tested quite fast, because
                *   the current line always moves forward
                */
            } /*endif*/
        }/*endwhile*/

        return $out;
    }/*end func*/

    /**
     *   callback function to format the diffence-lines with your 'style'
     */
    private function formatline(int $nr1, int $nr2, string $stat, $value): string
    { #change to $value if problems
        if (trim($value) == "") {
            return "";
        }

        switch ($stat) {
            case "=":
                // return $nr1. " : $nr2 : = ".htmlentities( $value )  ."<br>";
                return "$value\n";

            case "+":
                //return $nr1. " : $nr2 : + <font color='blue' >".htmlentities( $value )  ."</font><br>";
                return "+++ $value\n";

            case "-":
                //return $nr1. " : $nr2 : - <font color='red' >".htmlentities( $value )  ."</font><br>";
                return "--- $value\n";

            default:
                throw new RuntimeException("stat needs to be =, + or -");
        }
    }
}
