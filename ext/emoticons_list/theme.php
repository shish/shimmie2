<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{TITLE};
use function MicroHTML\IMG;
use function MicroHTML\TABLE;
use function MicroHTML\TD;
use function MicroHTML\TR;

class EmoticonListTheme extends Themelet
{
    /**
     * @param string[] $list
     */
    public function display_emotes(array $list): void
    {
        global $page;
        $data_href = Url::base();

        $table = TABLE();
        foreach ($list as $item) {
            $table->appendChild(TR(
                TD(IMG(['src' => "$data_href/$item", 'alt' => pathinfo($item, PATHINFO_FILENAME)])),
                TD(":".pathinfo($item, PATHINFO_FILENAME).":")
            ));
        }

        $page->set_mode(PageMode::DATA);
        $page->set_data((string)$page->html_html(
            TITLE("Emoticon list"),
            $table
        ));
    }
}
