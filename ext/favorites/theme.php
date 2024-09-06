<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{INPUT,rawHTML};

class FavoritesTheme extends Themelet
{
    /**
     * @param string[] $username_array
     */
    public function display_people(array $username_array): void
    {
        global $page;

        $i_favorites = count($username_array);
        $html = "$i_favorites people:";

        reset($username_array); // rewind to first element in array.

        foreach ($username_array as $row) {
            $username = html_escape($row);
            $html .= "<br><a href='".make_link("user/$username")."'>$username</a>";
        }

        $page->add_block(new Block("Favorited By", rawHTML($html), "left", 25));
    }

    public function get_help_html(): string
    {
        return '<p>Search for posts that have been favorited a certain number of times, or favorited by a particular individual.</p>
        <div class="command_example">
        <code>favorites=1</code>
        <p>Returns posts that have been favorited once.</p>
        </div>
        <div class="command_example">
        <code>favorites>0</code>
        <p>Returns posts that have been favorited 1 or more times</p>
        </div>
        <p>Can use &lt;, &lt;=, &gt;, &gt;=, or =.</p>
        <div class="command_example">
        <code>favorited_by:username</code>
        <p>Returns posts that have been favorited by "username". </p>
        </div>
        <div class="command_example">
        <code>favorited_by_userno:123</code>
        <p>Returns posts that have been favorited by user 123. </p>
        </div>
        ';
    }
}
