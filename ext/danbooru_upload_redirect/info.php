<?php

declare(strict_types=1);

namespace Shimmie2;

final class DanbooruUploadRedirectInfo extends ExtensionInfo
{
    public const KEY = "danbooru_upload_redirect";

    public string $name = "Upload to Danbooru support";
    public array $authors = ["Gremling" => "mailto:contact[at]gremling.uk"];
    public string $description = "Enables the 'Upload to Danbooru' extension to work with Shimmie!";
    public ExtensionCategory $category = ExtensionCategory::INTEGRATION;
    public ?string $documentation =
        "<b>You MUST have a transload enabled in order for this to work</b>
        <br><br>
        <b>Notes</b>:
        <br>Simply redirects the link and makes it autofill the form for uploading files in the upload page. It's not fully automatic but it saves a lot of effort!";
}