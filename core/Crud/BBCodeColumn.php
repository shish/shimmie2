<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroCRUD\TextColumn;
use MicroHTML\HTMLElement;

class BBCodeColumn extends TextColumn
{
    public function display(array $row): HTMLElement
    {
        return format_text($row[$this->name]);
    }
}
