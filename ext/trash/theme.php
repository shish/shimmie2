<?php

declare(strict_types=1);

namespace Shimmie2;

class TrashTheme extends Themelet
{
    public function get_help_html(): string
    {
        return '<p>Search for posts in the trash.</p>
        <div class="command_example">
        <code>in:trash</code>
        <p>Returns posts that are in the trash.</p>
        </div>
        ';
    }
}
