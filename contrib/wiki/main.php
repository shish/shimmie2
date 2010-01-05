<?php
/*
 * Name: Simple Wiki
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: A simple wiki, for those who don't want the hugeness of mediawiki
 * Documentation:
 *  Standard formatting APIs are used (This will be BBCode by default)
 */

class WikiUpdateEvent extends Event {
	var $user;
	var $wikipage;

	public function WikiUpdateEvent(User $user, WikiPage $wikipage) {
		$this->user = $user;
		$this->wikipage = $wikipage;
	}
}

class WikiUpdateException extends SCoreException {
}

class WikiPage {
	var $id;
	var $owner_id;
	var $owner_ip;
	var $date;
	var $title;
	var $revision;
	var $locked;
	var $body;

	public function WikiPage($row) {
		assert(!empty($row));

		$this->id = $row['id'];
		$this->owner_id = $row['owner_id'];
		$this->owner_ip = $row['owner_ip'];
		$this->date = $row['date'];
		$this->title = $row['title'];
		$this->revision = $row['revision'];
		$this->locked = ($row['locked'] == 'Y');
		$this->body = $row['body'];
	}

	public function get_owner() {
		return User::by_id($this->owner_id);
	}

	public function is_locked() {
		return $this->locked;
	}
}

class Wiki extends SimpleExtension {
	public function onInitExt($event) {
		global $database;
		global $config;

		if($config->get_int("ext_wiki_version", 0) < 1) {
			$database->create_table("wiki_pages", "
				id SCORE_AIPK,
				owner_id INTEGER NOT NULL,
				owner_ip SCORE_INET NOT NULL,
				date DATETIME DEFAULT NULL,
				title VARCHAR(255) NOT NULL,
				revision INTEGER NOT NULL DEFAULT 1,
				locked SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N,
				body TEXT NOT NULL,
				UNIQUE (title, revision),
				FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
			");
			$config->set_int("ext_wiki_version", 2);
		}
		if($config->get_int("ext_wiki_version") < 2) {
			$database->Execute("ALTER TABLE wiki_pages ADD COLUMN
				locked ENUM('Y', 'N') DEFAULT 'N' NOT NULL AFTER REVISION");
			$config->set_int("ext_wiki_version", 2);
		}
	}

	public function onPageRequest($event) {
		global $config, $page, $user;
		if($event->page_matches("wiki")) {
			if(is_null($event->get_arg(0)) || strlen(trim($event->get_arg(0))) == 0) {
				$title = "Index";
			}
			else {
				$title = $event->get_arg(0);
			}

			$content = $this->get_page($title);
			$this->theme->display_page($page, $content, $this->get_page("wiki:sidebar"));
		}
		else if($event->page_matches("wiki_admin/edit")) {
			$content = $this->get_page($_POST['title']);
			$this->theme->display_page_editor($page, $content);
		}
		else if($event->page_matches("wiki_admin/save")) {
			$title = $_POST['title'];
			$rev = int_escape($_POST['revision']);
			$body = $_POST['body'];
			$lock = $user->is_admin() && isset($_POST['lock']) && ($_POST['lock'] == "on");

			if($this->can_edit($user, $this->get_page($title))) {
				$wikipage = $this->get_page($title);
				$wikipage->rev = $rev;
				$wikipage->body = $body;
				$wikipage->locked = $lock;
				try {
					send_event(new WikiUpdateEvent($user, $wikipage));

					$u_title = url_escape($title);
					$page->set_mode("redirect");
					$page->set_redirect(make_link("wiki/$u_title"));
				}
				catch(WikiUpdateException $e) {
					$original = $this->get_page($title);
					// @ because arr_diff is full of warnings
					$original->body = @$this->arr_diff(
							explode("\n", $original->body),
							explode("\n", $wikipage->body)
					);
					$this->theme->display_page_editor($page, $original);
				}
			}
			else {
				$this->theme->display_permission_denied($page);
			}
		}
		else if($event->page_matches("wiki_admin/delete_revision")) {
			if($user->is_admin()) {
				global $database;
				$database->Execute(
						"DELETE FROM wiki_pages WHERE title=? AND revision=?",
						array($_POST["title"], $_POST["revision"]));
				$u_title = url_escape($_POST["title"]);
				$page->set_mode("redirect");
				$page->set_redirect(make_link("wiki/$u_title"));
			}
		}
		else if($event->page_matches("wiki_admin/delete_all")) {
			if($user->is_admin()) {
				global $database;
				$database->Execute(
						"DELETE FROM wiki_pages WHERE title=?",
						array($_POST["title"]));
				$u_title = url_escape($_POST["title"]);
				$page->set_mode("redirect");
				$page->set_redirect(make_link("wiki/$u_title"));
			}
		}
	}

	public function onWikiUpdate($event) {
		global $database;
		$wpage = $event->wikipage;
		try {
			$row = $database->Execute("
				INSERT INTO wiki_pages(owner_id, owner_ip, date, title, revision, locked, body)
				VALUES (?, ?, now(), ?, ?, ?, ?)", array($event->user->id, $_SERVER['REMOTE_ADDR'],
				$wpage->title, $wpage->rev, $wpage->locked?'Y':'N', $wpage->body));
		}
		catch(Exception $e) {
			throw new WikiUpdateException("Somebody else edited that page at the same time :-(");
		}
	}

	public function onSetupBuilding($event) {
		$sb = new SetupBlock("Wiki");
		$sb->add_bool_option("wiki_edit_anon", "Allow anonymous edits: ");
		$sb->add_bool_option("wiki_edit_user", "<br>Allow user edits: ");
		$event->panel->add_block($sb);
	}

	/**
	 * See if the given user is allowed to edit the given page
	 *
	 * @retval boolean
	 */
	public static function can_edit(User $user, WikiPage $page) {
		global $config;

		// admins can edit everything
		if($user->is_admin()) return true;

		// anon / user can't ever edit locked pages
		if($page->is_locked()) return false;

		// anon / user can edit if allowed by config
		if($config->get_bool("wiki_edit_anon", false) && $user->is_anonymous()) return true;
		if($config->get_bool("wiki_edit_user", false) && !$user->is_anonymous()) return true;

		return false;
	}

	private function get_page($title, $revision=-1) {
		global $database;
		// first try and get the actual page
		$row = $database->db->GetRow("
				SELECT *
				FROM wiki_pages
				WHERE title LIKE ?
				ORDER BY revision DESC", array($title));

		// fall back to wiki:default
		if(empty($row)) {
			$row = $database->db->GetRow("
					SELECT *
					FROM wiki_pages
					WHERE title LIKE ?
					ORDER BY revision DESC", "wiki:default");

			// fall further back to manual
			if(empty($row)) {
				$row = array(
					"id" => -1,
					"owner_ip" => "0.0.0.0",
					"date" => "",
					"revision" => 0,
					"locked" => false,
					"body" => "This is a default page for when a page is empty, ".
						"it can be edited by editing [[wiki:default]].",
				);
			}

			// correct the default
			global $config;
			$row["title"] = $title;
			$row["owner_id"] = $config->get_int("anon_id", 0);
		}

		assert(!empty($row));

		return new WikiPage($row);
	}

// php-diff {{{
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
         
        http://www.gnu.org/licenses/gpl.html 

        About: 
        I searched a function to compare arrays and the array_diff() 
        was not specific enough. It ignores the order of the array-values. 
        So I reimplemented the diff-function which is found on unix-systems 
        but this you can use directly in your code and adopt for your needs. 
        Simply adopt the formatline-function. with the third-parameter of arr_diff() 
        you can hide matching lines. Hope someone has use for this. 

        Contact: d.u.diff@holomind.de <daniel unterberger> 
    **/ 

    private function arr_diff( $f1 , $f2 , $show_equal = 0 ) 
    { 

        $c1         = 0 ;                   # current line of left 
        $c2         = 0 ;                   # current line of right 
        $max1       = count( $f1 ) ;        # maximal lines of left 
        $max2       = count( $f2 ) ;        # maximal lines of right 
        $outcount   = 0;                    # output counter 
        $hit1       = "" ;                  # hit in left 
        $hit2       = "" ;                  # hit in right 
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
              ) 
        { 
            /** 
            *   is the trimmed line of the current left and current right line 
            *   the same ? then this is a hit (no difference) 
            */   
            if ( trim( $f1[$c1] ) == trim ( $f2[$c2])  )     
            { 
                /** 
                *   add to output-string, if "show_equal" is enabled 
                */ 
                $out    .= ($show_equal==1)  
                         ?  formatline ( ($c1) , ($c2), "=", $f1[ $c1 ] )  
                         : "" ; 
                /** 
                *   increase the out-putcounter, if "show_equal" is enabled 
                *   this ist more for demonstration purpose 
                */ 
                if ( $show_equal == 1 )   
                {  
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
            else 
            { 
                 
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
                        ( $c1 + $s1 <= $max1 )  # and we are inside of the left lines 
                        and  
                        ( $c2 + $s2 <= $max2 )  # and we are inside of the right lines 
                        and      
                        $fstop++  < 10          # and the distance is lower than 10 lines 
                      ) 
                { 

                    /** 
                    *   test the left side for a hit 
                    * 
                    *   comparing current line with the searching line on the left 
                    *   b1 is a buffer, which collects the line which not match, to  
                    *   show the differences later, if one line hits, this buffer will 
                    *   be used, else it will be discarded later 
                    */ 
                    #hit 
                    if ( trim( $f1[$c1+$s1] ) == trim( $f2[$c2] )  ) 
                    { 
                        $found  = 1   ;     # set flag to stop further search 
                        $s2     = 0   ;     # reset right side search-pointer 
                        $c2--         ;     # move back the current right, so next loop hits 
                        $b      = $b1 ;     # set b=output (b)uffer 
                    } 
                    #no hit: move on 
                    else 
                    { 
                        /** 
                        *   prevent finding a line again, which would show wrong results 
                        * 
                        *   add the current line to leftbuffer, if this will be the hit 
                        */ 
                        if ( $hit1[ ($c1 + $s1) . "_" . ($c2) ] != 1 ) 
                        {    
                            /** 
                            *   add current search-line to diffence-buffer 
                            */ 
                            $b1  .= $this->formatline( ($c1 + $s1) , ($c2), "-", $f1[ $c1+$s1 ] ); 

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
                    if ( trim ( $f1[$c1] ) == trim ( $f2[$c2+$s2])  ) 
                    { 
                        $found  = 1   ;     # flag to stop search 
                        $s1     = 0   ;     # reset pointer for search 
                        $c1--         ;     # move current line back, so we hit next loop 
                        $b      = $b2 ;     # get the buffered difference 
                    } 
                    else 
                    {    
                        /** 
                        *   prevent to find line again 
                        */ 
                        if ( $hit2[ ($c1) . "_" . ( $c2 + $s2) ] != 1 ) 
                        { 
                            /** 
                            *   add current searchline to buffer 
                            */ 
                            $b2   .= $this->formatline ( ($c1) , ($c2 + $s2), "+", $f2[ $c2+$s2 ] ); 

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
                if ( $found == 0 ) 
                { 
                    $b  .= $this->formatline ( ($c1) , ($c2), "-", $f1[ $c1 ] ); 
                    $b  .= $this->formatline ( ($c1) , ($c2), "+", $f2[ $c2 ] ); 
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
	private function formatline( $nr1, $nr2, $stat, &$value ) { #change to $value if problems 
		if(trim($value) == "") {
			return ""; 
		} 

		switch($stat) {
			case "=": 
				// return $nr1. " : $nr2 : = ".htmlentities( $value )  ."<br>"; 
				return "$value\n";
				break; 

			case "+": 
				//return $nr1. " : $nr2 : + <font color='blue' >".htmlentities( $value )  ."</font><br>"; 
				return "+++ $value\n";
				break; 

			case "-": 
				//return $nr1. " : $nr2 : - <font color='red' >".htmlentities( $value )  ."</font><br>"; 
				return "--- $value\n";
				break; 
		} 
	}
// }}}
}
?>
