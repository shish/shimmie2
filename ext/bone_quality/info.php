<?php

declare(strict_types=1);

namespace Shimmie2;

class BoneQualityInfo extends ExtensionInfo
{
    public const KEY = "bone_quality";

    public string $key = self::KEY;
    public string $name = "Bone Quality";
    public array $authors = ["Discomrade" => ""];
    public string $license = self::LICENSE_WTFPL;
    public string $description = "Displays a summary page of booru quality metrics.";
    public ?string $documentation =
        "Inspired by Hydrus's \"how boned am i?\" feature.<br>
        Assumes statistics extension is enabled, so that a navigation link is added under Stats. Otherwise, you can still link to the page at /bone_quality.<br>
        <br>
        <b>Failure word:</b> The word 'boned' might not be appropriate for your booru, so you can replace this with any string, like 'damned', 'swamped', 'over'.<br>
        <b>Chore searches:</b> Checks how many posts match each of the searches. These searches represent posts which need to be fixed, for example:<br>
        <ul>
        <li>posts with certain request tags: <code>tagme</code>, <code>artist_request</code> and <code>translation_request</code></li>
        <li>posts with contradictory tags: <code>solo group</code></li>
        <li>posts with too few tags: <code>tags:&lt;5</code></li>
        <li>posts rated as 'unrated': <code>rating=?</code></li>
        <li>posts without any tags in a tag category: <code>artisttags=0</code></li>, <code>animal speciestags=0</code></li>
        </ul>
        <b>Chore threshold:</b> the number of matches for a chore search the booru can have until you are boned.";
}
