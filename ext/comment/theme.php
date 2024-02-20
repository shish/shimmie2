<?php

declare(strict_types=1);

namespace Shimmie2;

class CommentListTheme extends Themelet
{
    private bool $show_anon_id = false;
    private int $anon_id = 1;
    /** @var array<string,int> */
    private array $anon_map = [];

    /**
     * Display a page with a list of images, and for each image, the image's comments.
     *
     * @param array<array{0: Image, 1: Comment[]}> $images
     */
    public function display_comment_list(array $images, int $page_number, int $total_pages, bool $can_post): void
    {
        global $config, $page, $user;

        // aaaaaaargh php
        assert(is_array($images));
        assert(is_numeric($page_number));
        assert(is_numeric($total_pages));
        assert(is_bool($can_post));

        // parts for the whole page
        $prev = $page_number - 1;
        $next = $page_number + 1;

        $h_prev = ($page_number <= 1) ? "Prev" :
            '<a href="'.make_link('comment/list/'.$prev).'">Prev</a>';
        $h_index = "<a href='".make_link()."'>Index</a>";
        $h_next = ($page_number >= $total_pages) ? "Next" :
            '<a href="'.make_link('comment/list/'.$next).'">Next</a>';

        $nav = $h_prev.' | '.$h_index.' | '.$h_next;

        $page->set_title("Comments");
        $page->set_heading("Comments");
        $page->add_block(new Block("Navigation", $nav, "left", 0));
        $this->display_paginator($page, "comment/list", null, $page_number, $total_pages);

        // parts for each image
        $position = 10;

        $comment_limit = $config->get_int("comment_list_count", 10);
        $comment_captcha = $config->get_bool('comment_captcha');

        foreach ($images as $pair) {
            $image = $pair[0];
            $comments = $pair[1];

            $thumb_html = $this->build_thumb_html($image);
            $comment_html = "";

            $comment_count = count($comments);
            if ($comment_limit > 0 && $comment_count > $comment_limit) {
                $comment_html .= "<p>showing $comment_limit of $comment_count comments</p>";
                $comments = array_slice($comments, negative_int($comment_limit));
                $this->show_anon_id = false;
            } else {
                $this->show_anon_id = true;
            }
            $this->anon_id = 1;
            foreach ($comments as $comment) {
                $comment_html .= $this->comment_to_html($comment);
            }
            if (!$user->is_anonymous()) {
                if ($can_post) {
                    $comment_html .= $this->build_postbox($image->id);
                }
            } else {
                if ($can_post) {
                    if (!$comment_captcha) {
                        $comment_html .= $this->build_postbox($image->id);
                    } else {
                        $link = make_link("post/view/".$image->id);
                        $comment_html .= "<a href='$link'>Add Comment</a>";
                    }
                }
            }

            $html  = '
				<table class="comment_list_table"><tr>
					<td width="220">'.$thumb_html.'</td>
					<td>'.$comment_html.'</td>
				</tr></table>
			';

            $page->add_block(new Block($image->id.': '.$image->get_tag_list(), $html, "main", $position++, "comment-list-list"));
        }
    }

    public function display_admin_block(): void
    {
        global $page;

        $html = '
			Delete comments by IP.

			<br><br>'.make_form(make_link("comment/bulk_delete"))."
				<table class='form'>
					<tr><th>IP&nbsp;Address</th> <td><input type='text' name='ip' size='15'></td></tr>
					<tr><td colspan='2'><input type='submit' value='Delete'></td></tr>
				</table>
			</form>
		";
        $page->add_block(new Block("Mass Comment Delete", $html));
    }

    /**
     * Add some comments to the page, probably in a sidebar.
     *
     * @param Comment[] $comments An array of Comment objects to be shown
     */
    public function display_recent_comments(array $comments): void
    {
        global $page;
        $this->show_anon_id = false;
        $html = "";
        foreach ($comments as $comment) {
            $html .= $this->comment_to_html($comment, true);
        }
        $html .= "<a class='more' href='".make_link("comment/list")."'>Full List</a>";
        $page->add_block(new Block("Comments", $html, "left", 50, "comment-list-recent"));
    }

