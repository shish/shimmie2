<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, BR, P, emptyHTML};

use MicroHTML\HTMLElement;

class FavoritesTheme extends Themelet
{
    /**
     * @param string[] $usernames
     */
    public function display_people(array $usernames): void
    {
        $i_favorites = count($usernames);
        $html = emptyHTML("$i_favorites people:");

        foreach ($usernames as $username) {
            $html->appendChild(BR());
            $html->appendChild(A(["href" => make_link("user/$username")], $username));
        }

        Ctx::$page->add_block(new Block("Favorited By", $html, "left", 25));
    }

    public function get_help_html(): HTMLElement
    {
        return emptyHTML(
            P('Search for posts that have been favorited a certain number of times, or favorited by a particular individual.'),
            SHM_COMMAND_EXAMPLE('favorites=1', 'Returns posts that have been favorited once'),
            SHM_COMMAND_EXAMPLE('favorites>0', 'Returns posts that have been favorited 1 or more times'),
            SHM_COMMAND_EXAMPLE('favorited_by:username', 'Returns posts that have been favorited by a user'),
        );
    }
}
