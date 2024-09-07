<?php

declare(strict_types=1);

namespace Shimmie2;

class TermsInfo extends ExtensionInfo
{
    public const KEY = "terms";

    public string $key = self::KEY;
    public string $name = "Terms & Conditions Gate";
    public array $authors = ["discomrade" => ""];
    public string $license = "GPLv2";
    public string $description = "Show a page of terms which must be accepted before continuing";
}
