<?php

declare(strict_types=1);

namespace Shimmie2;

class PrivateImageTheme extends Themelet
{
    public function get_help_html(): string
    {
        return '<p>Search for posts that are private/public.</p>
        <div class="command_example">
        <code>private:yes</code>
        <p>Returns posts that are private, restricted to yourself if you are not an admin.</p>
        </div>
        <div class="command_example">
        <code>private:no</code>
        <p>Returns posts that are public.</p>
        </div>
        ';
    }
}
