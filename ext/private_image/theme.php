<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\INPUT;

class PrivateImageTheme extends Themelet
{
    public function get_help_html(): string
    {
        return '<p>Search for posts that are private/public.</p>
        <div class="command_example">
        <pre>private:yes</pre>
        <p>Returns posts that are private, restricted to yourself if you are not an admin.</p>
        </div>
        <div class="command_example">
        <pre>private:no</pre>
        <p>Returns posts that are public.</p>
        </div>
        ';
    }
}
