<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroCRUD\DateTimeColumn;

use function MicroHTML\{BR, INPUT, emptyHTML};

use MicroHTML\HTMLElement;

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
