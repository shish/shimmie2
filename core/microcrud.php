<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroCRUD\TextColumn;
use MicroCRUD\DateTimeColumn;
use MicroHTML\HTMLElement;

use function MicroHTML\{rawHTML, emptyHTML, INPUT, BR};

class BBCodeColumn extends TextColumn
{
    public function display(array $row): HTMLElement
    {
        return rawHTML(format_text($row[$this->name]));
    }
}

class ShortDateTimeColumn extends DateTimeColumn
{
    public function read_input(array $inputs): HTMLElement
    {
        return emptyHTML(
            INPUT([
                "type" => "date",
                "name" => "r_{$this->name}[]",
                "value" => @$inputs["r_{$this->name}"][0]
            ]),
            BR(),
            INPUT([
                "type" => "date",
                "name" => "r_{$this->name}[]",
                "value" => @$inputs["r_{$this->name}"][1]
            ])
        );
    }
}
