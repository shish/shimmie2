<?php

declare(strict_types=1);

namespace Shimmie2;

class FavoritesInfo extends ExtensionInfo
{
    public const KEY = "favorites";

    public string $key = self::KEY;
    public string $name = "Favorites";
    public array $authors = ["Daniel Marschall" => "info@daniel-marschall.de"];
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Allow users to favorite images";
    public ?string $documentation =
"Gives users a \"favorite this image\" button that they can press
<p>Favorites for a user can then be retrieved by searching for \"favorited_by=UserName\"
<p>Popular images can be searched for by eg. \"favorites>5\"
<p>Favorite info can be added to a post's filename or tooltip using the \$favorites placeholder";
}
