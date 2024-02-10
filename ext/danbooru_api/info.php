<?php

declare(strict_types=1);

namespace Shimmie2;

class DanbooruApiInfo extends ExtensionInfo
{
    public const KEY = "danbooru_api";

    public string $key = self::KEY;
    public string $name = "Danbooru Client API";
    public array $authors = ["JJS" => "jsutinen@gmail.com"];
    public string $description = "Allow Danbooru apps like Danbooru Uploader for Firefox to communicate with Shimmie";
    public ExtensionCategory $category = ExtensionCategory::INTEGRATION;
    public ?string $documentation =
"<b>Notes</b>:
 <br>danbooru API based on documentation from danbooru 1.0 -
 http://attachr.com/7569
 <br>I've only been able to test add_post and find_tags because I use the
 old danbooru firefox extension for firefox 1.5
 <p>Functions currently implemented:
 <ul>
 <li>add_post - title and rating are currently ignored because shimmie does not support them
 <li>find_posts - sort of works, filename is returned as the original filename and probably won't help when it comes to actually downloading it
 <li>find_tags - id, name, and after_id all work but the tags parameter is ignored just like danbooru 1.0 ignores it
 </ul>
<br><b>CHANGELOG</b>
<br>13-OCT-08 8:00PM CST - JJS
<br>Bugfix - Properly escape source attribute
<br>
<br>17-SEP-08 10:00PM CST - JJS
<br>Bugfix for changed page name checker in PageRequestEvent
<br>
<br>13-APR-08 10:00PM CST - JJS
<br>Properly escape the tags returned in find_tags and find_posts - Caught by ATravelingGeek
<br>Updated extension info to be a bit more clear about its purpose
<br>Deleted add_comment code as it didn't do anything anyway
<br>
<br>01-MAR-08 7:00PM CST - JJS
<br>Rewrote to make it compatible with Shimmie trunk again (r723 at least)
<br>It may or may not support the new file handling stuff correctly, I'm only testing with images and the danbooru uploader for firefox
<br>
<br>21-OCT-07 9:07PM CST - JJS
<br>Turns out I actually did need to implement the new parameter names
<br>for danbooru api v1.8.1. Now danbooruup should work when used with /api/danbooru/post/create.xml
<br>Also correctly redirects the url provided by danbooruup in the event
<br>of a duplicate image.
<br>
<br>19-OCT-07 4:46PM CST - JJS
<br>Add compatibility with danbooru api v1.8.1 style urls
<br>for find_posts and add_post. NOTE: This does not implement
<br>the changes to the parameter names, it is simply a
<br>workaround for the latest danbooruup firefox extension.
<br>Completely compatibility will probably involve a rewrite with a different URL";
}
