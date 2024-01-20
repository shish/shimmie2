<?php

declare(strict_types=1);

namespace Shimmie2;

class EmoticonListTheme extends Themelet
{
    /**
     * @param string[] $list
     */
    public function display_emotes(array $list): void
    {
        global $page;
        $data_href = get_base_href();
        $html = "<html lang='en'><head><title>Emoticon list</title></head><body>";
        $html .= "<table><tr>";
        $n = 1;
        foreach ($list as $item) {
            $name = pathinfo($item, PATHINFO_FILENAME);
            $html .= "<td><img alt='$name' src='$data_href/$item'> :$name:</td>";
            if ($n++ % 3 == 0) {
                $html .= "</tr><tr>";
            }
        }
        $html .= "</tr></table>";
        $html .= "</body></html>";
        $page->set_mode(PageMode::DATA);
        $page->set_data($html);
    }
}
