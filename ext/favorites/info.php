<?php declare(strict_types=1);

class FavoritesInfo extends ExtensionInfo
{
    public const KEY = "favorites";

    public $key = self::KEY;
    public $name = "Favorites";
    public $authors = ["Daniel Marschall"=>"info@daniel-marschall.de"];
    public $license = self::LICENSE_GPLV2;
    public $description = "Allow users to favorite images";
    public $documentation =
"Gives users a \"favorite this image\" button that they can press
<p>Favorites for a user can then be retrieved by searching for \"favorited_by=UserName\"
<p>Popular images can be searched for by eg. \"favorites>5\"
<p>Favorite info can be added to an image's filename or tooltip using the \$favorites placeholder";
}
