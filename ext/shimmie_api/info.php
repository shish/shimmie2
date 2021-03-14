<?php declare(strict_types=1);

class ShimmieApiInfo extends ExtensionInfo
{
    public const KEY = "shimmie_api";

    public string $key = self::KEY;
    public string $name = "Shimmie JSON API";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "A JSON interface to shimmie data [WARNING]";
    public ?string $documentation =
"<b>Admin Warning -</b> this exposes private data, eg IP addresses
<p><b>Developer Warning -</b> the API is unstable; notably, private data may get hidden
<p><b><u>Usage:</b></u>
<p><b>get_tags</b> - List of all tags. (May contain unused tags)
<br><ul>tags - <i>Optional</i> - Search for more specific tags (Searchs TAG*)</ul>
<p><b>get_image</b> - Get image via id.
<br><ul>id - <i>Required</i> - User id. (Defaults to id=1 if empty)</ul>
<p><b>find_images</b> - List of latest 12(?) images.
<p><b>get_user</b> - Get user info. (Defaults to id=2 if both are empty)
<br><ul>id - <i>Optional</i> - User id.</ul>
<ul>name - <i>Optional</i> - User name.</ul>";
}
