<?php

declare(strict_types=1);
class PostTitlesTheme extends Themelet
{
    public function get_title_set_html(string $title, bool $can_set): string
    {
        $html = "
			<tr>
				<th>Title</th>
				<td>
		".($can_set ? "
					<span class='view'>".html_escape($title)."</span>
						<input class='edit'  type='text' name='post_title' value='".html_escape($title)."' />
		" : html_escape("
					 $title
		"))."
				</td>
			</tr>
		";
        return $html;
    }
}
