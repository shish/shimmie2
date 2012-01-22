<?php
class ForumTheme extends Themelet {

    public function display_thread_list(Page $page, $threads, $showAdminOptions, $pageNumber, $totalPages)
    {
        if (count($threads) == 0)
            $html = "There are no threads to show.";
        else
            $html = $this->make_thread_list($threads, $showAdminOptions);

		$page->set_title(html_escape("Forum"));
		$page->set_heading(html_escape("Forum"));
        $page->add_block(new Block("Forum", $html, "main", 10));
		
        $this->display_paginator($page, "forum/index", null, $pageNumber, $totalPages);
    }



    public function display_new_thread_composer(Page $page, $threadText = null, $threadTitle = null)
    {
		global $config, $user;
		$max_characters = $config->get_int('forumMaxCharsPerPost');
		$html = make_form(make_link("forum/create"));

       
        if (!is_null($threadTitle))
        $threadTitle = html_escape($threadTitle);

        if(!is_null($threadText))
        $threadText = html_escape($threadText);
		
		$html .= "
				<table style='width: 500px;'>
					<tr><td>Title:</td><td><input type='text' name='title' value='$threadTitle'></td></tr>
					<tr><td>Message:</td><td><textarea id='message' name='message' >$threadText</textarea></td></tr>
					<tr><td></td><td><small>Max characters alowed: $max_characters.</small></td></tr>";
		if($user->is_admin()){
			$html .= "<tr><td colspan='2'><label for='sticky'>Sticky:</label><input name='sticky' type='checkbox' value='Y' /></td></tr>";
		}
			$html .= "<tr><td colspan='2'><input type='submit' value='Submit' /></td></tr>
				</table>
				</form>
				";

        $blockTitle = "Write a new thread";
		$page->set_title(html_escape($blockTitle));
		$page->set_heading(html_escape($blockTitle));
        $page->add_block(new Block($blockTitle, $html, "main", 20));
    }
	
	
	
    public function display_new_post_composer(Page $page, $threadID)
    {
		global $config;
		
		$max_characters = $config->get_int('forumMaxCharsPerPost');
		
		$html = make_form(make_link("forum/answer"));

        $html .= '<input type="hidden" name="threadID" value="'.$threadID.'" />';
		
		$html .= "
				<table style='width: 500px;'>
					<tr><td>Message:</td><td><textarea id='message' name='message' ></textarea>
					<tr><td></td><td><small>Max characters alowed: $max_characters.</small></td></tr>
					</td></tr>";
							
		$html .= "<tr><td colspan='2'><input type='submit' value='Submit' /></td></tr>
				</table>
				</form>
				";

        $blockTitle = "Answer to this thread";
        $page->add_block(new Block($blockTitle, $html, "main", 30));
    }



    public function display_thread($posts, $showAdminOptions,  $threadTitle, $threadID, $pageNumber, $totalPages)
    {
		global $config, $page/*, $user*/;

		$theme_name = $config->get_string('theme');
		
        $html = "";
        $n = 0;

        $html = "<table id='postList' class='zebra'>".
			"<thead><tr>".
            "<th>User</th>".
            "<th>Message</th>".
			"</tr></thead>";
		
        foreach ($posts as $post)
        {
            $message = $post["message"];

            $tfe = new TextFormattingEvent($message);
            send_event($tfe);
            $message = $tfe->formatted;
			
			$message = str_replace('\n\r', '<br>', $message);
            $message = str_replace('\r\n', '<br>', $message);
            $message = str_replace('\n', '<br>', $message);
            $message = str_replace('\r', '<br>', $message);
			
			$message = stripslashes($message);
			
            $user = "<a href='".make_link("user/".$post["user_name"]."")."'>".$post["user_name"]."</a>";

            $poster = User::by_name($post["user_name"]);
			$gravatar = $poster->get_avatar_html();

            $oe = ($n++ % 2 == 0) ? "even" : "odd";
			
			if ($post["user_admin"] == "Y") {
			$rank = "<sup>admin</sup>";
			} else {
			$rank = "<sup>user</sup>";
			}
			
			$postID = $post['id'];
			
			//if($user->is_admin()){
			//$delete_link = "<a href=".make_link("forum/delete/".$threadID."/".$postID).">Delete</a>";
			//} else {
			//$delete_link = "";
			//}
			
			if($showAdminOptions){
			$delete_link = "<a href=".make_link("forum/delete/".$threadID."/".$postID).">Delete</a>";
			}else{
			$delete_link = "";
			}
            
            $html .= "<tr class='$oe'>".
                "<td class='forum_user'>".$user."<br>".$rank."<br>".$gravatar."</td>".
                "<td class='forum_message'>".$message."</td>"."</tr>
				<tr class='$oe'>
					<td class='forum_subuser'><small>".autodate($post["date"])."</small></td>
					<td class='forum_submessage'>".$delete_link."</td>
				</tr>";

        }
		
        $html .= "</tbody></table>";
        
        $this->display_paginator($page, "forum/view/".$threadID, null, $pageNumber, $totalPages);

		$page->set_title(html_escape($threadTitle));
		$page->set_heading(html_escape($threadTitle));
        $page->add_block(new Block("Thread", $html, "main", 20));

    }
	
	

    public function add_actions_block(Page $page, $threadID)
    {
        $html = '<a href="'.make_link("forum/nuke/".$threadID).'">Delete this thread and its posts.</a>';

        $page->add_block(new Block("Admin Actions", $html, "main", 40));
    }



    private function make_thread_list($threads, $showAdminOptions)
    {
        $html = "<table id='threadList' class='zebra'>".
            "<thead><tr>".
            "<th>Title</th>".
            "<th>Author</th>".
			"<th>Updated</th>".
            "<th>Responses</th>";

        if($showAdminOptions)
        {
            $html .= "<th>Actions</th>";
        }

        $html .= "</tr></thead><tbody>";


        $n = 0;
        foreach($threads as $thread)
        {
            $oe = ($n++ % 2 == 0) ? "even" : "odd";
			
			global $config;
			$titleSubString = $config->get_int('forumTitleSubString');
			
			if ($titleSubString < strlen($thread["title"]))
			{
				$title = substr($thread["title"], 0, $titleSubString);
				$title = $title."...";
			} else {
				$title = $thread["title"];
			}
			
			if($thread["sticky"] == "Y"){
				$sticky = "Sticky: ";
			} else {
				$sticky = "";
				}
            
            $html .= "<tr class='$oe'>".
                '<td class="left">'.$sticky.'<a href="'.make_link("forum/view/".$thread["id"]).'">'.$title."</a></td>".
				'<td><a href="'.make_link("user/".$thread["user_name"]).'">'.$thread["user_name"]."</a></td>".
				"<td>".autodate($thread["uptodate"])."</td>".
                "<td>".$thread["response_count"]."</td>";
             
            if ($showAdminOptions)
                $html .= '<td><a href="'.make_link("forum/nuke/".$thread["id"]).'" title="Delete '.$title.'">Delete</a></td>';

            $html .= "</tr>";
        }

        $html .= "</tbody></table>";

        return $html;
    }
}
?>
