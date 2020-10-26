<?php declare(strict_types=1);

class RatingsInfo extends ExtensionInfo
{
    public const KEY = "rating";

    public $key = self::KEY;
    public $name = "Post Ratings";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Allow users to rate images \"safe\", \"questionable\" or \"explicit\"";
    public $documentation =
"This shimmie extension provides filter:
<ul>
  <li>rating = (safe|questionable|explicit|unknown)
    <ul>
      <li>rating=s -- safe images
      <li>rating=q -- questionable images
      <li>rating=e -- explicit images
      <li>rating=u -- Unknown rating
      <li>rating=sq -- safe and questionable images
    </ul>
</ul>";
}