    /**
     * Show comments for an image.
     *
     * @param Comment[] $comments
     */
    public function display_image_comments(Image $image, array $comments, bool $postbox): void
    {
        global $page;
        $this->show_anon_id = true;
        $html = "";
        foreach ($comments as $comment) {
            $html .= $this->comment_to_html($comment);
        }
        if ($postbox) {
            $html .= $this->build_postbox($image->id);
        }
        $page->add_block(new Block("Comments", $html, "main", 30, "comment-list-image"));
    }

    /**
     * Show comments made by a user.
     *
     * @param Comment[] $comments
     */
    public function display_recent_user_comments(array $comments, User $user): void
    {
        global $page;
        $html = "";
        foreach ($comments as $comment) {
            $html .= $this->comment_to_html($comment, true);
        }
        if (empty($html)) {
            $html = '<p>No comments by this user.</p>';
        } else {
            $html .= "<p><a href='".make_link("comment/beta-search/{$user->name}/1")."'>More</a></p>";
        }
        $page->add_block(new Block("Comments", $html, "left", 70, "comment-list-user"));
    }

    /**
     * @param Comment[] $comments
     */
    public function display_all_user_comments(array $comments, int $page_number, int $total_pages, User $user): void
    {
        global $page;

        $html = "";
        foreach ($comments as $comment) {
            $html .= $this->comment_to_html($comment, true);
        }
        if (empty($html)) {
            $html = '<p>No comments by this user.</p>';
        }
        $page->add_block(new Block("Comments", $html, "main", 70, "comment-list-user"));


        $prev = $page_number - 1;
        $next = $page_number + 1;

        //$search_terms = array('I','have','no','idea','what','this','does!');
        //$u_tags = url_escape(Tag::implode($search_terms));
        //$query = empty($u_tags) ? "" : '/'.$u_tags;

        $h_prev = ($page_number <= 1) ? "Prev" : "<a href='$prev'>Prev</a>";
        $h_index = "<a href='".make_link()."'>Index</a>";
        $h_next = ($page_number >= $total_pages) ? "Next" : "<a href='$next'>Next</a>";

        $page->set_title(html_escape($user->name)."'s comments");
        $page->add_block(new Block("Navigation", $h_prev.' | '.$h_index.' | '.$h_next, "left", 0));
        $this->display_paginator($page, "comment/beta-search/{$user->name}", null, $page_number, $total_pages);
    }

