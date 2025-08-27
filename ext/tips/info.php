<?php

declare(strict_types=1);

namespace Shimmie2;

final class TipsInfo extends ExtensionInfo
{
    public const KEY = "tips";

    public string $name = "Random Tip";
    public array $authors = ["Sein Kraft" => "mailto:mail@seinkraft.info"];
    public string $description = "Show a random line of text in the subheader space";
    public ?string $documentation = "Formatting is done with HTML";
    public array $db_support = [DatabaseDriverID::MYSQL, DatabaseDriverID::SQLITE];  // rand() ?
}
