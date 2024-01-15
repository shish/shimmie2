<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroCRUD\TextColumn;

use function MicroHTML\INPUT;

class AutoCompleteColumn extends TextColumn
{
    public function read_input(array $inputs): \MicroHTML\HTMLElement
    {
        return INPUT([
            "type" => "text",
            "name" => "r_{$this->name}",
            "class" => "autocomplete_tags",
            "placeholder" => $this->title,
            "value" => @$inputs["r_{$this->name}"]
        ]);
    }

    public function create_input(array $inputs): \MicroHTML\HTMLElement
    {
        return INPUT([
            "type" => "text",
            "name" => "c_{$this->name}",
            "class" => "autocomplete_tags",
            "placeholder" => $this->title,
            "value" => @$inputs["c_{$this->name}"]
        ]);
    }
}
