<?php

declare(strict_types=1);

namespace Shimmie2;

final class TermsInfo extends ExtensionInfo
{
    public const KEY = "terms";

    public string $name = "Terms & Conditions Gate";
    public array $authors = ["discomrade" => null];
    public string $description = "Show a page of terms which must be accepted before continuing";
}
