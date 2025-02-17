<?php

declare(strict_types=1);

namespace Shimmie2;

class S3Config extends ConfigGroup
{
    public const KEY = "s3";
    public ?string $title = "S3 CDN";

    #[ConfigMeta("Access key ID", ConfigType::STRING)]
    public const ACCESS_KEY_ID = 's3_access_key_id';

    #[ConfigMeta("Access key secret", ConfigType::STRING)]
    public const ACCESS_KEY_SECRET = 's3_access_key_secret';

    #[ConfigMeta("Endpoint", ConfigType::STRING)]
    public const ENDPOINT = 's3_endpoint';

    #[ConfigMeta("Image bucket", ConfigType::STRING)]
    public const IMAGE_BUCKET = 's3_image_bucket';
}
