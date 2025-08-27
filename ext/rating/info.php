<?php

declare(strict_types=1);

namespace Shimmie2;

final class RatingsInfo extends ExtensionInfo
{
    public const KEY = "rating";

    public string $name = "Post Ratings";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::METADATA;
    public string $description = "Allow users to rate images \"safe\", \"questionable\" or \"explicit\"";
    public array $dependencies = [UserConfigEditorInfo::KEY];
    public ?string $documentation =
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
