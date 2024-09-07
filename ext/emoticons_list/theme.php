<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{TITLE, rawHTML};

class EmoticonListTheme extends Themelet
{
    /**
     * @param string[] $list
     */
    public function display_emotes(array $list): void
    {
        global $page;
        $data_href = get_base_href();
        $body = "<table><tr>";
        $n = 1;
        foreach ($list as $item) {
            $name = pathinfo($item, PATHINFO_FILENAME);
            $body .= "<td><img alt='$name' src='$data_href/$item'> :$name:</td>";
            if ($n++ % 3 == 0) {
                $body .= "</tr><tr>";
            }
        }
        $body .= "</tr></table>";

        $page->set_mode(PageMode::DATA);
        $page->set_data((string)$page->html_html(
            TITLE("Emoticon list"),
            rawHTML($body)
        ));
    }
}
