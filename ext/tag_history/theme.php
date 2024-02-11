<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\LI;
use function MicroHTML\A;
use function MicroHTML\INPUT;
use function MicroHTML\LABEL;
use function MicroHTML\SPAN;
use function MicroHTML\rawHTML;

/**
 * @phpstan-type HistoryEntry array{image_id:int,id:int,tags:string,date_set:string,user_id:string,user_ip:string,name:string}
 */
class TagHistoryTheme extends Themelet
{
    /** @var string[] */
    private array $messages = [];

    /**
     * @param HistoryEntry[] $history
     */
    public function display_history_page(Page $page, int $image_id, array $history): void
    {
        $history_html = $this->history_list($history, true);

        $page->set_title('Post '.$image_id.' Tag History');
        $page->set_heading('Tag History: '.$image_id);
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Tag History", $history_html, "main", 10));
    }

    /**
     * @param HistoryEntry[] $history
     */
    public function display_global_page(Page $page, array $history, int $page_number): void
    {
        $history_html = $this->history_list($history, false);

        $page->set_title("Global Tag History");
        $page->set_heading("Global Tag History");
        $page->add_block(new Block("Tag History", $history_html, "main", 10));

        $h_prev = ($page_number <= 1) ? "Prev" :
            '<a href="'.make_link('tag_history/all/'.($page_number - 1)).'">Prev</a>';
        $h_index = "<a href='".make_link()."'>Index</a>";
        $h_next = '<a href="'.make_link('tag_history/all/'.($page_number + 1)).'">Next</a>';

        $nav = $h_prev.' | '.$h_index.' | '.$h_next;
        $page->add_block(new Block("Navigation", $nav, "left", 0));
    }

    /**
     * Add a section to the admin page.
     */
    public function display_admin_block(string $validation_msg = ''): void
    {
        global $page;

        if (!empty($validation_msg)) {
            $validation_msg = '<br><b>'. $validation_msg .'</b>';
        }

        $html = '
			Revert tag changes by a specific IP address or username, optionally limited to recent changes.
			'.$validation_msg.'

			<br><br>'.make_form(make_link("tag_history/bulk_revert"))."
				<table class='form'>
					<tr><th>Username</th>        <td><input type='text' name='revert_name' size='15'></td></tr>
					<tr><th>IP&nbsp;Address</th> <td><input type='text' name='revert_ip' size='15'></td></tr>
					<tr><th>Since</th>           <td><input type='date' name='revert_date' size='15'></td></tr>
					<tr><td colspan='2'><input type='submit' value='Revert'></td></tr>
				</table>
			</form>
		";
        $page->add_block(new Block("Mass Tag Revert", $html));
    }

    /*
     * Show a standard page for results to be put into
     */
    public function display_revert_ip_results(): void
    {
        global $page;
        $html = implode("\n", $this->messages);
        $page->add_block(new Block("Bulk Revert Results", $html));
    }

    public function add_status(string $title, string $body): void
    {
        $this->messages[] = '<p><b>'. $title .'</b><br>'. $body .'</p>';
    }

    /**
     * @param HistoryEntry[] $history
     */
    protected function history_list(array $history, bool $select_2nd): string
    {
        $history_list = "";
        foreach ($history as $n => $fields) {
            $history_list .= $this->history_entry($fields, $select_2nd && $n == 1);
        }

        return "
			<div style='text-align: left'>
				" . make_form(make_link("tag_history/revert")) . "
					<ul style='list-style-type:none;'>
					    $history_list
					</ul>
					<input type='submit' value='Revert To'>
				</form>
			</div>
		";
    }

    /**
     * @param HistoryEntry $fields
     */
    protected function history_entry(array $fields, bool $selected): string
    {
        global $user;
        $image_id = $fields['image_id'];
        $current_id = $fields['id'];
        $current_tags = $fields['tags'];
        $name = $fields['name'];
        $date_set = rawHTML(autodate($fields['date_set']));
        $ip = $user->can(Permissions::VIEW_IP) ?
            rawHTML(" " . show_ip($fields['user_ip'], "Tagging >>$image_id as '$current_tags'"))
            : null;
        $setter = A(["href" => make_link("user/" . url_escape($name))], $name);

        $current_tags = Tag::explode($current_tags);
        $taglinks = SPAN();
        foreach ($current_tags as $tag) {
            $taglinks->appendChild(A(["href" => search_link([$tag])], $tag));
            $taglinks->appendChild(" ");
        }

        return (string)LI(
            INPUT(["type" => "radio", "name" => "revert", "id" => "$current_id", "value" => "$current_id", "checked" => $selected]),
            A(["href" => make_link("post/view/$image_id")], $image_id),
            ": ",
            LABEL(
                ["for" => "$current_id"],
                $taglinks,
                " - ",
                $setter,
                $ip,
                " - ",
                $date_set
            )
        );
    }
}
