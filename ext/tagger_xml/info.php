<?php

declare(strict_types=1);

namespace Shimmie2;

final class TaggerXMLInfo extends ExtensionInfo
{
    public const KEY = "tagger_xml";

    public string $name = "Tagger AJAX backend";
    public array $authors = ["Artanis (Erik Youngren)" => "mailto:artanis.00@gmail.com"];
    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
    public string $description = "Advanced Tagging v2 AJAX backend";
}