    protected function comment_to_html(Comment $comment, bool $trim = false): string
    {
        global $config, $user;

        $tfe = send_event(new TextFormattingEvent($comment->comment));

        $i_uid = $comment->owner_id;
        $h_name = html_escape($comment->owner_name);
        $h_timestamp = autodate($comment->posted);
        if ($trim) {
            $h_comment = truncate($tfe->stripped, 50);
        } else {
            $h_comment = $tfe->formatted;
        }
        $i_comment_id = $comment->comment_id;
        $i_image_id = $comment->image_id;

        if ($i_uid == $config->get_int("anon_id")) {
            $anoncode = "";
            $anoncode2 = "";
            if ($this->show_anon_id) {
                $anoncode = '<sup>'.$this->anon_id.'</sup>';
                if (!array_key_exists($comment->poster_ip, $this->anon_map)) {
                    $this->anon_map[$comment->poster_ip] = $this->anon_id;
                }
                #if($user->can(UserAbilities::VIEW_IP)) {
                #$style = " style='color: ".$this->get_anon_colour($comment->poster_ip).";'";
                if ($user->can(Permissions::VIEW_IP) || $config->get_bool("comment_samefags_public", false)) {
                    if ($this->anon_map[$comment->poster_ip] != $this->anon_id) {
                        $anoncode2 = '<sup>('.$this->anon_map[$comment->poster_ip].')</sup>';
                    }
                }
            }
            $h_userlink = "<span class='username'>" . $h_name . $anoncode . $anoncode2 . "</span>";
            $this->anon_id++;
        } else {
            $h_userlink = '<a class="username" href="'.make_link('user/'.$h_name).'">'.$h_name.'</a>';
        }

        $hb = ($comment->owner_class == "hellbanned" ? "hb" : "");
        if ($trim) {
            $html = "
			<div class=\"comment $hb\">
				$h_userlink: $h_comment
				<a href=\"".make_link("post/view/$i_image_id", null, "c$i_comment_id")."\">&gt;&gt;&gt;</a>
			</div>
			";
        } else {
            $h_avatar = "";
            if (!empty($comment->owner_email)) {
                $hash = md5(strtolower($comment->owner_email));
                $cb = date("Y-m-d");
                $h_avatar = "<img alt='avatar' src=\"//www.gravatar.com/avatar/$hash.jpg?cacheBreak=$cb\"><br>";
            }
            $h_reply = " - <a href='javascript: replyTo($i_image_id, $i_comment_id, \"$h_name\")'>Reply</a>";
            $h_ip = $user->can(Permissions::VIEW_IP) ? "<br>".show_ip($comment->poster_ip, "Comment posted {$comment->posted}") : "";
            $h_del = "";
            if ($user->can(Permissions::DELETE_COMMENT)) {
                $h_del = " - " . $this->delete_link($i_comment_id, $i_image_id, $comment->owner_name, $tfe->stripped);
            }
            $html = "
				<div class=\"comment $hb\" id=\"c$i_comment_id\">
					<div class=\"info\">
					$h_avatar
					$h_timestamp$h_reply$h_ip$h_del
					</div>
					$h_userlink: $h_comment
				</div>
			";
        }
        return $html;
    }

    protected function delete_link(int $comment_id, int $image_id, string $owner, string $text): string
    {
        $comment_preview = substr(html_unescape($text), 0, 50);
        $j_delete_confirm_message = json_encode("Delete comment by {$owner}:\n$comment_preview") ?: "Delete <corrupt comment>";
        $h_delete_script = html_escape("return confirm($j_delete_confirm_message);");
        $h_delete_link = make_link("comment/delete/$comment_id/$image_id");
        return "<a onclick='$h_delete_script' href='$h_delete_link'>Del</a>";
    }

    protected function build_postbox(int $image_id): string
    {
        global $config;

        $hash = CommentList::get_hash();
        $h_captcha = $config->get_bool("comment_captcha") ? captcha_get_html() : "";

        return '
		<div class="comment comment_add">
			'.make_form(make_link("comment/add")).'
				<input type="hidden" name="image_id" value="'.$image_id.'" />
				<input type="hidden" name="hash" value="'.$hash.'" />
				<textarea id="comment_on_'.$image_id.'" name="comment" rows="5" cols="50"></textarea>
				'.$h_captcha.'
				<br><input type="submit" value="Post Comment" />
			</form>
		</div>
		';
    }

    public function get_help_html(): string
    {
        return '<p>Search for posts containing a certain number of comments, or comments by a particular individual.</p>
        <div class="command_example">
        <pre>comments=1</pre>
        <p>Returns posts with exactly 1 comment.</p>
        </div>
        <div class="command_example">
        <pre>comments>0</pre>
        <p>Returns posts with 1 or more comments. </p>
        </div>
        <p>Can use &lt;, &lt;=, &gt;, &gt;=, or =.</p>
        <div class="command_example">
        <pre>commented_by:username</pre>
        <p>Returns posts that have been commented on by "username". </p>
        </div>
        <div class="command_example">
        <pre>commented_by_userno:123</pre>
        <p>Returns posts that have been commented on by user 123. </p>
        </div>
        ';
    }
}
