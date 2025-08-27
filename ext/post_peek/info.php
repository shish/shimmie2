<?php

declare(strict_types=1);

namespace Shimmie2;

final class PostPeekInfo extends ExtensionInfo
{
    public const KEY = "post_peek";

    public string $name = "Post Peek";
    public array $authors = ["Matthew Barbour" => "mailto:matthew@darkholme.net"];
    public string $license = self::LICENSE_WTFPL;
    public string $description = "Peek at posts";
}
