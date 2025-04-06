<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{IMG, TABLE, TD, TR};
use function MicroHTML\{TITLE};

class EmoticonListTheme extends Themelet
{
    /**
     * @param string[] $list
     */
    public function display_emotes(array $list): void
    {
        $data_href = Url::base();

        $table = TABLE();
        foreach ($list as $item) {
            $table->appendChild(TR(
                TD(IMG(['src' => "$data_href/$item", 'alt' => pathinfo($item, PATHINFO_FILENAME)])),
                TD(":".pathinfo($item, PATHINFO_FILENAME).":")
            ));
        }

        Ctx::$page->set_data(MimeType::HTML, (string)Ctx::$page->html_html(
            TITLE("Emoticon list"),
            $table
        ));
    }
}
