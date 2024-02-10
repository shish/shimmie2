<?php

declare(strict_types=1);

namespace Shimmie2;

class BulkActionsInfo extends ExtensionInfo
{
    public const KEY = "bulk_actions";

    public string $key = self::KEY;
    public string $name = "Bulk Actions";
    public array $authors = ["Matthew Barbour" => "matthew@darkholme.net"];
    public string $license = self::LICENSE_WTFPL;
    public string $description = "Provides query and selection-based bulk action support";
    public ?string $documentation = "Provides bulk action section in list view. Allows performing actions against a set of posts based on query or manual selection. Based on Mass Tagger by <a href='mailto:walde.christian@googlemail.com'>Christian Walde</a>, contributions by Shish and Agasa.
    <p>
    <p>
        <b>Delete</b>
        <br>Deletes all selected posts.
    </p>
    <p>
        <b>Tag</b>
        <br>Add the tags to all selected posts.
        <br><code>[background wallpaper]</code> + <code>[sky]</code> → <code>[background wallpaper sky]</code>
        <br>
        <br>Remove the tags from all selected posts.
        <br><code>[background wallpaper]</code> + <code>[-wallpaper]</code> → <code>[background]</code>
        <br>
        <br>Replace the tags in all selected posts.
        <br><code>[background wallpaper]</code> + <code>[sky]</code> → <code>[sky]</code>
    </p>
    <p>
        <b>Source</b>
        <br>Sets the source of all selected posts.
    </p>
    </p>";
    public ExtensionCategory $category = ExtensionCategory::MODERATION;
}
