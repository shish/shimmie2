<?php

declare(strict_types=1);

namespace Shimmie2;

class SpeedHaxInfo extends ExtensionInfo
{
    public const KEY = "speed_hax";

    public string $key = self::KEY;
    public string $name = "Speed Hax";
    public array $authors = [self::SHISH_NAME => self::SHISH_EMAIL, "jgen" => "jgen.tech@gmail.com", "Matthew Barbour" => "matthew@darkholme.net", "Discomrade" => ""];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::ADMIN;
    public string $description = "Show performance tweak options. Read the documentation.";
    public ?string $documentation =
        "Many of these changes reduce the correctness of the software and increase admin workload for the sake of speed. You almost certainly don't want to set some of them, but if you do (e.g. you're trying to run a site with 10,000 concurrent users on a single server), it can be a huge help.
<br><br>
<ul>
<li><code>Don't auto-upgrade database</code>: Database schema upgrades are no longer automatic; you'll need to run <code>php index.php db-upgrade</code> from the CLI each time you update the code.</li>
<li><code>Cache event listeners</code>: Mapping from Events to Extensions is cached - you'll need to delete <code>data/cache/shm_event_listeners.php</code> after each code change, and after enabling or disabling any extensions.</li>
<li><code>Purge cookie on logout</code>: Clears the <code>user</code> cookie when a user logs out, to keep as few versions of content as possible.</li>
<li><code>List only recent comments</code>: Only comments from the past 24 hours show up in <code>/comment/list</code>.</li>
<li><code>Fast page limit</code>: We only show the first 500 pages of results for any query, except for the most simple (no tags, or one positive tag). Web crawlers are blocked from creating too many nonsense searches by limiting to 10 pages.</li>
<li><code>Anonymous search tag limit</code>: Anonymous users can only search for this many tags at once. To disable, set to 0.</li>
<li><code>Limit complex searches</code>: Only ever show the first 5,000 results for complex queries.</li>
<li><code>Fast page limit</code>: We only show the first 500 pages of results for any query, except for the most simple (no tags, or one positive tag) or users with the <code>BIG_SEARCH</code> permission. Web crawlers are blocked from creating too many nonsense searches by limiting to 10 pages. Consider enabling <code>Extra caching on first pages</code> as well.</li>
<li><code>Extra caching on first pages</code>: The first 10 pages in the <code>post/list</code> index get extra caching.</li>
<li><code>Limit images RSS</code>: RSS is limited to 10 pages for the image list.</li>
</ul>
";
}
