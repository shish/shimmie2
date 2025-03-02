<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroCRUD\TextColumn;
use MicroHTML\HTMLElement;

use function MicroHTML\{rawHTML};

class BBCodeColumn extends TextColumn
{
    public function display(array $row): HTMLElement
    {
        return rawHTML(format_text($row[$this->name]));
    }
}
